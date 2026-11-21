<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Services;

use App\Modules\Domains\Xinfutong\Enums\ApprovalStatus;
use App\Modules\Domains\Xinfutong\Enums\EmployeeStatus;
use App\Modules\Domains\Xinfutong\Enums\SyncStatus;
use App\Modules\Domains\Xinfutong\Models\Account;
use App\Modules\Domains\Xinfutong\Models\ApprovalRequest;
use App\Modules\Domains\Xinfutong\Models\Employee;
use App\Modules\Domains\Xinfutong\Models\SyncLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 薪福通 API 服务类
 *
 * 封装与招商银行薪福通开放平台的所有 API 交互。
 * 使用国密 SM2/SM3 算法进行请求签名认证。
 * 支持多组账号配置（从数据库读取）。
 *
 * @see https://xft.cmbchina.com/open/#/doc
 */
class XinfutongService
{
    private ?Account $account;

    /**
     * @param  Account|int|string|null  $account  账号实例、ID、企业名称，null 则使用默认账号
     */
    public function __construct(Account|int|string|null $account = null)
    {
        $this->account = $this->resolveAccount($account);

        if ($this->account === null) {
            throw new \RuntimeException('薪福通账号未配置，请先在账号管理中添加账号');
        }
    }

    /**
     * 解析账号.
     */
    private function resolveAccount(Account|int|string|null $account): ?Account
    {
        if ($account instanceof Account) {
            return $account;
        }

        if (is_int($account)) {
            return Account::find($account);
        }

        if (is_string($account)) {
            return Account::where('company_name', $account)
                ->orWhere('id', $account)
                ->first();
        }

        return Account::getDefault();
    }

    /**
     * 使用国密 SM2/SM3 对请求进行签名.
     *
     * @param  array{app_id: string, timestamp: string, nonce: string, body: string}  $params
     */
    public function signRequest(array $params): string
    {
        $privateKey = $this->account->sign_private_key;
        if (empty($privateKey)) {
            throw new \RuntimeException('薪福通签名私钥未配置');
        }

        $data = $params['app_id'].$params['timestamp'].$params['nonce'].$params['body'];

        // 尝试使用 SM3 + SM2（需要 PHP openssl 扩展支持国密）
        if (defined('OPENSSL_ALGO_SM2') && defined('OPENSSL_ALGO_SM3')) {
            $hash = openssl_digest($data, 'SM3', true);
            $signature = '';
            $key = openssl_pkey_get_private($privateKey);
            if ($key === false) {
                throw new \RuntimeException('薪福通签名私钥加载失败: '.openssl_error_string());
            }
            openssl_sign($hash, $signature, $key, OPENSSL_ALGO_SM2);
            openssl_pkey_free($key);

            return base64_encode($signature);
        }

        // 回退方案：使用 SHA256 + RSA（需确保与薪福通协商兼容）
        // 注意：生产环境应使用国密 SM2/SM3
        Log::warning('薪福通：SM2/SM3 不可用，使用 RSA-SHA256 回退签名');

        $signature = '';
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new \RuntimeException('薪福通签名私钥加载失败: '.openssl_error_string());
        }

        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_pkey_free($key);

        return base64_encode($signature);
    }

    /**
     * 生成请求时间戳（毫秒级）.
     */
    public function generateTimestamp(): string
    {
        return (string) round(microtime(true) * 1000);
    }

    /**
     * 生成随机数.
     */
    public function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * 调用薪福通 API（国密签名认证）.
     *
     * @param  array<string, mixed>  $body  请求体
     */
    public function callApi(string $endpoint, array $body = []): array
    {
        $appId = $this->account->app_id;
        $authoritySecret = $this->account->authority_secret;

        if (empty($appId) || empty($authoritySecret)) {
            throw new \RuntimeException('薪福通 AppId 或 AuthoritySecret 未配置');
        }

        $timestamp = $this->generateTimestamp();
        $nonce = $this->generateNonce();

        $requestBody = array_merge([
            'appId' => $appId,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ], $body);

        $bodyJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE);

        // 生成签名
        $signature = $this->signRequest([
            'app_id' => $appId,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'body' => $bodyJson,
        ]);

        $baseUrl = rtrim($this->account->base_url, '/');
        $url = $baseUrl.$endpoint;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'AppId' => $appId,
            'Timestamp' => $timestamp,
            'Nonce' => $nonce,
            'Signature' => $signature,
        ])
            ->timeout($this->account->timeout ?? 30)
            ->withoutVerifying()
            ->post($url, $requestBody);

        if ($response->failed()) {
            Log::error('薪福通 API 调用失败', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('薪福通 API 调用失败: '.$response->body());
        }

        $result = $response->json();

        // 检查业务状态码
        $code = $result['code'] ?? $result['status'] ?? 0;
        if ($code !== 0 && $code !== '0' && $code !== 200) {
            $message = $result['message'] ?? $result['msg'] ?? '未知错误';
            Log::error('薪福通 API 业务错误', [
                'endpoint' => $endpoint,
                'code' => $code,
                'message' => $message,
            ]);

            throw new \RuntimeException("薪福通 API 业务错误 [{$code}]: {$message}");
        }

        return $result;
    }

    /**
     * 调用薪福通 API 拉取员工列表.
     *
     * @return array{data: array, total: int}
     */
    public function fetchEmployees(int $page = 1, int $pageSize = 100): array
    {
        $result = $this->callApi('/api/v1/employee/list', [
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ]);

        return [
            'data' => $result['data']['list'] ?? $result['data']['items'] ?? $result['data'] ?? [],
            'total' => $result['data']['total'] ?? 0,
        ];
    }

    /**
     * 全量同步员工数据到本地数据库.
     */
    public function syncEmployees(): SyncLog
    {
        $log = SyncLog::create([
            'sync_type' => 'full',
            'status' => SyncStatus::PENDING,
            'started_at' => now(),
        ]);

        try {
            $page = 1;
            $pageSize = 100;
            $totalFetched = 0;
            $totalCreated = 0;
            $totalUpdated = 0;

            do {
                $result = $this->fetchEmployees($page, $pageSize);
                $employees = $result['data'];
                $totalFetched += count($employees);

                foreach ($employees as $item) {
                    $xinfutongId = (string) ($item['employeeId'] ?? $item['empId'] ?? $item['id'] ?? '');

                    if ($xinfutongId === '') {
                        continue;
                    }

                    $attributes = [
                        'xinfutong_id' => $xinfutongId,
                    ];

                    $values = [
                        'employee_no' => $item['empNo'] ?? $item['jobNumber'] ?? $item['employeeNo'] ?? '',
                        'name' => $item['empName'] ?? $item['name'] ?? '',
                        'gender' => $item['gender'] ?? null,
                        'phone' => $item['mobile'] ?? $item['phone'] ?? null,
                        'email' => $item['email'] ?? null,
                        'department' => $item['deptName'] ?? $item['department'] ?? null,
                        'position' => $item['positionName'] ?? $item['position'] ?? null,
                        'hire_date' => $item['entryDate'] ?? $item['hireDate'] ?? null,
                        'status' => $this->mapStatus($item['status'] ?? 'active'),
                        'raw_data' => $item,
                        'last_synced_at' => now(),
                    ];

                    $existing = Employee::where('xinfutong_id', $xinfutongId)->first();

                    if ($existing) {
                        $existing->update($values);
                        $totalUpdated++;
                    } else {
                        Employee::create(array_merge($attributes, $values));
                        $totalCreated++;
                    }
                }

                $hasMore = count($employees) >= $pageSize;
                $page++;
            } while ($hasMore);

            $log->update([
                'status' => SyncStatus::SUCCESS,
                'records_fetched' => $totalFetched,
                'records_created' => $totalCreated,
                'records_updated' => $totalUpdated,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => SyncStatus::FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('薪福通员工同步失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $log;
    }

    /**
     * 将薪福通状态映射到本地枚举.
     */
    private function mapStatus(string $externalStatus): EmployeeStatus
    {
        return match (strtolower($externalStatus)) {
            'inactive', 'disabled', '停用' => EmployeeStatus::INACTIVE,
            'resigned', 'quit', '离职' => EmployeeStatus::RESIGNED,
            default => EmployeeStatus::ACTIVE,
        };
    }

    /**
     * 提交审批申请到薪福通（发起流程V2）.
     *
     * @see https://xft.cmbchina.com/open/#/doc/open-document?id=10692&mid=18973
     */
    public function submitApproval(ApprovalRequest $approval): ApprovalRequest
    {
        $approval->update(['status' => ApprovalStatus::PENDING]);

        try {
            // 发起流程V2 接口
            $endpoint = '/api/v1/workflow/start';

            $body = [
                'workflowType' => $approval->approval_type,
                'title' => $approval->title,
                'description' => $approval->description,
                'initiator' => [
                    'name' => $approval->applicant_name,
                    'employeeId' => $approval->applicant_id,
                ],
                'formFields' => $this->buildFormFields($approval->form_data),
                'externalBusinessNo' => $approval->approval_no,
            ];

            $result = $this->callApi($endpoint, $body);

            $xinfutongId = $result['data']['workflowId'] ?? $result['data']['instanceId'] ?? $result['data']['id'] ?? null;

            $approval->update([
                'status' => ApprovalStatus::PENDING,
                'xinfutong_approval_id' => $xinfutongId,
                'raw_response' => $result,
                'submitted_at' => now(),
            ]);

            Log::info('薪福通审批提交成功', [
                'approval_no' => $approval->approval_no,
                'xinfutong_id' => $xinfutongId,
            ]);

            return $approval;
        } catch (\Throwable $e) {
            $approval->update([
                'status' => ApprovalStatus::DRAFT,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('薪福通审批提交失败', [
                'approval_no' => $approval->approval_no,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 构建表单字段数据.
     *
     * @param  array<string, mixed>  $formData
     * @return array<int, array{fieldCode: string, fieldValue: mixed}>
     */
    private function buildFormFields(array $formData): array
    {
        $fields = [];
        $index = 0;

        foreach ($formData as $code => $value) {
            $fields[] = [
                'fieldCode' => $code,
                'fieldValue' => $value,
                'sort' => $index,
            ];
            $index++;
        }

        return $fields;
    }

    /**
     * 查询审批状态.
     *
     * @see https://xft.cmbchina.com/open/#/doc/open-document?id=10692&mid=10684
     */
    public function queryApprovalStatus(ApprovalRequest $approval): ApprovalRequest
    {
        if ($approval->xinfutong_approval_id === null) {
            return $approval;
        }

        try {
            $endpoint = '/api/v1/workflow/status';

            $result = $this->callApi($endpoint, [
                'workflowId' => $approval->xinfutong_approval_id,
            ]);

            $status = $result['data']['status'] ?? null;
            $newStatus = match ($status) {
                'approved', 'completed' => ApprovalStatus::APPROVED,
                'rejected' => ApprovalStatus::REJECTED,
                'withdrawn', 'cancelled' => ApprovalStatus::WITHDRAWN,
                default => ApprovalStatus::PENDING,
            };

            $approval->update([
                'status' => $newStatus,
                'raw_response' => $result,
                'approved_at' => in_array($newStatus, [ApprovalStatus::APPROVED, ApprovalStatus::REJECTED]) ? now() : null,
            ]);

            return $approval;
        } catch (\Throwable $e) {
            Log::error('薪福通审批状态查询失败', [
                'approval_no' => $approval->approval_no,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 撤回审批.
     */
    public function withdrawApproval(ApprovalRequest $approval): ApprovalRequest
    {
        if ($approval->xinfutong_approval_id === null) {
            throw new \RuntimeException('审批单未提交到薪福通，无法撤回');
        }

        try {
            $endpoint = '/api/v1/workflow/withdraw';

            $result = $this->callApi($endpoint, [
                'workflowId' => $approval->xinfutong_approval_id,
            ]);

            $approval->update([
                'status' => ApprovalStatus::WITHDRAWN,
                'raw_response' => $result,
            ]);

            return $approval;
        } catch (\Throwable $e) {
            Log::error('薪福通审批撤回失败', [
                'approval_no' => $approval->approval_no,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 获取当前账号.
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * 获取所有可用账号列表.
     *
     * @return array<int, array{id: int, company_name: string, base_url: string, app_id: string}>
     */
    public static function getAvailableAccounts(): array
    {
        /** @var Collection<int, Account> $accounts */
        $accounts = Account::getActive();

        return $accounts
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'company_name' => $account->company_name,
                'base_url' => $account->base_url,
                'app_id' => $account->app_id,
            ])
            ->toArray();
    }
}

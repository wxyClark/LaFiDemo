<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Services;

use App\Modules\Domains\Base\ApiTesting\Enums\TestStatus;
use App\Modules\Domains\Base\ApiTesting\Models\ApiEnvironment;
use App\Modules\Domains\Base\ApiTesting\Models\ApiInterface;
use App\Modules\Domains\Base\ApiTesting\Models\ApiTestBatch;
use App\Modules\Domains\Base\ApiTesting\Models\ApiTestCase;
use App\Modules\Domains\Base\ApiTesting\Models\ApiTestResult;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;

class TestExecutorService
{
    /**
     * 执行单个测试用例
     */
    public function execute(ApiTestCase $testCase, ?ApiEnvironment $environment = null): ApiTestResult
    {
        /** @var ApiEnvironment $environment */
        $environment = $environment ?? $testCase->environment;
        $interface = $testCase->interface;

        // 构建请求 URL
        $url = rtrim($environment->base_url, '/').'/'.ltrim($interface->path, '/');

        // 合并 Headers
        $headers = array_merge(
            $environment->headers ?? [],
            $interface->headers ?? [],
            $testCase->headers ?? []
        );

        // 解析前置认证（JWT / Session / API Key），必要时先登录获取凭证
        [$headers, $cookies] = $this->resolveAuth($headers, $environment);

        // 合并 Query 参数
        $queryParams = $testCase->query_params ?? [];

        // 准备请求 Body
        $body = $testCase->body;

        $startTime = microtime(true);

        try {
            $request = Http::withHeaders($headers)
                ->timeout(30)
                ->withoutVerifying();

            if (! empty($cookies)) {
                $request = $request->withCookies($cookies, $this->cookieDomain($environment->base_url));
            }

            // 添加 Query 参数
            if (! empty($queryParams)) {
                $request = $request->withQueryParameters($queryParams);
            }

            // 发送请求
            $response = match ($interface->method->value) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $body),
                'PUT' => $request->put($url, $body),
                'PATCH' => $request->patch($url, $body),
                'DELETE' => $request->delete($url),
                default => $request->get($url),
            };

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            // 解析响应
            $responseBody = $response->json();
            $responseStatus = $response->status();
            $responseHeaders = $response->headers();

            // 执行断言
            $assertionResults = $this->runAssertions(
                $testCase,
                $responseStatus,
                $responseBody,
                $responseTime
            );

            $allPassed = collect($assertionResults)->every(fn ($a) => $a['passed']);

            return ApiTestResult::create([
                'test_case_id' => $testCase->id,
                'interface_id' => $interface->id,
                'environment_id' => $environment->id,
                'status' => $allPassed ? TestStatus::PASS : TestStatus::FAIL,
                'request_url' => $url,
                'request_method' => $interface->method->value,
                'request_headers' => $headers,
                'request_body' => $body,
                'response_status' => $responseStatus,
                'response_headers' => $responseHeaders,
                'response_body' => $responseBody,
                'response_time' => $responseTime,
                'assertion_results' => $assertionResults,
                'executed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return ApiTestResult::create([
                'test_case_id' => $testCase->id,
                'interface_id' => $interface->id,
                'environment_id' => $environment->id,
                'status' => TestStatus::ERROR,
                'request_url' => $url,
                'request_method' => $interface->method->value,
                'request_headers' => $headers,
                'request_body' => $body,
                'response_status' => null,
                'response_body' => null,
                'response_time' => $responseTime,
                'error_message' => $e->getMessage(),
                'assertion_results' => [],
                'executed_at' => now(),
            ]);
        }
    }

    /**
     * 批量执行测试用例
     *
     * @param  iterable<ApiTestCase>  $testCases
     */
    public function executeBatch(iterable $testCases, ?ApiEnvironment $environment = null): array
    {
        $results = [];
        foreach ($testCases as $testCase) {
            $results[] = $this->execute($testCase, $environment);
        }

        return $results;
    }

    /**
     * 创建批量测试记录并执行全部用例
     *
     * @param  iterable<ApiTestCase>  $testCases
     */
    public function executeBatchAndRecord(iterable $testCases, ?ApiEnvironment $environment = null): ApiTestBatch
    {
        $batch = ApiTestBatch::create([
            'name' => '批量测试 '.now()->format('Y-m-d H:i:s'),
            'test_case_ids' => collect($testCases)->map(fn (ApiTestCase $tc) => $tc->id)->toArray(),
        ]);

        $this->executeBatch($testCases, $environment);

        return $batch;
    }

    /**
     * 执行接口的所有测试用例
     */
    public function executeAllForInterface(ApiInterface $interface, ?ApiEnvironment $environment = null): array
    {
        $testCases = $interface->testCases()->orderBy('sort_order')->get();

        return $this->executeBatch($testCases->toArray(), $environment);
    }

    /**
     * 解析前置认证
     *
     * 根据环境 auth_type 区分认证方式：
     * - none:   无认证
     * - jwt:    先登录获取 token，注入 Authorization Header
     * - session:先登录获取 Session Cookie，后续请求携带该 Cookie
     * - apikey: 直接注入 API Key Header
     *
     * @return array{0: array, 1: array<string, string>} [headers, cookies]
     */
    protected function resolveAuth(array $headers, ApiEnvironment $environment): array
    {
        if ($environment->auth_type->value === 'none') {
            return [$headers, []];
        }

        $authConfig = $environment->auth_config ?? [];

        return match ($environment->auth_type->value) {
            'jwt' => [$this->resolveJwtAuth($headers, $authConfig, $environment), []],
            'session' => [[], $this->resolveSessionAuth($authConfig, $environment)],
            'apikey' => [$this->resolveApiKeyAuth($headers, $authConfig), []],
        };
    }

    /**
     * 前置登录获取 JWT，并注入 Authorization Header
     */
    protected function resolveJwtAuth(array $headers, array $config, ApiEnvironment $environment): array
    {
        $tokenUrl = $config['token_url'] ?? null;
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;
        $headerName = $config['header_name'] ?? 'Authorization';
        $headerPrefix = $config['header_prefix'] ?? 'Bearer';
        $tokenPath = $config['token_path'] ?? 'token';

        if (! $tokenUrl || ! $username || ! $password) {
            return $headers;
        }

        try {
            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->post($this->baseUrl($environment).$tokenUrl, [
                    $config['username_field'] ?? 'email' => $username,
                    $config['password_field'] ?? 'password' => $password,
                ]);

            if ($response->successful()) {
                $token = data_get($response->json(), $tokenPath);
                if ($token) {
                    $headers[$headerName] = $headerPrefix.' '.$token;
                }
            }
        } catch (\Throwable $e) {
            // 认证失败，继续执行
        }

        return $headers;
    }

    /**
     * 前置登录获取 Session Cookie
     *
     * @return array<string, string>
     */
    protected function resolveSessionAuth(array $config, ApiEnvironment $environment): array
    {
        $loginUrl = $config['login_url'] ?? null;
        $username = $config['username'] ?? null;
        $password = $config['password'] ?? null;

        if (! $loginUrl || ! $username || ! $password) {
            return [];
        }

        try {
            $jar = new CookieJar;

            $response = Http::withHeaders(['Accept' => 'application/json'])
                ->withOptions(['cookies' => $jar])
                ->post($this->baseUrl($environment).$loginUrl, [
                    $config['username_field'] ?? 'email' => $username,
                    $config['password_field'] ?? 'password' => $password,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $cookies = [];
            foreach ($jar->toArray() as $cookie) {
                $cookies[$cookie['Name']] = $cookie['Value'];
            }

            // 兜底：从 Set-Cookie 响应头解析（兼容未启用 CookieJar 的场景）
            if ($cookies === []) {
                $setCookies = (array) $response->header('Set-Cookie');
                foreach ($setCookies as $setCookie) {
                    $parts = explode(';', (string) $setCookie);
                    $first = $parts[0];
                    $pair = explode('=', $first, 2);
                    $name = trim($pair[0]);
                    $value = trim($pair[1] ?? '');
                    if ($name !== '') {
                        $cookies[$name] = $value;
                    }
                }
            }

            return $cookies;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 注入 API Key
     */
    protected function resolveApiKeyAuth(array $headers, array $config): array
    {
        $keyName = $config['key_name'] ?? 'X-API-Key';
        $keyValue = $config['key_value'] ?? null;
        $keyLocation = $config['key_location'] ?? 'header';

        if ($keyLocation === 'header' && $keyValue) {
            $headers[$keyName] = $keyValue;
        }

        return $headers;
    }

    protected function baseUrl(ApiEnvironment $environment): string
    {
        return rtrim($environment->base_url, '/').'/';
    }

    protected function cookieDomain(string $baseUrl): string
    {
        $host = (string) parse_url($baseUrl, PHP_URL_HOST);

        return $host !== '' ? $host : 'localhost';
    }

    /**
     * 执行断言
     */
    protected function runAssertions(
        ApiTestCase $testCase,
        int $responseStatus,
        mixed $responseBody,
        int $responseTime
    ): array {
        $results = [];

        // 状态码断言
        $results[] = [
            'type' => 'status',
            'expected' => $testCase->expected_status,
            'actual' => $responseStatus,
            'passed' => $testCase->expected_status === $responseStatus,
        ];

        // 响应时间断言
        if ($testCase->expected_response_time) {
            $results[] = [
                'type' => 'response_time',
                'expected' => '<= '.$testCase->expected_response_time,
                'actual' => $responseTime,
                'passed' => $responseTime <= $testCase->expected_response_time,
            ];
        }

        // 数据值断言
        if ($testCase->expected_data) {
            foreach ($testCase->expected_data as $assertion) {
                $path = $assertion['path'] ?? '';
                $operator = $assertion['operator'] ?? 'equals';
                $expected = $assertion['expected'] ?? null;

                $actual = data_get($responseBody, $path);
                $passed = $this->evaluateAssertion($operator, $actual, $expected);

                $results[] = [
                    'type' => 'data',
                    'path' => $path,
                    'operator' => $operator,
                    'expected' => $expected,
                    'actual' => $actual,
                    'passed' => $passed,
                ];
            }
        }

        return $results;
    }

    /**
     * 评估断言
     */
    protected function evaluateAssertion(string $operator, mixed $actual, mixed $expected): bool
    {
        return match ($operator) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'gt' => $actual > $expected,
            'gte' => $actual >= $expected,
            'lt' => $actual < $expected,
            'lte' => $actual <= $expected,
            'contains' => str_contains((string) $actual, (string) $expected),
            'exists' => $actual !== null,
            'not_exists' => $actual === null,
            'in' => in_array($actual, (array) $expected),
            'not_in' => ! in_array($actual, (array) $expected),
            'type' => gettype($actual) === $expected,
            default => false,
        };
    }
}

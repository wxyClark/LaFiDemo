<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Contracts;

use App\Modules\Domains\Xinfutong\Models\ApprovalRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * 审批请求仓储接口
 */
interface ApprovalRepositoryInterface
{
    public function findById(int $id): ?ApprovalRequest;

    public function findByApprovalNo(string $approvalNo): ?ApprovalRequest;

    public function create(array $data): ApprovalRequest;

    public function update(ApprovalRequest $approval, array $data): ApprovalRequest;

    public function delete(ApprovalRequest $approval): bool;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function getByStatus(string $status): Collection;

    public function getByType(string $type): Collection;
}

<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Contracts;

use App\Modules\Domains\Xinfutong\Models\ApprovalRequest;

/**
 * 审批服务接口
 */
interface ApprovalServiceInterface
{
    public function createApproval(array $data): ApprovalRequest;

    public function updateApproval(ApprovalRequest $approval, array $data): ApprovalRequest;

    public function deleteApproval(ApprovalRequest $approval): bool;

    public function submit(ApprovalRequest $approval): ApprovalRequest;

    public function withdraw(ApprovalRequest $approval): ApprovalRequest;

    public function queryStatus(ApprovalRequest $approval): ApprovalRequest;

    public function findApprovalById(int $id): ?ApprovalRequest;

    public function findByApprovalNo(string $approvalNo): ?ApprovalRequest;
}

<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Services;

use App\Modules\Domains\Xinfutong\Contracts\ApprovalRepositoryInterface;
use App\Modules\Domains\Xinfutong\Contracts\ApprovalServiceInterface;
use App\Modules\Domains\Xinfutong\Models\ApprovalRequest;
use App\Modules\Infrastructure\Services\BaseService;

class ApprovalService extends BaseService implements ApprovalServiceInterface
{
    public function __construct(
        private readonly ApprovalRepositoryInterface $approvalRepository,
        private readonly XinfutongService $xinfutongService
    ) {}

    public function createApproval(array $data): ApprovalRequest
    {
        return $this->approvalRepository->create($data);
    }

    public function updateApproval(ApprovalRequest $approval, array $data): ApprovalRequest
    {
        return $this->approvalRepository->update($approval, $data);
    }

    public function deleteApproval(ApprovalRequest $approval): bool
    {
        return $this->approvalRepository->delete($approval);
    }

    public function submit(ApprovalRequest $approval): ApprovalRequest
    {
        return $this->xinfutongService->submitApproval($approval);
    }

    public function withdraw(ApprovalRequest $approval): ApprovalRequest
    {
        return $this->xinfutongService->withdrawApproval($approval);
    }

    public function queryStatus(ApprovalRequest $approval): ApprovalRequest
    {
        return $this->xinfutongService->queryApprovalStatus($approval);
    }

    public function findApprovalById(int $id): ?ApprovalRequest
    {
        return $this->approvalRepository->findById($id);
    }

    public function findByApprovalNo(string $approvalNo): ?ApprovalRequest
    {
        return $this->approvalRepository->findByApprovalNo($approvalNo);
    }
}

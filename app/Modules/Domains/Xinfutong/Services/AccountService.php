<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Services;

use App\Modules\Domains\Xinfutong\Contracts\AccountRepositoryInterface;
use App\Modules\Domains\Xinfutong\Contracts\AccountServiceInterface;
use App\Modules\Domains\Xinfutong\Models\Account;
use App\Modules\Infrastructure\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

class AccountService extends BaseService implements AccountServiceInterface
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository
    ) {}

    public function createAccount(array $data): Account
    {
        return $this->accountRepository->create($data);
    }

    public function updateAccount(Account $account, array $data): Account
    {
        return $this->accountRepository->update($account, $data);
    }

    public function deleteAccount(Account $account): bool
    {
        return $this->accountRepository->delete($account);
    }

    public function getDefault(): ?Account
    {
        return $this->accountRepository->getDefault();
    }

    public function getActive(): Collection
    {
        return $this->accountRepository->getActive();
    }

    public function setAsDefault(Account $account): void
    {
        $this->accountRepository->setAsDefault($account);
    }
}

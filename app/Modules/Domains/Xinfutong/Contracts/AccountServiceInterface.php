<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Contracts;

use App\Modules\Domains\Xinfutong\Models\Account;
use Illuminate\Database\Eloquent\Collection;

/**
 * 账号服务接口
 */
interface AccountServiceInterface
{
    public function createAccount(array $data): Account;

    public function updateAccount(Account $account, array $data): Account;

    public function deleteAccount(Account $account): bool;

    public function getDefault(): ?Account;

    public function getActive(): Collection;

    public function setAsDefault(Account $account): void;
}

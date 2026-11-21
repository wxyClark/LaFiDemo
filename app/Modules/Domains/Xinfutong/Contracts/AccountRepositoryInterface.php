<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Contracts;

use App\Modules\Domains\Xinfutong\Models\Account;
use Illuminate\Database\Eloquent\Collection;

/**
 * 账号仓储接口
 */
interface AccountRepositoryInterface
{
    public function findById(int $id): ?Account;

    public function findByAppId(string $appId): ?Account;

    public function getDefault(): ?Account;

    public function getActive(): Collection;

    public function create(array $data): Account;

    public function update(Account $account, array $data): Account;

    public function delete(Account $account): bool;

    public function setAsDefault(Account $account): void;
}

<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Repositories;

use App\Modules\Domains\User\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;

    public function findByEmail(string $email): ?Customer;

    public function create(array $data): Customer;

    public function updateModel(Customer $customer, array $data): Customer;

    public function deleteModel(Customer $customer): bool;

    public function emailExists(string $email, ?int $excludeId = null): bool;

    public function paginate(int $perPage = 20, array $columns = ['*']): LengthAwarePaginator;
}

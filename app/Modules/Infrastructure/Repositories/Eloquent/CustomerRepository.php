<?php

declare(strict_types=1);

namespace App\Modules\Infrastructure\Repositories\Eloquent;

use App\Modules\Domains\User\Repositories\CustomerRepositoryInterface;
use App\Modules\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Customer
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->findBy('email', $email);
    }

    public function create(array $data): Customer
    {
        return parent::create($data);
    }

    public function updateModel(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->refresh();
    }

    public function deleteModel(Customer $customer): bool
    {
        return $customer->delete();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->query()->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function paginate(int $perPage = 20, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->orderByDesc('id')->paginate($perPage, $columns);
    }
}

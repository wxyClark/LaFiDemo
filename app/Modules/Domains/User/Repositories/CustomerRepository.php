<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Repositories;

use App\Modules\Domains\User\Models\Customer;
use App\Modules\Infrastructure\Repositories\Eloquent\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Customer
    {
        /** @var Customer|null $customer */
        $customer = $this->find($id);

        return $customer;
    }

    public function findByEmail(string $email): ?Customer
    {
        /** @var Customer|null $customer */
        $customer = $this->findBy('email', $email);

        return $customer;
    }

    public function create(array $data): Customer
    {
        /** @var Customer $customer */
        $customer = parent::create($data);

        return $customer;
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

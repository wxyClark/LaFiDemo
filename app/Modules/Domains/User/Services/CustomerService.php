<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Services;

use App\Modules\Domains\User\Data\CustomerCreateData;
use App\Modules\Domains\User\Data\CustomerUpdateData;
use App\Modules\Domains\User\Events\CustomerDeleted;
use App\Modules\Domains\User\Events\CustomerRegistered;
use App\Modules\Domains\User\Events\CustomerUpdated;
use App\Modules\Domains\User\Repositories\CustomerRepositoryInterface;
use App\Modules\Infrastructure\Services\BaseService;
use App\Modules\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CustomerService extends BaseService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {}

    public function register(array $data): Customer
    {
        $data = CustomerCreateData::fromArray($data);

        if ($this->customerRepository->emailExists($data->email)) {
            throw ValidationException::withMessages([
                'email' => ['邮箱已注册'],
            ]);
        }

        $customer = $this->customerRepository->create($data->toArray());

        event(new CustomerRegistered($customer));

        return $customer;
    }

    public function findCustomerById(int $id): ?Customer
    {
        return $this->customerRepository->findById($id);
    }

    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $data = CustomerUpdateData::fromArray($data);
        $updateData = $data->toArray();

        if (isset($updateData['email']) && $updateData['email'] !== $customer->email) {
            if ($this->customerRepository->emailExists($updateData['email'], $customer->id)) {
                throw ValidationException::withMessages([
                    'email' => ['邮箱已被使用'],
                ]);
            }
        }

        $customer = $this->customerRepository->updateModel($customer, $updateData);

        event(new CustomerUpdated($customer));

        return $customer;
    }

    public function deleteCustomer(Customer $customer): bool
    {
        $customerId = $customer->id;
        $result = $this->customerRepository->deleteModel($customer);

        if ($result) {
            event(new CustomerDeleted($customerId));
        }

        return $result;
    }

    public function paginateCustomers(int $perPage = 20): LengthAwarePaginator
    {
        return $this->customerRepository->paginate($perPage);
    }
}

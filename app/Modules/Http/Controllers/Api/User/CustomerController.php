<?php

declare(strict_types=1);

namespace App\Modules\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Modules\Http\Requests\Api\User\StoreCustomerRequest;
use App\Modules\Http\Requests\Api\User\UpdateCustomerRequest;
use App\Modules\Http\Resources\CustomerResource;
use App\Modules\Infrastructure\Support\Traits\ApiResponse;
use App\Modules\Models\Customer;
use App\Modules\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $customers = $this->customerService->paginateCustomers($perPage);

        return $this->paginated($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->register($request->validated());

        return $this->created(new CustomerResource($customer), '用户创建成功');
    }

    public function show(Customer $customer): JsonResponse
    {
        return $this->success(new CustomerResource($customer));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->customerService->updateCustomer($customer, $request->validated());

        return $this->updated(new CustomerResource($customer), '用户更新成功');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->customerService->deleteCustomer($customer);

        return $this->deleted('用户删除成功');
    }
}

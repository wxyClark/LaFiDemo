<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Services;

use App\Modules\Domains\Xinfutong\Contracts\EmployeeRepositoryInterface;
use App\Modules\Domains\Xinfutong\Contracts\EmployeeServiceInterface;
use App\Modules\Domains\Xinfutong\Models\Employee;
use App\Modules\Infrastructure\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EmployeeService extends BaseService implements EmployeeServiceInterface
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository
    ) {}

    public function createEmployee(array $data): Employee
    {
        return $this->employeeRepository->create($data);
    }

    public function updateEmployee(Employee $employee, array $data): Employee
    {
        return $this->employeeRepository->update($employee, $data);
    }

    public function deleteEmployee(Employee $employee): bool
    {
        return $this->employeeRepository->delete($employee);
    }

    public function findEmployeeById(int $id): ?Employee
    {
        return $this->employeeRepository->findById($id);
    }

    public function findByXinfutongId(string $xinfutongId): ?Employee
    {
        return $this->employeeRepository->findByXinfutongId($xinfutongId);
    }

    public function paginateEmployees(int $perPage = 15): LengthAwarePaginator
    {
        return $this->employeeRepository->paginate($perPage);
    }

    public function getByStatus(string $status): Collection
    {
        return $this->employeeRepository->getByStatus($status);
    }

    public function getByDepartment(string $department): Collection
    {
        return $this->employeeRepository->getByDepartment($department);
    }

    public function search(string $keyword): Collection
    {
        return $this->employeeRepository->search($keyword);
    }
}

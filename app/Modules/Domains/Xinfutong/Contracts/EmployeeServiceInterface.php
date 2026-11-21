<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Contracts;

use App\Modules\Domains\Xinfutong\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * 员工服务接口
 */
interface EmployeeServiceInterface
{
    public function createEmployee(array $data): Employee;

    public function updateEmployee(Employee $employee, array $data): Employee;

    public function deleteEmployee(Employee $employee): bool;

    public function findEmployeeById(int $id): ?Employee;

    public function findByXinfutongId(string $xinfutongId): ?Employee;

    public function paginateEmployees(int $perPage = 15): LengthAwarePaginator;

    public function getByStatus(string $status): Collection;

    public function getByDepartment(string $department): Collection;

    public function search(string $keyword): Collection;
}

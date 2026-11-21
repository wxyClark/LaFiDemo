<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Contracts;

use App\Modules\Domains\Xinfutong\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * 员工仓储接口
 */
interface EmployeeRepositoryInterface
{
    public function findById(int $id): ?Employee;

    public function findByXinfutongId(string $xinfutongId): ?Employee;

    public function create(array $data): Employee;

    public function update(Employee $employee, array $data): Employee;

    public function delete(Employee $employee): bool;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function getByStatus(string $status): Collection;

    public function getByDepartment(string $department): Collection;

    public function search(string $keyword): Collection;
}

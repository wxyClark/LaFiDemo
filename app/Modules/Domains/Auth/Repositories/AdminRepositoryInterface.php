<?php

declare(strict_types=1);

namespace App\Modules\Domains\Auth\Repositories;

use App\Modules\Domains\Auth\Models\Admin;

interface AdminRepositoryInterface
{
    public function findById(int $id): ?Admin;

    public function findByEmail(string $email): ?Admin;

    public function create(array $data): Admin;

    public function emailExists(string $email): bool;
}

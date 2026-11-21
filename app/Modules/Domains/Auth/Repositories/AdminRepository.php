<?php

declare(strict_types=1);

namespace App\Modules\Domains\Auth\Repositories;

use App\Modules\Domains\Auth\Models\Admin;
use App\Modules\Infrastructure\Repositories\Eloquent\BaseRepository;

class AdminRepository extends BaseRepository implements AdminRepositoryInterface
{
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Admin
    {
        /** @var Admin|null $admin */
        $admin = $this->find($id);

        return $admin;
    }

    public function findByEmail(string $email): ?Admin
    {
        /** @var Admin|null $admin */
        $admin = $this->findBy('email', $email);

        return $admin;
    }

    public function create(array $data): Admin
    {
        /** @var Admin $admin */
        $admin = parent::create($data);

        return $admin;
    }

    public function emailExists(string $email): bool
    {
        return $this->exists(['email' => $email]);
    }
}

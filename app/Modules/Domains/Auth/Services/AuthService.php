<?php

declare(strict_types=1);

namespace App\Modules\Domains\Auth\Services;

use App\Modules\Domains\Auth\Data\AuthResult;
use App\Modules\Domains\Auth\Data\LoginData;
use App\Modules\Domains\Auth\Data\RegisterData;
use App\Modules\Domains\Auth\Events\AdminRegistered;
use App\Modules\Domains\Auth\Models\Admin;
use App\Modules\Domains\Auth\Repositories\AdminRepositoryInterface;
use App\Modules\Infrastructure\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;
use Tymon\JWTAuth\JWTAuth;

class AuthService extends BaseService
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
        private readonly JWTAuth $jwt,
    ) {}

    public function register(array $data): AuthResult
    {
        $data = RegisterData::fromArray($data);

        if ($this->adminRepository->emailExists($data->email)) {
            throw ValidationException::withMessages([
                'email' => ['邮箱已注册'],
            ]);
        }

        $admin = $this->adminRepository->create($data->toArray());

        event(new AdminRegistered($admin));

        $token = $this->jwt->fromUser($admin);

        return new AuthResult(
            admin: $admin,
            token: $token,
            expiresIn: config('jwt.ttl') * 60,
        );
    }

    public function login(array $data): AuthResult
    {
        $data = LoginData::fromArray($data);

        $admin = $this->adminRepository->findByEmail($data->email);

        if (! $admin || ! Hash::check($data->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['邮箱或密码错误'],
            ]);
        }

        $token = $this->jwt->fromUser($admin);

        return new AuthResult(
            admin: $admin,
            token: $token,
            expiresIn: config('jwt.ttl') * 60,
        );
    }

    public function logout(Admin $admin): void
    {
        try {
            $this->jwt->invalidate();
        } catch (Throwable) {
            // Token missing or already invalid — safe no-op.
        }
    }

    public function refresh(Admin $admin): string
    {
        return $this->jwt->fromUser($admin);
    }
}

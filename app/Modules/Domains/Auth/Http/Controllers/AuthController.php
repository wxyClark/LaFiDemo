<?php

declare(strict_types=1);

namespace App\Modules\Domains\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Domains\Auth\Http\Requests\LoginRequest;
use App\Modules\Domains\Auth\Http\Requests\RegisterRequest;
use App\Modules\Domains\Auth\Http\Resources\AdminResource;
use App\Modules\Domains\Auth\Services\AuthService;
use App\Modules\Infrastructure\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created([
            'user' => new AdminResource($result->admin),
            'token' => $result->token,
            'token_type' => 'bearer',
            'expires_in' => $result->expiresIn,
        ], '注册成功');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->success([
            'user' => new AdminResource($result->admin),
            'token' => $result->token,
            'token_type' => 'bearer',
            'expires_in' => $result->expiresIn,
        ], '登录成功');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, '退出成功');
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $this->authService->refresh($request->user());

        return $this->success([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], 'Token 已刷新');
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $request->user();

        return $this->success((new AdminResource($admin))->additional([
            'roles' => $admin->getRoleNames(),
        ]));
    }
}

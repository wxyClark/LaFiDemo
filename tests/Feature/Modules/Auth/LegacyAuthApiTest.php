<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

/**
 * 遗留业务接口（routes/laf/*）：
 * 仓储实现此前从未绑定，JWT_SECRET 也无配置项，这两组路由全部 500。
 * 本文件把"注册 -> 登录 -> 取当前用户 -> 客户增查"整条链路钉住，
 * 防止这类接口再次退化成"路由存在但不可用"。
 */
test('the legacy auth api registers, logs in and manages customers', function (): void {
    /** @var TestCase $this */
    $register = $this->postJson('/api/register', [
        'name' => 'Operator',
        'email' => 'operator@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $register->assertStatus(201)->assertJsonPath('success', true);

    $token = $register->json('data.token');
    expect($token)->not->toBeEmpty();

    // 令牌可换取当前用户
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/me')
        ->assertStatus(200)
        ->assertJsonPath('data.email', 'operator@example.com');

    // 受保护的客户接口在同一令牌下可用
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/users', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])
        ->assertStatus(201)
        ->assertJsonPath('success', true);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/users')
        ->assertStatus(200);
});

test('the legacy auth api rejects credentials that do not match', function (): void {
    /** @var TestCase $this */
    $this->postJson('/api/register', [
        'name' => 'Operator',
        'email' => 'operator@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertStatus(201);

    // 遗留实现的约定：凭证错误走 ValidationException（422），而非 401。
    $login = $this->postJson('/api/login', [
        'email' => 'operator@example.com',
        'password' => 'wrong-password',
    ]);

    $login->assertStatus(422);

    // 遗留实现直接抛 Laravel ValidationException，因此 422 用的是框架信封
    // （message/errors），而非 ApiResponse trait 的 success 信封。
    expect($login->json('success'))->toBeNull()
        ->and($login->json('errors.email'))->not->toBeEmpty();
});

<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Models\User;

it('bloqueia visitantes de ver stats do painel', function (): void {
    $this->getJson('/api/admin/dashboard/stats')->assertUnauthorized();
});

it('proíbe stats do painel sem permissão de cards', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo([
        PermissionName::DashboardSidebar->value,
        PermissionName::DashboardView->value,
    ]);

    $this->actingAs($user)
        ->getJson('/api/admin/dashboard/stats')
        ->assertForbidden();
});

it('retorna totais do painel para quem tem dashboard.cards', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->count(2)->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/admin/dashboard/stats')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'users',
                'roles',
                'permissions',
                'audits_today',
            ],
        ]);

    expect($response->json('data.users'))->toBe(3)
        ->and($response->json('data.roles'))->toBeGreaterThanOrEqual(2)
        ->and($response->json('data.permissions'))->toBe(count(PermissionName::values()))
        ->and($response->json('data.audits_today'))->toBeInt()
        ->and($response->json('data.audits_today'))->toBeGreaterThanOrEqual(0);
});

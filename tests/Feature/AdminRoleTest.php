<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

it('blocks guests from listing roles', function (): void {
    $this->getJson('/api/admin/roles')->assertUnauthorized();
});

it('forbids listing roles without permission', function (): void {
    $user = User::factory()->withUserRole()->create();

    $this->actingAs($user)
        ->getJson('/api/admin/roles')
        ->assertForbidden();
});

it('lists roles for an admin', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/roles')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'label', 'permissions', 'users_count'],
            ],
            'links',
            'meta',
        ]);
});

it('creates a role with permissions', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/roles', [
            'name' => 'editor',
            'permissions' => [
                PermissionName::DashboardSidebar->value,
                PermissionName::DashboardView->value,
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'editor')
        ->assertJsonPath('data.permissions.0', PermissionName::DashboardSidebar->value);

    $role = Role::findByName('editor', 'web');
    expect($role->hasPermissionTo(PermissionName::DashboardView->value))->toBeTrue();
});

it('shows a role', function (): void {
    $admin = User::factory()->admin()->create();
    $role = Role::findByName(RoleName::User->value, 'web');

    $this->actingAs($admin)
        ->getJson("/api/admin/roles/{$role->id}")
        ->assertOk()
        ->assertJsonPath('data.name', RoleName::User->value)
        ->assertJsonPath('data.label', 'Usuário');
});

it('updates a role and syncs permissions', function (): void {
    $admin = User::factory()->admin()->create();
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->putJson("/api/admin/roles/{$role->id}", [
            'name' => 'editor-updated',
            'permissions' => [
                PermissionName::UsersView->value,
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'editor-updated');

    expect($role->fresh()->hasPermissionTo(PermissionName::UsersView->value))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo(PermissionName::DashboardView->value))->toBeFalse();
});

it('forbids deleting the admin role', function (): void {
    $admin = User::factory()->admin()->create();
    $role = Role::findByName(RoleName::Admin->value, 'web');

    $this->actingAs($admin)
        ->deleteJson("/api/admin/roles/{$role->id}")
        ->assertForbidden();
});

it('forbids deleting a role that still has users', function (): void {
    $admin = User::factory()->admin()->create();
    $role = Role::findByName(RoleName::User->value, 'web');
    User::factory()->withUserRole()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/roles/{$role->id}")
        ->assertForbidden();
});

it('deletes a role without users', function (): void {
    $admin = User::factory()->admin()->create();
    $role = Role::create(['name' => 'temp-role', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->deleteJson("/api/admin/roles/{$role->id}")
        ->assertNoContent();

    expect(Role::query()->where('name', 'temp-role')->exists())->toBeFalse();
});

it('returns the permission catalog for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/permissions/catalog')
        ->assertOk()
        ->assertJsonPath('data.0.key', 'menu')
        ->assertJsonPath('data.0.modules.0.key', 'dashboard')
        ->assertJsonPath('data.1.key', 'settings')
        ->assertJsonPath('data.1.modules.0.key', 'users')
        ->assertJsonPath('data.1.modules.1.key', 'roles')
        ->assertJsonPath('data.1.modules.2.key', 'audit');
});

it('forbids the permission catalog without create or update permission', function (): void {
    $user = User::factory()->withUserRole()->create();

    $this->actingAs($user)
        ->getJson('/api/admin/permissions/catalog')
        ->assertForbidden();
});

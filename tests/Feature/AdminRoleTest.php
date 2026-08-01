<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

it('bloqueia visitantes de listar perfis', function (): void {
    $this->getJson('/api/admin/roles')->assertUnauthorized();
});

it('proíbe listar perfis sem permissão', function (): void {
    $user = User::factory()->withUserRole()->create();

    $this->actingAs($user)
        ->getJson('/api/admin/roles')
        ->assertForbidden();
});

it('lista perfis para um administrador', function (): void {
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

it('cria um perfil com permissões', function (): void {
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

it('exibe um perfil', function (): void {
    $admin = User::factory()->admin()->create();
    $role = Role::findByName(RoleName::User->value, 'web');

    $this->actingAs($admin)
        ->getJson("/api/admin/roles/{$role->id}")
        ->assertOk()
        ->assertJsonPath('data.name', RoleName::User->value)
        ->assertJsonPath('data.label', RoleName::User->value);
});

it('atualiza um perfil e sincroniza permissões', function (): void {
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

it('proíbe excluir o perfil admin', function (): void {
    $admin = User::factory()->admin()->create();
    $role = Role::findByName(RoleName::Admin->value, 'web');

    $this->actingAs($admin)
        ->deleteJson("/api/admin/roles/{$role->id}")
        ->assertForbidden();
});

it('proíbe excluir perfil que ainda tem usuários', function (): void {
    $admin = User::factory()->admin()->create();
    $role = Role::findByName(RoleName::User->value, 'web');
    User::factory()->withUserRole()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/roles/{$role->id}")
        ->assertForbidden();
});

it('exclui um perfil sem usuários', function (): void {
    $admin = User::factory()->admin()->create();
    $role = Role::create(['name' => 'temp-role', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->deleteJson("/api/admin/roles/{$role->id}")
        ->assertNoContent();

    expect(Role::query()->where('name', 'temp-role')->exists())->toBeFalse();
});

it('retorna o catálogo de permissões para administradores', function (): void {
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

it('proíbe o catálogo sem permissão de criar ou atualizar', function (): void {
    $user = User::factory()->withUserRole()->create();

    $this->actingAs($user)
        ->getJson('/api/admin/permissions/catalog')
        ->assertForbidden();
});

it('filtra perfis pela busca', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/roles', [
            'name' => 'filterable-role',
            'permissions' => [PermissionName::DashboardView->value],
        ])
        ->assertCreated();

    $this->actingAs($admin)
        ->getJson('/api/admin/roles?q=filterable')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'filterable-role');
});

it('filtra perfis pelo nome exibido', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/roles?q=administrador')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', RoleName::Admin->value)
        ->assertJsonPath('data.0.label', RoleName::Admin->value);
});

it('ordena perfis por nome descendente', function (): void {
    $admin = User::factory()->admin()->create();

    $names = $this->actingAs($admin)
        ->getJson('/api/admin/roles?sort=name&direction=desc&per_page=50')
        ->assertOk()
        ->json('data.*.name');

    expect($names)->toBe(collect($names)->sortDesc()->values()->all());
});

it('rejeita colunas de ordenação inválidas em perfis', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/roles?sort=users_count')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sort']);
});

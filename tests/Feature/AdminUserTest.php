<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;

it('blocks guests from listing users', function (): void {
    $this->getJson('/api/admin/users')->assertUnauthorized();
});

it('forbids listing users without permission', function (): void {
    $user = User::factory()->withUserRole()->create();

    $this->actingAs($user)
        ->getJson('/api/admin/users')
        ->assertForbidden();
});

it('lists users for an admin', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'email', 'roles', 'permissions'],
            ],
            'links',
            'meta',
        ]);
});

it('paginates users with a custom per_page', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->count(10)->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/users?per_page=5')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.per_page', 5);
});

it('creates a user', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/users', [
            'name' => 'New User',
            'email' => 'new@spa-base.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleName::User->value],
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'new@spa-base.test')
        ->assertJsonPath('data.roles.0', RoleName::User->value)
        ->assertJsonMissingPath('data.password');

    $this->assertDatabaseHas('users', [
        'email' => 'new@spa-base.test',
        'name' => 'New User',
    ]);

    expect(User::query()->where('email', 'new@spa-base.test')->first()?->hasRole(RoleName::User))->toBeTrue();
});

it('forbids creating a user without permission', function (): void {
    $user = User::factory()->withUserRole()->create();

    $this->actingAs($user)
        ->postJson('/api/admin/users', [
            'name' => 'Blocked',
            'email' => 'blocked@spa-base.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleName::User->value],
        ])
        ->assertForbidden();
});

it('shows a user', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->getJson("/api/admin/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('forbids showing a user without permission', function (): void {
    $actor = User::factory()->withUserRole()->create();
    $user = User::factory()->create();

    $this->actingAs($actor)
        ->getJson("/api/admin/users/{$user->id}")
        ->assertForbidden();
});

it('updates a user', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withUserRole()->create([
        'email' => 'old@spa-base.test',
    ]);

    $this->actingAs($admin)
        ->putJson("/api/admin/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@spa-base.test',
            'roles' => [RoleName::Admin->value],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.email', 'updated@spa-base.test')
        ->assertJsonPath('data.roles.0', RoleName::Admin->value);

    expect($user->fresh()?->hasRole(RoleName::Admin))->toBeTrue();
});

it('updates a user without requiring a password', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withUserRole()->create([
        'email' => 'keep-password@spa-base.test',
        'password' => 'password',
    ]);

    $originalHash = $user->password;

    $this->actingAs($admin)
        ->putJson("/api/admin/users/{$user->id}", [
            'name' => 'Name Only',
            'email' => 'keep-password@spa-base.test',
            'password' => '',
            'password_confirmation' => '',
            'roles' => [RoleName::User->value],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Name Only');

    expect($user->fresh()?->password)->toBe($originalHash);
});

it('deletes another user', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$user->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

it('prevents self-deletion', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$admin->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
    ]);
});

it('returns roles and permissions for the authenticated user', function (): void {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.roles.0', RoleName::Admin->value);

    $permissions = $response->json('data.permissions');

    expect($permissions)->toEqualCanonicalizing(PermissionName::values());
});

it('sorts users by email descending', function (): void {
    $admin = User::factory()->admin()->create([
        'name' => 'Admin Sort',
        'email' => 'admin-sort@spa-base.test',
    ]);
    User::factory()->create([
        'name' => 'Alpha',
        'email' => 'alpha@spa-base.test',
    ]);
    User::factory()->create([
        'name' => 'Zulu',
        'email' => 'zulu@spa-base.test',
    ]);

    $emails = $this->actingAs($admin)
        ->getJson('/api/admin/users?sort=email&direction=desc&per_page=50')
        ->assertOk()
        ->json('data.*.email');

    expect($emails)->toBe(collect($emails)->sortDesc()->values()->all());
});

it('filters users by search query', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->create([
        'name' => 'Unique Filter Name',
        'email' => 'unique-filter@spa-base.test',
    ]);
    User::factory()->create([
        'name' => 'Other Person',
        'email' => 'other-person@spa-base.test',
    ]);

    $this->actingAs($admin)
        ->getJson('/api/admin/users?q=Unique%20Filter')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'unique-filter@spa-base.test');
});

it('filters users by role', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->withUserRole()->create([
        'email' => 'only-user-role@spa-base.test',
    ]);

    $emails = $this->actingAs($admin)
        ->getJson('/api/admin/users?role=user&per_page=50')
        ->assertOk()
        ->json('data.*.email');

    expect($emails)->toContain('only-user-role@spa-base.test')
        ->and($emails)->not->toContain($admin->email);
});

it('rejects invalid user sort columns', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/users?sort=password')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sort']);
});

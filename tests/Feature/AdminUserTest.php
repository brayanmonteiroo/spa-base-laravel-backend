<?php

declare(strict_types=1);

use App\Models\User;

it('blocks guests from listing users', function (): void {
    $this->getJson('/api/admin/users')->assertUnauthorized();
});

it('lists users for an authenticated user', function (): void {
    $admin = User::factory()->create();
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'name', 'email'],
            ],
            'links',
            'meta',
        ]);
});

it('creates a user', function (): void {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/users', [
            'name' => 'New User',
            'email' => 'new@spa-base.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'new@spa-base.test')
        ->assertJsonMissingPath('data.password');

    $this->assertDatabaseHas('users', [
        'email' => 'new@spa-base.test',
        'name' => 'New User',
    ]);
});

it('shows a user', function (): void {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->getJson("/api/admin/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('updates a user', function (): void {
    $admin = User::factory()->create();
    $user = User::factory()->create([
        'email' => 'old@spa-base.test',
    ]);

    $this->actingAs($admin)
        ->putJson("/api/admin/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@spa-base.test',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.email', 'updated@spa-base.test');
});

it('deletes another user', function (): void {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$user->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

it('prevents self-deletion', function (): void {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/api/admin/users/{$admin->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
    ]);
});

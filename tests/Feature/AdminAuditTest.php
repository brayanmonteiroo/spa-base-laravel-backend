<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\User;
use OwenIt\Auditing\Models\Audit;

it('blocks guests from listing audits', function (): void {
    $this->getJson('/api/admin/audits')->assertUnauthorized();
});

it('forbids listing audits without permission', function (): void {
    $user = User::factory()->withUserRole()->create();

    $this->actingAs($user)
        ->getJson('/api/admin/audits')
        ->assertForbidden();
});

it('lists audits for an admin', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->putJson("/api/admin/users/{$admin->id}", [
            'name' => 'Admin Updated',
            'email' => $admin->email,
            'roles' => [RoleName::Admin->value],
        ])
        ->assertOk();

    expect(Audit::query()->count())->toBeGreaterThan(0);

    $this->actingAs($admin)
        ->getJson('/api/admin/audits')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'event',
                    'event_label',
                    'auditable_type',
                    'auditable_label',
                    'auditable_id',
                    'user',
                    'ip_address',
                    'url',
                    'old_values',
                    'new_values',
                    'created_at',
                ],
            ],
            'links',
            'meta',
        ]);

    $first = $this->actingAs($admin)
        ->getJson('/api/admin/audits')
        ->json('data.0');

    expect($first['event_label'])->toBeIn(['Criado', 'Atualizado', 'Perfis atualizados'])
        ->and($first['auditable_label'])->toBeIn(['Usuário', 'Perfil']);
});

it('records an audit when a user is created', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/users', [
            'name' => 'Audited User',
            'email' => 'audited@spa-base.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleName::User->value],
        ])
        ->assertCreated();

    $created = User::query()->where('email', 'audited@spa-base.test')->first();

    expect($created)->not->toBeNull()
        ->and(
            Audit::query()
                ->where('auditable_type', User::class)
                ->where('auditable_id', $created?->id)
                ->where('event', 'created')
                ->exists()
        )->toBeTrue()
        ->and(
            Audit::query()
                ->where('auditable_type', User::class)
                ->where('auditable_id', $created?->id)
                ->where('event', 'roles_updated')
                ->exists()
        )->toBeTrue();
});

it('records an audit when user roles change', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->withUserRole()->create([
        'email' => 'role-change@spa-base.test',
    ]);

    $this->actingAs($admin)
        ->putJson("/api/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => [RoleName::Admin->value],
        ])
        ->assertOk();

    $audit = Audit::query()
        ->where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'roles_updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit?->old_values['roles'] ?? null)->toContain(RoleName::User->value)
        ->and($audit?->new_values['roles'] ?? null)->toContain(RoleName::Admin->value);
});

it('filters audits by event', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/admin/users', [
            'name' => 'Audit Filter User',
            'email' => 'audit-filter@spa-base.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleName::User->value],
        ])
        ->assertCreated();

    $events = $this->actingAs($admin)
        ->getJson('/api/admin/audits?event=created&per_page=50')
        ->assertOk()
        ->json('data.*.event');

    expect($events)->not->toBeEmpty()
        ->and(collect($events)->every(fn (string $event): bool => $event === 'created'))->toBeTrue();
});

it('sorts audits by created_at ascending', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->putJson("/api/admin/users/{$admin->id}", [
            'name' => 'Admin Audit Sort',
            'email' => $admin->email,
            'roles' => [RoleName::Admin->value],
        ])
        ->assertOk();

    $dates = $this->actingAs($admin)
        ->getJson('/api/admin/audits?sort=created_at&direction=asc&per_page=50')
        ->assertOk()
        ->json('data.*.created_at');

    expect($dates)->toBe(collect($dates)->sort()->values()->all());
});

it('rejects invalid audit sort columns', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson('/api/admin/audits?sort=ip_address')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sort']);
});

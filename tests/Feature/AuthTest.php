<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Notification;

function spaHeaders(): array
{
    return [
        'Origin' => 'http://localhost:9020',
        'Referer' => 'http://localhost:9020',
    ];
}

it('returns the authenticated user', function (): void {
    $user = User::factory()->withUserRole()->create();

    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'roles',
                'permissions',
            ],
        ]);
});

it('rejects guests from the authenticated user endpoint', function (): void {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('logs in with valid credentials and regenerates the session', function (): void {
    $user = User::factory()->create([
        'email' => 'admin@spa-base.test',
        'password' => 'password',
    ]);

    $this->withoutMiddleware(ValidateCsrfToken::class)
        ->withHeaders(spaHeaders())
        ->postJson('/api/login', [
            'email' => 'admin@spa-base.test',
            'password' => 'password',
        ])
        ->assertOk()
        ->assertJsonPath('user.email', $user->email);

    $this->assertAuthenticated('web');
});

it('rejects invalid login credentials', function (): void {
    User::factory()->create([
        'email' => 'admin@spa-base.test',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email' => 'admin@spa-base.test',
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest('web');
});

it('logs out an authenticated user', function (): void {
    $user = User::factory()->create();

    $this->withoutMiddleware(ValidateCsrfToken::class)
        ->actingAs($user)
        ->withHeaders(spaHeaders())
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out.');

    $this->assertGuest('web');
});

it('sends a password reset link', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'admin@spa-base.test',
    ]);

    $this->postJson('/api/forgot-password', [
        'email' => 'admin@spa-base.test',
    ])
        ->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('resets the password with a valid token', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'admin@spa-base.test',
        'password' => 'password',
    ]);

    $this->postJson('/api/forgot-password', [
        'email' => $user->email,
    ])->assertOk();

    $token = null;

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        }
    );

    $this->postJson('/api/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    expect(auth()->attempt([
        'email' => $user->email,
        'password' => 'new-password',
    ]))->toBeTrue();
});

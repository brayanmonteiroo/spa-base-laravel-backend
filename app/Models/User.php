<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Events\AuditCustom;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use AuditableTrait, HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected array $auditExclude = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Sync Spatie roles and record an audit when the set changes.
     *
     * @param  list<string>  $roleNames
     */
    public function syncAuditedRoles(array $roleNames): void
    {
        $oldRoles = $this->exists
            ? $this->getRoleNames()->sort()->values()->all()
            : [];

        $this->syncRoles($roleNames);

        $newRoles = $this->getRoleNames()->sort()->values()->all();

        if ($oldRoles === $newRoles) {
            return;
        }

        $this->auditCustomOld = ['roles' => $oldRoles];
        $this->auditCustomNew = ['roles' => $newRoles];
        $this->auditEvent = 'roles_updated';
        $this->isCustomEvent = true;

        Event::dispatch(new AuditCustom($this));

        $this->auditCustomOld = [];
        $this->auditCustomNew = [];
        $this->isCustomEvent = false;
        $this->auditEvent = null;
    }
}

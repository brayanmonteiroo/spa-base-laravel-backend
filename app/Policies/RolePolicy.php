<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Spatie\Permission\Models\Role;

final class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::RolesView->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(PermissionName::RolesShow->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::RolesCreate->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(PermissionName::RolesUpdate->value);
    }

    public function delete(User $user, Role $role): bool
    {
        if (! $user->can(PermissionName::RolesDelete->value)) {
            return false;
        }

        if ($role->name === RoleName::Admin->value) {
            return false;
        }

        return $role->users()->count() === 0;
    }
}

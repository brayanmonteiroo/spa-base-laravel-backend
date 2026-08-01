<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

final class RolePolicy
{
    /**
     * Verifica se o usuário pode visualizar todos os perfis.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::RolesView->value);
    }

    /**
     * Verifica se o usuário pode visualizar um perfil específico.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can(PermissionName::RolesShow->value);
    }

    /**
     * Verifica se o usuário pode criar um novo perfil.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::RolesCreate->value);
    }

    /**
     * Verifica se o usuário pode atualizar um perfil.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can(PermissionName::RolesUpdate->value);
    }

    /**
     * Verifica se o usuário pode deletar um perfil.
     */
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

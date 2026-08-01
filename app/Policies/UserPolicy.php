<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

final class UserPolicy
{
    /**
     * Verifica se o usuário pode visualizar todos os usuários.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::UsersView->value);
    }

    /**
     * Verifica se o usuário pode visualizar um usuário específico.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersShow->value);
    }

    /**
     * Verifica se o usuário pode criar um novo usuário.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionName::UsersCreate->value);
    }

    /**
     * Verifica se o usuário pode atualizar um usuário.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersUpdate->value);
    }

    /**
     * Verifica se o usuário pode deletar um usuário.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersDelete->value)
            && $user->id !== $model->id;
    }
}

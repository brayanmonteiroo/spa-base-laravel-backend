<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::UsersView->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersShow->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::UsersCreate->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersUpdate->value);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can(PermissionName::UsersDelete->value)
            && $user->id !== $model->id;
    }
}

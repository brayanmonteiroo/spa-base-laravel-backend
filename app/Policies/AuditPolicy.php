<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use OwenIt\Auditing\Models\Audit;

final class AuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::AuditView->value);
    }

    public function view(User $user, Audit $audit): bool
    {
        return $user->can(PermissionName::AuditView->value);
    }
}

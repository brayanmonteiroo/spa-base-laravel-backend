<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use OwenIt\Auditing\Models\Audit;

final class AuditPolicy
{
    /**
     * Verifica se o usuário pode visualizar todas as auditorias.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::AuditView->value);
    }

    /**
     * Verifica se o usuário pode visualizar uma auditoria específica.
     */
    public function view(User $user, Audit $audit): bool
    {
        return $user->can(PermissionName::AuditView->value);
    }
}

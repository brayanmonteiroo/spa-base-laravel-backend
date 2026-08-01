<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Role;
use App\Models\User;

final class AuditLabels
{
    public static function eventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Criado',
            'updated' => 'Atualizado',
            'deleted' => 'Excluído',
            'restored' => 'Restaurado',
            'roles_updated' => 'Perfis atualizados',
            'sync' => 'Vínculos atualizados',
            default => ucfirst($event),
        };
    }

    public static function auditableLabel(?string $type): string
    {
        return match ($type) {
            User::class => 'Usuário',
            Role::class => 'Perfil',
            default => class_basename((string) $type) ?: 'Registro',
        };
    }
}

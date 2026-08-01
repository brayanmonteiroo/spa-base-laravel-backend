<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::User => 'Usuário',
        };
    }

    public static function labelFor(string $name): string
    {
        $role = self::tryFrom($name);

        if ($role instanceof self) {
            return $role->label();
        }

        return ucfirst($name);
    }
}

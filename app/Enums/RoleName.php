<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'Administrador';
    case User = 'Usuário';
}

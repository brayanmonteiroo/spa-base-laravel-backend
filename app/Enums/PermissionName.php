<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionName: string
{
    case DashboardSidebar = 'dashboard.sidebar';
    case DashboardView = 'dashboard.view';
    case DashboardCards = 'dashboard.cards';

    case UsersSidebar = 'users.sidebar';
    case UsersView = 'users.view';
    case UsersShow = 'users.show';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';

    case RolesSidebar = 'roles.sidebar';
    case RolesView = 'roles.view';
    case RolesShow = 'roles.show';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';

    case AuditSidebar = 'audit.sidebar';
    case AuditView = 'audit.view';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PermissionName;

final class PermissionCatalog
{
    /**
     * Ordered like the admin sidebar. Append new modules at the end.
     *
     * @return list<array{key: string, label: string, permissions: list<array{name: string, label: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'Painel',
                'permissions' => [
                    ['name' => PermissionName::DashboardSidebar->value, 'label' => 'Menu'],
                    ['name' => PermissionName::DashboardView->value, 'label' => 'Visualizar'],
                ],
            ],
            [
                'key' => 'users',
                'label' => 'Usuários',
                'permissions' => [
                    ['name' => PermissionName::UsersSidebar->value, 'label' => 'Menu'],
                    ['name' => PermissionName::UsersView->value, 'label' => 'Listar'],
                    ['name' => PermissionName::UsersShow->value, 'label' => 'Visualizar'],
                    ['name' => PermissionName::UsersCreate->value, 'label' => 'Cadastrar'],
                    ['name' => PermissionName::UsersUpdate->value, 'label' => 'Editar'],
                    ['name' => PermissionName::UsersDelete->value, 'label' => 'Excluir'],
                ],
            ],
            [
                'key' => 'roles',
                'label' => 'Perfis',
                'permissions' => [
                    ['name' => PermissionName::RolesSidebar->value, 'label' => 'Menu'],
                    ['name' => PermissionName::RolesView->value, 'label' => 'Listar'],
                    ['name' => PermissionName::RolesShow->value, 'label' => 'Visualizar'],
                    ['name' => PermissionName::RolesCreate->value, 'label' => 'Cadastrar'],
                    ['name' => PermissionName::RolesUpdate->value, 'label' => 'Editar'],
                    ['name' => PermissionName::RolesDelete->value, 'label' => 'Excluir'],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        foreach (self::groups() as $group) {
            foreach ($group['permissions'] as $permission) {
                $names[] = $permission['name'];
            }
        }

        return $names;
    }
}

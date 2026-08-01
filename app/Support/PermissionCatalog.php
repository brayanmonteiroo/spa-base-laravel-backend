<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PermissionName;

final class PermissionCatalog
{
    /**
     * Seções alinhadas com o sidebar. As etiquetas de sección son organizativas solo.
     *
     * @return list<array{key: string, label: string, modules: list<array{key: string, label: string, permissions: list<array{name: string, label: string}>}>}>
     */
    public static function sections(): array
    {
        return [
            [
                'key' => 'menu',
                'label' => 'Menu',
                'modules' => [
                    [
                        'key' => 'dashboard',
                        'label' => 'Painel',
                        'permissions' => [
                            ['name' => PermissionName::DashboardSidebar->value, 'label' => 'Menu'],
                            ['name' => PermissionName::DashboardView->value, 'label' => 'Visualizar'],
                            ['name' => PermissionName::DashboardCards->value, 'label' => 'Cards'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'settings',
                'label' => 'Configurações',
                'modules' => [
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
                    [
                        'key' => 'audit',
                        'label' => 'Auditoria',
                        'permissions' => [
                            ['name' => PermissionName::AuditSidebar->value, 'label' => 'Menu'],
                            ['name' => PermissionName::AuditView->value, 'label' => 'Listar'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Grupos planos.
     *
     * @deprecated Use sections(); kept for callers that still expect flat groups.
     *
     * @return list<array{key: string, label: string, permissions: list<array{name: string, label: string}>}>
     */
    public static function groups(): array
    {
        $groups = [];

        foreach (self::sections() as $section) {
            foreach ($section['modules'] as $module) {
                $groups[] = $module;
            }
        }

        return $groups;
    }

    /**
     * Nomes das permissões.
     *
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        foreach (self::sections() as $section) {
            foreach ($section['modules'] as $module) {
                foreach ($module['permissions'] as $permission) {
                    $names[] = $permission['name'];
                }
            }
        }

        return $names;
    }
}

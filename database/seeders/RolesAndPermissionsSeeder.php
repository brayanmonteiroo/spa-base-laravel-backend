<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Semeia os perfis e as permissões.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionName::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::findOrCreate(RoleName::Admin->value, 'web');
        $admin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

        $user = Role::findOrCreate(RoleName::User->value, 'web');
        $user->syncPermissions([
            PermissionName::DashboardSidebar->value,
            PermissionName::DashboardView->value,
            PermissionName::DashboardCards->value,
        ]);
    }
}

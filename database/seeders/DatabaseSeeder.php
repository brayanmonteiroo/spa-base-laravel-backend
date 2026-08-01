<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Semeia o banco de dados da aplicação.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@spa-base.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([RoleName::Admin->value]);

        User::factory()
            ->count(34)
            ->create()
            ->each(function (User $user): void {
                $user->assignRole(RoleName::User->value);
            });
    }
}

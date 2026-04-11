<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@bunyan.test',
                'role_enum' => UserRole::ADMIN,
                'role_slug' => 'admin',
            ],
            [
                'name' => 'Customer User',
                'email' => 'customer@bunyan.test',
                'role_enum' => UserRole::CUSTOMER,
                'role_slug' => 'customer',
            ],
            [
                'name' => 'Contractor User',
                'email' => 'contractor@bunyan.test',
                'role_enum' => UserRole::CONTRACTOR,
                'role_slug' => 'contractor',
            ],
            [
                'name' => 'Supervising Architect',
                'email' => 'architect@bunyan.test',
                'role_enum' => UserRole::SUPERVISING_ARCHITECT,
                'role_slug' => 'supervising_architect',
            ],
            [
                'name' => 'Field Engineer',
                'email' => 'engineer@bunyan.test',
                'role_enum' => UserRole::FIELD_ENGINEER,
                'role_slug' => 'field_engineer',
            ],
        ];

        foreach ($users as $userData) {
            $roleEnum = $userData['role_enum'];
            $roleSlug = $userData['role_slug'];

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'is_active' => true,
                ],
            );

            // Set the enum column directly (not via mass assignment — privilege escalation guard)
            $user->role = $roleEnum;
            $user->save();

            // Attach role pivot record (idempotent)
            $role = Role::where('name', $roleSlug)->first();

            if ($role !== null) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Default permission matrix per spec
        $matrix = [
            'customer' => [
                'projects.view', 'projects.create',
                'phases.view',
                'tasks.view',
                'reports.view',
                'orders.view', 'orders.create',
                'products.view',
                'payments.view',
            ],
            'contractor' => [
                'projects.view', 'projects.update',
                'phases.view', 'phases.create', 'phases.update',
                'tasks.view', 'tasks.create', 'tasks.update',
                'reports.view',
                'orders.view',
                'products.view',
                'payments.view',
            ],
            'supervising_architect' => [
                'projects.view', 'projects.update',
                'phases.view', 'phases.create', 'phases.update',
                'tasks.view', 'tasks.create', 'tasks.update',
                'reports.view', 'reports.approve',
                'users.view',
            ],
            'field_engineer' => [
                'projects.view',
                'phases.view',
                'tasks.view', 'tasks.create', 'tasks.update',
                'reports.view', 'reports.create',
            ],
            'admin' => [
                'projects.view', 'projects.create', 'projects.update', 'projects.delete',
                'phases.view', 'phases.create', 'phases.update', 'phases.delete',
                'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
                'reports.view', 'reports.create', 'reports.approve',
                'users.view', 'users.manage', 'users.deactivate',
                'orders.view', 'orders.create', 'orders.manage',
                'products.view', 'products.create', 'products.update', 'products.delete',
                'payments.view', 'payments.process', 'payments.refund',
                'settings.view', 'settings.manage',
                'roles.view', 'roles.manage',
            ],
        ];

        foreach ($matrix as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}

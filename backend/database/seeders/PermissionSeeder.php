<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // projects (4)
            ['name' => 'projects.view', 'display_name' => 'View Projects', 'group' => 'projects', 'description' => 'List and view projects'],
            ['name' => 'projects.create', 'display_name' => 'Create Projects', 'group' => 'projects', 'description' => 'Create new construction projects'],
            ['name' => 'projects.update', 'display_name' => 'Update Projects', 'group' => 'projects', 'description' => 'Update project details and status'],
            ['name' => 'projects.delete', 'display_name' => 'Delete Projects', 'group' => 'projects', 'description' => 'Archive or delete projects'],

            // phases (4)
            ['name' => 'phases.view', 'display_name' => 'View Phases', 'group' => 'phases', 'description' => 'View project phases'],
            ['name' => 'phases.create', 'display_name' => 'Create Phases', 'group' => 'phases', 'description' => 'Create project phases'],
            ['name' => 'phases.update', 'display_name' => 'Update Phases', 'group' => 'phases', 'description' => 'Update project phases'],
            ['name' => 'phases.delete', 'display_name' => 'Delete Phases', 'group' => 'phases', 'description' => 'Delete project phases'],

            // tasks (4)
            ['name' => 'tasks.view', 'display_name' => 'View Tasks', 'group' => 'tasks', 'description' => 'View project tasks'],
            ['name' => 'tasks.create', 'display_name' => 'Create Tasks', 'group' => 'tasks', 'description' => 'Create project tasks'],
            ['name' => 'tasks.update', 'display_name' => 'Update Tasks', 'group' => 'tasks', 'description' => 'Update project tasks'],
            ['name' => 'tasks.delete', 'display_name' => 'Delete Tasks', 'group' => 'tasks', 'description' => 'Delete project tasks'],

            // reports (3)
            ['name' => 'reports.view', 'display_name' => 'View Reports', 'group' => 'reports', 'description' => 'View field reports'],
            ['name' => 'reports.create', 'display_name' => 'Create Reports', 'group' => 'reports', 'description' => 'Submit field reports'],
            ['name' => 'reports.approve', 'display_name' => 'Approve Reports', 'group' => 'reports', 'description' => 'Approve or reject field reports'],

            // users (3)
            ['name' => 'users.view', 'display_name' => 'View Users', 'group' => 'users', 'description' => 'List and view user profiles'],
            ['name' => 'users.manage', 'display_name' => 'Manage Users', 'group' => 'users', 'description' => 'Manage user accounts and roles'],
            ['name' => 'users.deactivate', 'display_name' => 'Deactivate Users', 'group' => 'users', 'description' => 'Deactivate user accounts'],

            // orders (3)
            ['name' => 'orders.view', 'display_name' => 'View Orders', 'group' => 'orders', 'description' => 'View e-commerce orders'],
            ['name' => 'orders.create', 'display_name' => 'Create Orders', 'group' => 'orders', 'description' => 'Place product orders'],
            ['name' => 'orders.manage', 'display_name' => 'Manage Orders', 'group' => 'orders', 'description' => 'Process and fulfil orders'],

            // products (4)
            ['name' => 'products.view', 'display_name' => 'View Products', 'group' => 'products', 'description' => 'Browse the building materials catalog'],
            ['name' => 'products.create', 'display_name' => 'Create Products', 'group' => 'products', 'description' => 'Add new products to the catalog'],
            ['name' => 'products.update', 'display_name' => 'Update Products', 'group' => 'products', 'description' => 'Update product information and pricing'],
            ['name' => 'products.delete', 'display_name' => 'Delete Products', 'group' => 'products', 'description' => 'Remove products from the catalog'],

            // payments (3)
            ['name' => 'payments.view', 'display_name' => 'View Payments', 'group' => 'payments', 'description' => 'View payment transactions'],
            ['name' => 'payments.process', 'display_name' => 'Process Payments', 'group' => 'payments', 'description' => 'Process payment transactions'],
            ['name' => 'payments.refund', 'display_name' => 'Refund Payments', 'group' => 'payments', 'description' => 'Process payment refunds'],

            // settings (2)
            ['name' => 'settings.view', 'display_name' => 'View Settings', 'group' => 'settings', 'description' => 'View platform configuration'],
            ['name' => 'settings.manage', 'display_name' => 'Manage Settings', 'group' => 'settings', 'description' => 'Update global workflow and platform settings'],

            // roles (2)
            ['name' => 'roles.view', 'display_name' => 'View Roles', 'group' => 'roles', 'description' => 'View role definitions and permissions'],
            ['name' => 'roles.manage', 'display_name' => 'Manage Roles', 'group' => 'roles', 'description' => 'Manage role-permission assignments'],
        ];

        foreach ($permissions as $data) {
            Permission::updateOrCreate(
                ['name' => $data['name']],
                $data,
            );
        }
    }
}

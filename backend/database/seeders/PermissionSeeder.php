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
            // users (5)
            ['name' => 'users.view', 'display_name' => 'View Users', 'group' => 'users', 'description' => 'List and view user profiles'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'group' => 'users', 'description' => 'Create new user accounts'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'group' => 'users', 'description' => 'Update user profiles and roles'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'group' => 'users', 'description' => 'Soft-delete user accounts'],
            ['name' => 'users.restore', 'display_name' => 'Restore Users', 'group' => 'users', 'description' => 'Restore soft-deleted user accounts'],

            // projects (5)
            ['name' => 'projects.view', 'display_name' => 'View Projects', 'group' => 'projects', 'description' => 'List and view projects'],
            ['name' => 'projects.create', 'display_name' => 'Create Projects', 'group' => 'projects', 'description' => 'Create new construction projects'],
            ['name' => 'projects.edit', 'display_name' => 'Edit Projects', 'group' => 'projects', 'description' => 'Update project details and status'],
            ['name' => 'projects.delete', 'display_name' => 'Delete Projects', 'group' => 'projects', 'description' => 'Archive or delete projects'],
            ['name' => 'projects.manage', 'display_name' => 'Manage Projects', 'group' => 'projects', 'description' => 'Assign contractors and architects to projects'],

            // reports (4)
            ['name' => 'reports.view', 'display_name' => 'View Reports', 'group' => 'reports', 'description' => 'View field reports'],
            ['name' => 'reports.create', 'display_name' => 'Create Reports', 'group' => 'reports', 'description' => 'Submit field reports'],
            ['name' => 'reports.edit', 'display_name' => 'Edit Reports', 'group' => 'reports', 'description' => 'Edit submitted reports'],
            ['name' => 'reports.delete', 'display_name' => 'Delete Reports', 'group' => 'reports', 'description' => 'Delete field reports'],

            // transactions (3)
            ['name' => 'transactions.view', 'display_name' => 'View Transactions', 'group' => 'transactions', 'description' => 'View payment and withdrawal transactions'],
            ['name' => 'transactions.create', 'display_name' => 'Create Transactions', 'group' => 'transactions', 'description' => 'Initiate payments and withdrawals'],
            ['name' => 'transactions.manage', 'display_name' => 'Manage Transactions', 'group' => 'transactions', 'description' => 'Approve and manage platform transactions'],

            // products (4)
            ['name' => 'products.view', 'display_name' => 'View Products', 'group' => 'products', 'description' => 'Browse the building materials catalog'],
            ['name' => 'products.create', 'display_name' => 'Create Products', 'group' => 'products', 'description' => 'Add new products to the catalog'],
            ['name' => 'products.edit', 'display_name' => 'Edit Products', 'group' => 'products', 'description' => 'Update product information and pricing'],
            ['name' => 'products.delete', 'display_name' => 'Delete Products', 'group' => 'products', 'description' => 'Remove products from the catalog'],

            // orders (3)
            ['name' => 'orders.view', 'display_name' => 'View Orders', 'group' => 'orders', 'description' => 'View e-commerce orders'],
            ['name' => 'orders.create', 'display_name' => 'Create Orders', 'group' => 'orders', 'description' => 'Place product orders'],
            ['name' => 'orders.manage', 'display_name' => 'Manage Orders', 'group' => 'orders', 'description' => 'Process and fulfil orders'],

            // settings (2)
            ['name' => 'settings.view', 'display_name' => 'View Settings', 'group' => 'settings', 'description' => 'View platform configuration'],
            ['name' => 'settings.manage', 'display_name' => 'Manage Settings', 'group' => 'settings', 'description' => 'Update global workflow and platform settings'],
        ];

        foreach ($permissions as $data) {
            Permission::updateOrCreate(
                ['name' => $data['name']],
                $data,
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'display_name_ar' => 'الإدارة',
                'description' => 'Full platform control and administration.',
            ],
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'display_name_ar' => 'العميل',
                'description' => 'Project tracking and payments.',
            ],
            [
                'name' => 'contractor',
                'display_name' => 'Contractor',
                'display_name_ar' => 'المقاول',
                'description' => 'Project execution, earnings, and withdrawals.',
            ],
            [
                'name' => 'supervising_architect',
                'display_name' => 'Supervising Architect',
                'display_name_ar' => 'المهندس المشرف',
                'description' => 'Project oversight and field engineer management.',
            ],
            [
                'name' => 'field_engineer',
                'display_name' => 'Field Engineer',
                'display_name_ar' => 'المهندس الميداني',
                'description' => 'Field reporting and status updates.',
            ],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(
                ['name' => $data['name']],
                $data,
            );
        }
    }
}

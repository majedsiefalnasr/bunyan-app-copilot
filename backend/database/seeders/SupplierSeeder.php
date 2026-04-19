<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SupplierProfile;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // Verified suppliers
        SupplierProfile::factory()
            ->count(10)
            ->verified()
            ->create();

        // Pending suppliers
        SupplierProfile::factory()
            ->count(5)
            ->pending()
            ->create();

        // Suspended suppliers
        SupplierProfile::factory()
            ->count(3)
            ->suspended()
            ->create();
    }
}

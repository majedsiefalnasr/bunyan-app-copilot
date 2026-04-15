<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Top-level categories (building materials domain)
        $buildingMaterials = Category::create([
            'name_ar' => 'مواد البناء',
            'name_en' => 'Building Materials',
            'slug' => 'building-materials',
            'icon' => 'lucide-bricks',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $electrical = Category::create([
            'name_ar' => 'كهربائي',
            'name_en' => 'Electrical',
            'slug' => 'electrical',
            'icon' => 'lucide-zap',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $plumbing = Category::create([
            'name_ar' => 'السباكة',
            'name_en' => 'Plumbing',
            'slug' => 'plumbing',
            'icon' => 'lucide-pipe',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $hardware = Category::create([
            'name_ar' => 'الأدوات',
            'name_en' => 'Hardware',
            'slug' => 'hardware',
            'icon' => 'lucide-hammer',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $tools = Category::create([
            'name_ar' => 'الأدوات اليدوية',
            'name_en' => 'Hand Tools',
            'slug' => 'hand-tools',
            'icon' => 'lucide-wrench',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        // Sub-categories under Building Materials
        Category::create([
            'parent_id' => $buildingMaterials->id,
            'name_ar' => 'الطوب والكتل',
            'name_en' => 'Bricks & Blocks',
            'slug' => 'bricks-blocks',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Category::create([
            'parent_id' => $buildingMaterials->id,
            'name_ar' => 'الأسمنت',
            'name_en' => 'Cement',
            'slug' => 'cement',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Sub-categories under Electrical
        Category::create([
            'parent_id' => $electrical->id,
            'name_ar' => 'الأسلاك والكابلات',
            'name_en' => 'Wires & Cables',
            'slug' => 'wires-cables',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Category::create([
            'parent_id' => $electrical->id,
            'name_ar' => 'التوزيع الكهربائي',
            'name_en' => 'Electrical Distribution',
            'slug' => 'electrical-distribution',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Sub-categories under Plumbing
        Category::create([
            'parent_id' => $plumbing->id,
            'name_ar' => 'الأنابيب والتركيبات',
            'name_en' => 'Pipes & Fittings',
            'slug' => 'pipes-fittings',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Category::create([
            'parent_id' => $plumbing->id,
            'name_ar' => 'الصنوبر والحنفيات',
            'name_en' => 'Faucets & Taps',
            'slug' => 'faucets-taps',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }
}

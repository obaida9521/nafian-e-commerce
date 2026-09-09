<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, array{name: string, tone: string, tone2: string, home: bool}> $categories */
        $categories = [
            ['name' => 'Bags', 'tone' => '#E7DFD2', 'tone2' => '#CFC2AC', 'home' => true],
            ['name' => 'Outerwear', 'tone' => '#DED7C9', 'tone2' => '#C6BBA4', 'home' => false],
            ['name' => 'Apparel', 'tone' => '#E3DECF', 'tone2' => '#CBC3AC', 'home' => true],
            ['name' => 'Footwear', 'tone' => '#DAD2C2', 'tone2' => '#BEB39C', 'home' => false],
            ['name' => 'Accessories', 'tone' => '#E6DECB', 'tone2' => '#CFC4A7', 'home' => true],
        ];

        foreach ($categories as $index => $data) {
            Category::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'tone' => $data['tone'],
                    'tone2' => $data['tone2'],
                    'show_on_home' => $data['home'],
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }
    }
}

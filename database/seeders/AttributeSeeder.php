<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = ['Size', 'Color', 'Type', 'Concentration'];

        foreach ($attributes as $name) {
            AttributeDefinition::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}

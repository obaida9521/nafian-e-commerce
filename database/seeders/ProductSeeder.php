<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');
        $sizeAttr = AttributeDefinition::where('slug', 'size')->first();
        $colorAttr = AttributeDefinition::where('slug', 'color')->first();

        /** @var array<int, array{name:string, cat:string, price:float, colors:list<string>, sizes:list<string>, rating:float, reviews:int, sku:string, badge:?string, stock:int, tone:string, tone2:string, featured:bool}> $products */
        $products = [
            ['name' => 'Marlowe Structured Tote', 'cat' => 'Bags', 'price' => 340, 'colors' => ['Tan', 'Black', 'Cognac'], 'sizes' => ['One Size'], 'rating' => 4.8, 'reviews' => 126, 'sku' => 'NF-BAG-014', 'badge' => null, 'stock' => 42, 'tone' => '#C9B49A', 'tone2' => '#A98F6E', 'featured' => true],
            ['name' => 'Atelier Wool Overcoat', 'cat' => 'Outerwear', 'price' => 480, 'colors' => ['Camel', 'Charcoal'], 'sizes' => ['XS', 'S', 'M', 'L', 'XL'], 'rating' => 4.9, 'reviews' => 88, 'sku' => 'NF-OUT-021', 'badge' => 'New', 'stock' => 12, 'tone' => '#C19A6B', 'tone2' => '#9A7C52', 'featured' => false],
            ['name' => 'Cashmere Ribbed Scarf', 'cat' => 'Accessories', 'price' => 120, 'colors' => ['Oat', 'Slate', 'Burgundy'], 'sizes' => ['One Size'], 'rating' => 4.7, 'reviews' => 204, 'sku' => 'NF-ACC-007', 'badge' => null, 'stock' => 88, 'tone' => '#D8CBB6', 'tone2' => '#B9AC95', 'featured' => true],
            ['name' => 'Bianca Leather Loafers', 'cat' => 'Footwear', 'price' => 290, 'colors' => ['Black', 'Cognac'], 'sizes' => ['36', '37', '38', '39', '40', '41', '42'], 'rating' => 4.6, 'reviews' => 73, 'sku' => 'NF-FOO-033', 'badge' => 'Low Stock', 'stock' => 5, 'tone' => '#9A8466', 'tone2' => '#7C6849', 'featured' => false],
            ['name' => 'Silk Twill Shirt', 'cat' => 'Apparel', 'price' => 180, 'colors' => ['Ivory', 'Sage'], 'sizes' => ['XS', 'S', 'M', 'L', 'XL'], 'rating' => 4.5, 'reviews' => 61, 'sku' => 'NF-APP-049', 'badge' => null, 'stock' => 0, 'tone' => '#CFC9BD', 'tone2' => '#B3AC9C', 'featured' => false],
            ['name' => 'Minimalist Field Watch', 'cat' => 'Accessories', 'price' => 260, 'colors' => ['Steel', 'Gold'], 'sizes' => ['One Size'], 'rating' => 4.8, 'reviews' => 142, 'sku' => 'NF-ACC-058', 'badge' => null, 'stock' => 27, 'tone' => '#BFC3C7', 'tone2' => '#9DA2A7', 'featured' => true],
            ['name' => 'Tailored Wide-Leg Trouser', 'cat' => 'Apparel', 'price' => 165, 'colors' => ['Black', 'Stone'], 'sizes' => ['24', '26', '28', '30', '32'], 'rating' => 4.4, 'reviews' => 39, 'sku' => 'NF-APP-062', 'badge' => null, 'stock' => 34, 'tone' => '#C2BBB0', 'tone2' => '#A39B8E', 'featured' => false],
            ['name' => 'Pebbled Card Holder', 'cat' => 'Bags', 'price' => 85, 'colors' => ['Black', 'Tan', 'Navy'], 'sizes' => ['One Size'], 'rating' => 4.7, 'reviews' => 318, 'sku' => 'NF-BAG-070', 'badge' => null, 'stock' => 120, 'tone' => '#B9A98C', 'tone2' => '#9A8B6E', 'featured' => true],
            ['name' => 'Merino Crew Knit', 'cat' => 'Apparel', 'price' => 145, 'colors' => ['Heather', 'Navy', 'Forest'], 'sizes' => ['XS', 'S', 'M', 'L', 'XL'], 'rating' => 4.6, 'reviews' => 97, 'sku' => 'NF-APP-081', 'badge' => null, 'stock' => 18, 'tone' => '#C7C2B4', 'tone2' => '#A8A294', 'featured' => false],
            ['name' => 'Acetate Sunglasses', 'cat' => 'Accessories', 'price' => 135, 'colors' => ['Tortoise', 'Black'], 'sizes' => ['One Size'], 'rating' => 4.5, 'reviews' => 55, 'sku' => 'NF-ACC-090', 'badge' => 'New', 'stock' => 8, 'tone' => '#C9BBA3', 'tone2' => '#AA9C82', 'featured' => false],
        ];

        foreach ($products as $data) {
            /** @var Product $product */
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'short_description' => "Considered {$data['cat']} piece, made in small batches to outlast the season.",
                    'description' => "The {$data['name']} is part of the Nafian Autumn Collection — modern essentials in leather, wool and silk, designed in studio and made to last. Crafted with restraint for a quieter wardrobe.",
                    'tone' => $data['tone'],
                    'tone2' => $data['tone2'],
                    'badge' => $data['badge'],
                    'rating' => $data['rating'],
                    'reviews_count' => $data['reviews'],
                    'is_active' => true,
                    'is_featured' => $data['featured'],
                ],
            );

            if (isset($categories[$data['cat']])) {
                $product->categories()->sync([$categories[$data['cat']]]);
            }

            $sortOrder = 0;

            foreach ($data['colors'] as $color) {
                foreach ($data['sizes'] as $size) {
                    $sizeToken = Str::upper(Str::slug($size));
                    $colorToken = Str::upper(Str::slug($color));
                    $sku = "{$data['sku']}-{$colorToken}-{$sizeToken}";

                    /** @var ProductVariant $variant */
                    $variant = ProductVariant::updateOrCreate(
                        ['sku' => $sku],
                        [
                            'product_id' => $product->id,
                            'price' => $data['price'],
                            'compare_at_price' => $data['badge'] === 'New' ? null : round($data['price'] * 1.18),
                            'cost_price' => round($data['price'] * 0.55),
                            'stock_quantity' => $data['stock'],
                            'reserved_quantity' => 0,
                            'is_active' => true,
                            'sort_order' => $sortOrder++,
                        ],
                    );

                    if ($colorAttr) {
                        $variant->attributeValues()->updateOrCreate(
                            ['attribute_id' => $colorAttr->id],
                            ['value' => $color],
                        );
                    }

                    if ($sizeAttr) {
                        $variant->attributeValues()->updateOrCreate(
                            ['attribute_id' => $sizeAttr->id],
                            ['value' => $size],
                        );
                    }
                }
            }
        }
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Create a product with its variants, attributes, categories and images.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function create(array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($data, $images): Product {
            $product = Product::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
            ]);

            $product->categories()->sync($data['categories'] ?? []);

            $this->syncVariants($product, $data['variants'] ?? []);
            $this->attachImages($product, $images);

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function update(Product $product, array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($product, $data, $images): Product {
            $product->update([
                'name' => $data['name'],
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
            ]);

            $product->categories()->sync($data['categories'] ?? []);

            $this->syncVariants($product, $data['variants'] ?? []);
            $this->removeImages($product, $data['remove_images'] ?? []);
            $this->attachImages($product, $images);

            return $product;
        });
    }

    /**
     * Delete media the admin marked for removal in the edit form.
     *
     * @param  array<int, int|string>  $ids
     */
    private function removeImages(Product $product, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $product->media()->whereIn('id', $ids)->get()->each(fn ($media) => $media->delete());
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $keptIds = [];

        foreach ($variants as $row) {
            $variant = $product->variants()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'sku' => $row['sku'],
                    'price' => $row['price'],
                    'compare_at_price' => $row['compare_at_price'] ?? null,
                    'cost_price' => $row['cost_price'] ?? null,
                    'stock_quantity' => $row['stock_quantity'],
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ],
            );

            $keptIds[] = $variant->id;

            foreach ($row['attributes'] ?? [] as $attr) {
                if (empty($attr['attribute_id']) || ($attr['value'] ?? '') === '') {
                    continue;
                }

                $variant->attributeValues()->updateOrCreate(
                    ['attribute_id' => $attr['attribute_id']],
                    ['value' => $attr['value']],
                );
            }
        }

        // Soft-delete variants the admin removed from the form.
        $product->variants()->whereNotIn('id', $keptIds)->each(fn ($v) => $v->delete());
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function attachImages(Product $product, array $images): void
    {
        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $product->addMedia($image)->toMediaCollection('images');
            }
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}

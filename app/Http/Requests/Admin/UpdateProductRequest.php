<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Services\MediaLibraryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:200'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_combo' => ['nullable', 'boolean'],
            'hide_when_out_of_stock' => ['nullable', 'boolean'],
            'notes_top' => ['nullable', 'string', 'max:255'],
            'notes_heart' => ['nullable', 'string', 'max:255'],
            'notes_base' => ['nullable', 'string', 'max:255'],
            'ingredients' => ['nullable', 'string', 'max:2000'],
            'usage_instructions' => ['nullable', 'string', 'max:2000'],
            'video_url' => ['nullable', 'url', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if (Product::parseYoutubeId($value) === null) {
                    $fail('সঠিক YouTube ভিডিও লিংক দিন।');
                }
            }],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
            'library_images' => ['nullable', 'array', 'max:20'],
            'library_images.*' => ['string', 'regex:'.MediaLibraryService::KEY_PATTERN],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product->id)],
            'variants.*.sku' => ['required', 'string', 'max:100', 'distinct'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.image' => ['nullable', 'image', 'max:4096'],
            'variants.*.remove_image' => ['nullable', 'boolean'],
            'variants.*.library_image' => ['nullable', 'string', 'regex:'.MediaLibraryService::KEY_PATTERN],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.attributes.*.attribute_id' => ['nullable', 'integer', 'exists:attribute_definitions,id'],
            'variants.*.attributes.*.value' => ['nullable', 'string', 'max:100'],
        ];
    }
}

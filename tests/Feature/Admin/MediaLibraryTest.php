<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = Admin::factory()->superAdmin()->create();
    }

    /**
     * Upload one image to the library and return its key.
     */
    private function libraryKey(string $name = 'flacon.jpg'): string
    {
        return $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.media.store'), ['files' => [UploadedFile::fake()->image($name, 400, 440)]])
            ->assertOk()
            ->json('data.0.key');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(array $overrides = []): array
    {
        $size = AttributeDefinition::firstOrCreate(['slug' => 'size'], ['name' => 'Size']);

        return array_replace_recursive([
            'name' => 'Oud Nafis',
            'is_active' => '1',
            'variants' => [
                ['sku' => 'ON-12', 'price' => 1450, 'stock_quantity' => 5, 'is_active' => '1', 'attributes' => [['attribute_id' => $size->id, 'value' => '12ml']]],
            ],
        ], $overrides);
    }

    public function test_upload_adds_images_to_the_library(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')]])
            ->assertOk()
            ->assertJsonPath('data.0.source', 'library')
            ->assertJsonPath('data.0.deletable', true);

        $this->assertSame(2, MediaAsset::count());
        $this->assertStringStartsWith('media:', $response->json('data.1.key'));
    }

    public function test_manager_lists_every_source_and_filters(): void
    {
        $this->libraryKey();
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->productPayload([
            'images' => [UploadedFile::fake()->image('front.jpg')],
            'variants' => [0 => ['image' => UploadedFile::fake()->image('12ml.jpg')]],
        ]));
        Storage::disk('public')->put('categories/bags.jpg', UploadedFile::fake()->image('bags.jpg')->getContent());
        Category::factory()->create(['name' => 'Bags', 'image_path' => 'categories/bags.jpg']);
        Storage::disk('public')->put('branding/logo.png', UploadedFile::fake()->image('logo.png')->getContent());
        app(SettingsService::class)->put('general', ['logo' => 'branding/logo.png']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertViewHas('counts', fn (array $c) => $c['all'] === 5 && $c['library'] === 1 && $c['product'] === 1
                && $c['variant'] === 1 && $c['category'] === 1 && $c['logo'] === 1)
            ->assertSee('Oud Nafis');

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.media.index', ['source' => 'category']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.owner', 'Bags')
            ->assertJsonPath('data.0.deletable', false);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.media.index', ['search' => 'oud']))
            ->assertJsonPath('total', 2);
    }

    public function test_library_image_can_be_deleted_but_product_images_cannot(): void
    {
        $key = $this->libraryKey();
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->productPayload([
            'images' => [UploadedFile::fake()->image('front.jpg')],
        ]));
        $productKey = 'media:'.Product::firstOrFail()->getFirstMedia('images')->id;

        $this->actingAs($this->admin, 'admin')->delete(route('admin.media.destroy', $productKey))->assertSessionHas('error');
        $this->assertTrue(Product::firstOrFail()->hasMedia('images'));

        $this->actingAs($this->admin, 'admin')->delete(route('admin.media.destroy', $key))->assertSessionHas('success');
        $this->assertSame(0, MediaAsset::count());
    }

    public function test_product_and_variant_can_use_library_images(): void
    {
        $gallery = $this->libraryKey('gallery.jpg');
        $variantPhoto = $this->libraryKey('variant.jpg');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $this->productPayload([
                'library_images' => [$gallery],
                'variants' => [0 => ['library_image' => $variantPhoto]],
            ]))
            ->assertSessionHasNoErrors();

        $product = Product::with('variants')->firstOrFail();
        $this->assertCount(1, $product->getMedia('images'));
        $this->assertTrue($product->variants->first()->hasMedia('image'));
        Storage::disk('public')->assertExists($product->getFirstMedia('images')->getPathRelativeToRoot());

        // The file was copied: removing it from the library leaves the product intact.
        $this->actingAs($this->admin, 'admin')->delete(route('admin.media.destroy', $gallery));
        Storage::disk('public')->assertExists($product->fresh()->getFirstMedia('images')->getPathRelativeToRoot());
    }

    public function test_category_image_and_logo_can_use_library_images(): void
    {
        $key = $this->libraryKey();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.categories.store'), ['name' => 'Attar', 'library_image' => $key])
            ->assertSessionHasNoErrors();
        $category = Category::where('name', 'Attar')->firstOrFail();
        $this->assertStringStartsWith('categories/', $category->image_path);
        Storage::disk('public')->assertExists($category->image_path);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.general'), [
                'store_name' => 'Nafian', 'support_email' => 'team@nafian.com', 'currency' => 'BDT',
                'delivery_inside' => 60, 'delivery_outside' => 120, 'library_logo' => $key,
            ])
            ->assertSessionHasNoErrors();
        $general = app(SettingsService::class)->group('general');
        $this->assertStringStartsWith('branding/', $general['logo']);
        $this->assertArrayNotHasKey('library_logo', $general);
        Storage::disk('public')->assertExists($general['logo']);
    }

    public function test_malformed_library_keys_are_rejected(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $this->productPayload(['library_images' => ['../../.env']]))
            ->assertSessionHasErrors('library_images.0');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.categories.store'), ['name' => 'X', 'library_image' => 'category:abc'])
            ->assertSessionHasErrors('library_image');
    }

    public function test_viewer_can_browse_but_not_upload(): void
    {
        $viewer = Admin::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->get(route('admin.media.index'))->assertOk();
        $this->actingAs($viewer, 'admin')
            ->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('a.jpg')]])
            ->assertForbidden();
    }

    public function test_every_image_field_offers_the_media_picker(): void
    {
        foreach ([route('admin.products.create'), route('admin.categories.create'), route('admin.settings.index')] as $url) {
            $this->actingAs($this->admin, 'admin')
                ->get($url)
                ->assertOk()
                ->assertSee('id="media-picker"', false)
                ->assertSee('মিডিয়া থেকে বেছে নিন');
        }

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.create'))
            ->assertSee('variants[${vi}][library_image]', false);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AttributeDefinition;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMediaTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private AttributeDefinition $size;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = Admin::factory()->superAdmin()->create();
        $this->size = AttributeDefinition::create(['name' => 'Size', 'slug' => 'size']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Oud Nafis',
            'is_active' => '1',
            'variants' => [
                ['sku' => 'ON-12', 'price' => 1450, 'stock_quantity' => 5, 'is_active' => '1', 'attributes' => [['attribute_id' => $this->size->id, 'value' => '12ml']]],
                ['sku' => 'ON-50', 'price' => 3200, 'stock_quantity' => 5, 'is_active' => '1', 'attributes' => [['attribute_id' => $this->size->id, 'value' => '50ml']]],
            ],
        ], $overrides);
    }

    public function test_admin_can_add_a_photo_per_variant_and_a_youtube_video(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $this->payload([
                'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
                'variants' => [1 => ['image' => UploadedFile::fake()->image('50ml.jpg', 600, 660)]],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $product = Product::with('variants')->where('name', 'Oud Nafis')->firstOrFail();
        $small = $product->variants->firstWhere('sku', 'ON-12');
        $large = $product->variants->firstWhere('sku', 'ON-50');

        $this->assertSame('dQw4w9WgXcQ', $product->youtubeId());
        $this->assertFalse($small->hasMedia('image'));
        $this->assertTrue($large->hasMedia('image'));
        Storage::disk('public')->assertExists($large->getFirstMedia('image')->getPathRelativeToRoot());
    }

    public function test_variant_photo_can_be_replaced_and_removed(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->payload([
            'variants' => [0 => ['image' => UploadedFile::fake()->image('first.jpg')]],
        ]));
        $product = Product::with('variants')->firstOrFail();
        $variant = $product->variants->firstWhere('sku', 'ON-12');
        $ids = $product->variants->pluck('id', 'sku');

        $update = fn (array $variantOverrides) => $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $product), $this->payload([
                'variants' => [
                    0 => ['id' => $ids['ON-12']] + $variantOverrides,
                    1 => ['id' => $ids['ON-50']],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $update(['image' => UploadedFile::fake()->image('second.jpg')]);
        $this->assertSame('second.jpg', $variant->fresh()->getFirstMedia('image')->file_name);
        $this->assertCount(1, $variant->fresh()->getMedia('image'));

        $update(['remove_image' => '0']);
        $this->assertTrue($variant->fresh()->hasMedia('image'));

        $update(['remove_image' => '1']);
        $this->assertFalse($variant->fresh()->hasMedia('image'));
    }

    public function test_non_youtube_video_link_is_rejected(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $this->payload(['video_url' => 'https://vimeo.com/123456']))
            ->assertSessionHasErrors('video_url');

        $this->assertSame(0, Product::count());
    }

    public function test_youtube_links_are_parsed(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtube.com/watch?feature=share&v=dQw4w9WgXcQ',
            'https://m.youtube.com/watch?v=dQw4w9WgXcQ&t=10s',
            'https://youtu.be/dQw4w9WgXcQ?si=abc',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ] as $url) {
            $this->assertSame('dQw4w9WgXcQ', Product::parseYoutubeId($url), $url);
        }

        $this->assertNull(Product::parseYoutubeId('https://example.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(Product::parseYoutubeId(null));
    }

    public function test_product_page_shows_variant_photos_and_the_video(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->payload([
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'variants' => [1 => ['image' => UploadedFile::fake()->image('50ml.jpg')]],
        ]));
        $product = Product::with('variants')->firstOrFail();
        $large = $product->variants->firstWhere('sku', 'ON-50');

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee($large->getFirstMediaUrl('image'), false)
            ->assertSee('\u0022slide\u0022:0', false)
            ->assertSee('i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', false)
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
    }

    public function test_edit_form_shows_existing_variant_photo_and_video(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->payload([
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'variants' => [0 => ['image' => UploadedFile::fake()->image('first.jpg')]],
        ]));
        $product = Product::firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('dQw4w9WgXcQ', false)
            ->assertSee('first', false)
            ->assertSee('ভিডিও');
    }

    public function test_product_card_fades_to_the_second_photo_on_hover(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->payload([
            'images' => [UploadedFile::fake()->image('front.jpg'), UploadedFile::fake()->image('back.jpg')],
        ]));
        $product = Product::firstOrFail();
        $second = $product->getMedia('images')->get(1);

        $this->get('/shop')
            ->assertOk()
            ->assertSee('group-hover/img:opacity-100', false)
            ->assertSee($second->getUrl('thumb'), false);
    }

    public function test_product_card_uses_a_variant_photo_when_there_is_only_one_product_photo(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->payload([
            'images' => [UploadedFile::fake()->image('front.jpg')],
            'variants' => [1 => ['image' => UploadedFile::fake()->image('50ml.jpg')]],
        ]));
        $variant = Product::with('variants')->firstOrFail()->variants->firstWhere('sku', 'ON-50');

        $this->get('/shop')
            ->assertOk()
            ->assertSee('group-hover/img:opacity-100', false)
            ->assertSee($variant->getFirstMediaUrl('image', 'thumb'), false);
    }

    public function test_single_photo_product_card_has_no_hover_swap(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->payload([
            'images' => [UploadedFile::fake()->image('front.jpg')],
        ]));

        $this->get('/shop')->assertOk()->assertDontSee('group-hover/img:opacity-100', false);
    }

    public function test_products_index_renders_mobile_cards_alongside_the_table(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->payload([
            'variants' => [1 => ['stock_quantity' => 0]],
        ]));

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('hidden lg:block', false)
            ->assertSee('lg:hidden grid', false)
            ->assertSee('কম স্টক')
            ->assertSee('৳1,450+');
    }

    public function test_inventory_renders_mobile_cards_with_stock_state(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), $this->payload([
            'variants' => [1 => ['stock_quantity' => 0]],
        ]));

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('lg:hidden grid', false)
            ->assertSee('বিক্রিযোগ্য')
            ->assertSee('স্টক শেষ')
            ->assertSee('কম স্টক');
    }
}

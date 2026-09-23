<?php

namespace Tests\Feature;

use App\Models\AttributeDefinition;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestockRequest;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CategorySeeder::class, AttributeSeeder::class, ProductSeeder::class]);
    }

    public function test_shop_lists_active_products_with_counts(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('১০টি পণ্য')
            ->assertSee('Marlowe Structured Tote')
            ->assertSee('Merino Crew Knit');
    }

    public function test_shop_paginates(): void
    {
        config(['shop.per_page' => 4]);

        $this->get('/shop')
            ->assertOk()
            ->assertSee('পরবর্তী')
            ->assertViewHas('products', fn ($products) => $products->count() === 4 && $products->total() === 10);

        $this->get('/shop?page=3')
            ->assertViewHas('products', fn ($products) => $products->count() === 2);
    }

    public function test_filters_by_size(): void
    {
        $this->get('/shop?sizes[]=XL')
            ->assertOk()
            ->assertSee('Atelier Wool Overcoat')
            ->assertSee('Merino Crew Knit')
            ->assertDontSee('Marlowe Structured Tote');
    }

    public function test_filters_by_colour(): void
    {
        $this->get('/shop?colors[]=Navy')
            ->assertOk()
            ->assertSee('Pebbled Card Holder')
            ->assertSee('Merino Crew Knit')
            ->assertDontSee('Marlowe Structured Tote');
    }

    public function test_filters_by_type(): void
    {
        $this->get('/shop')->assertDontSee('সুগন্ধির ধরন');

        $this->giveType('Merino Crew Knit', 'Oriental');
        $this->giveType('Bianca Leather Loafers', 'Woody');

        // Type options show once a category narrows the listing.
        $this->giveType('Silk Twill Shirt', 'Floral');
        $this->get(route('store.shop.category', 'apparel'))->assertSee('সুগন্ধির ধরন')->assertSee('Oriental');

        $this->get('/shop?types[]=Oriental')
            ->assertOk()
            ->assertSee('Merino Crew Knit')
            ->assertDontSee('Marlowe Structured Tote')
            ->assertViewHas('products', fn ($products) => $products->total() === 1);
    }

    public function test_all_products_page_starts_with_only_category_price_and_toggles(): void
    {
        $this->giveType('Merino Crew Knit', 'Oriental');
        $this->giveType('Bianca Leather Loafers', 'Woody');

        $this->get('/shop')
            ->assertOk()
            ->assertSee('ধরন, রং ও সাইজ দিয়ে ফিল্টার করতে আগে একটি ক্যাটাগরি বেছে নিন।')
            ->assertDontSee('সুগন্ধির ধরন')
            ->assertDontSee('name="colors[]"', false)
            ->assertDontSee('name="sizes[]"', false)
            ->assertSee('শুধু স্টকে আছে')
            ->assertSee('সর্বনিম্ন দাম')
            ->assertViewHas('facets', fn (array $facets) => $facets['attributes_need_category']
                && $facets['types'] === [] && $facets['colors'] === [] && $facets['sizes'] === []);
    }

    public function test_arriving_with_an_attribute_filter_shows_the_matching_facets(): void
    {
        // e.g. a home-page link to /shop?colors[]=Navy
        $this->get('/shop?colors[]=Navy')
            ->assertOk()
            ->assertDontSee('ধরন, রং ও সাইজ দিয়ে ফিল্টার করতে')
            ->assertSee('name="colors[]"', false)
            ->assertViewHas('facets', fn (array $facets) => ! $facets['attributes_need_category'] && in_array('Navy', $facets['colors'], true));
    }

    public function test_a_search_within_one_category_shows_its_facets(): void
    {
        $this->get('/shop?q=Loafers')
            ->assertOk()
            ->assertViewHas('facets', fn (array $facets) => ! $facets['attributes_need_category']
                && $facets['sizes'] === ['36', '37', '38', '39', '40', '41', '42']);
    }

    public function test_attribute_filter_across_categories_still_withholds_mixed_sizes(): void
    {
        // Black spans shoes (36–42), trousers (24–32) and bags: sizes wait for a category.
        $this->get('/shop?colors[]=Black')
            ->assertViewHas('facets', fn (array $facets) => $facets['sizes'] === [] && $facets['sizes_need_category']);

        // Navy is a one-size bag plus knitwear, so only the knit sizes are meaningful.
        $this->get('/shop?colors[]=Navy')
            ->assertViewHas('facets', fn (array $facets) => $facets['sizes'] === ['XS', 'S', 'M', 'L', 'XL']);
    }

    public function test_category_page_only_offers_its_own_sizes_in_order(): void
    {
        $this->get(route('store.shop.category', 'footwear'))
            ->assertOk()
            ->assertDontSee('সাইজ দিয়ে ফিল্টার করতে')
            ->assertViewHas('facets', fn (array $facets) => $facets['sizes'] === ['36', '37', '38', '39', '40', '41', '42']
                && $facets['colors'] === ['Black', 'Cognac']);

        $this->get(route('store.shop.category', 'outerwear'))
            ->assertViewHas('facets', fn (array $facets) => $facets['sizes'] === ['XS', 'S', 'M', 'L', 'XL']);
    }

    public function test_single_option_facets_are_hidden(): void
    {
        // Every bag is "One Size": offering that as a size filter would be meaningless.
        $this->get(route('store.shop.category', 'bags'))
            ->assertOk()
            ->assertViewHas('facets', fn (array $facets) => $facets['sizes'] === [] && ! $facets['sizes_need_category']);
    }

    public function test_picking_a_type_narrows_the_other_filters(): void
    {
        $this->giveType('Merino Crew Knit', 'Oriental');
        $this->giveType('Bianca Leather Loafers', 'Woody');

        $this->get('/shop?types[]=Oriental')
            ->assertOk()
            ->assertViewHas('facets', fn (array $facets) => $facets['sizes'] === ['XS', 'S', 'M', 'L', 'XL']
                && ! in_array('Cognac', $facets['colors'], true)
                && in_array('Heather', $facets['colors'], true)
                // The type facet itself still lists the alternatives.
                && $facets['types'] === ['Oriental', 'Woody']);
    }

    public function test_a_selected_option_stays_visible(): void
    {
        $this->get(route('store.shop.category', 'footwear').'?colors[]=Navy')
            ->assertOk()
            ->assertViewHas('facets', fn (array $facets) => in_array('Navy', $facets['colors'], true));
    }

    public function test_filters_by_price_range(): void
    {
        $this->get('/shop?max=100')
            ->assertOk()
            ->assertSee('Pebbled Card Holder')
            ->assertDontSee('Cashmere Ribbed Scarf');

        $this->get('/shop?min=400')
            ->assertOk()
            ->assertSee('Atelier Wool Overcoat')
            ->assertDontSee('Pebbled Card Holder');
    }

    public function test_in_stock_filter_hides_sold_out_products(): void
    {
        $this->get('/shop')->assertSee('Silk Twill Shirt');

        $this->get('/shop?in_stock=1')
            ->assertOk()
            ->assertDontSee('Silk Twill Shirt')
            ->assertSee('Marlowe Structured Tote');
    }

    public function test_on_sale_filter_only_keeps_discounted_products(): void
    {
        $this->get('/shop?on_sale=1')
            ->assertOk()
            ->assertDontSee('Atelier Wool Overcoat')
            ->assertSee('Marlowe Structured Tote');
    }

    public function test_top_rated_filter(): void
    {
        $this->get('/shop?top_rated=1')
            ->assertOk()
            ->assertDontSee('Tailored Wide-Leg Trouser')
            ->assertSee('Atelier Wool Overcoat');
    }

    public function test_search_matches_product_name(): void
    {
        $this->get('/shop?q=cashmere')
            ->assertOk()
            ->assertSee('Cashmere Ribbed Scarf')
            ->assertDontSee('Marlowe Structured Tote');
    }

    public function test_filters_combine_with_category(): void
    {
        $this->get('/shop/bags?colors[]=Navy')
            ->assertOk()
            ->assertSee('Pebbled Card Holder')
            ->assertDontSee('Merino Crew Knit')
            ->assertDontSee('Marlowe Structured Tote');
    }

    public function test_sorts_by_price(): void
    {
        $this->get('/shop?sort=price_asc')
            ->assertOk()
            ->assertSeeInOrder(['Pebbled Card Holder', 'Cashmere Ribbed Scarf', 'Atelier Wool Overcoat']);

        $this->get('/shop?sort=price_desc')
            ->assertOk()
            ->assertSeeInOrder(['Atelier Wool Overcoat', 'Cashmere Ribbed Scarf', 'Pebbled Card Holder']);
    }

    public function test_best_selling_sort_ranks_by_units_sold(): void
    {
        $trouser = Product::where('slug', 'tailored-wide-leg-trouser')->with('variants')->firstOrFail();
        $variant = $trouser->variants->first();

        OrderItem::create([
            'order_id' => Order::factory()->create()->id,
            'variant_id' => $variant->id,
            'product_name' => $trouser->name,
            'variant_name' => $variant->display_name,
            'sku' => $variant->sku,
            'unit_price' => $variant->price,
            'quantity' => 5,
            'line_total' => $variant->price * 5,
        ]);

        $this->get('/shop')
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->first()->is($trouser));
    }

    public function test_invalid_sort_is_rejected(): void
    {
        $this->get('/shop?sort=bogus')->assertSessionHasErrors('sort');
    }

    public function test_shopper_can_ask_to_be_notified_when_back_in_stock(): void
    {
        $shirt = Product::where('slug', 'silk-twill-shirt')->firstOrFail();

        $this->get('/shop')->assertSee('স্টকে এলে জানান');

        $this->postJson(route('store.product.restock', $shirt->slug), ['contact' => 'buyer@example.com'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->postJson(route('store.product.restock', $shirt->slug), ['contact' => 'buyer@example.com'])->assertOk();

        $this->assertSame(1, RestockRequest::where('product_id', $shirt->id)->count());
    }

    public function test_restock_request_accepts_a_phone_number(): void
    {
        $shirt = Product::where('slug', 'silk-twill-shirt')->firstOrFail();

        $this->post(route('store.product.restock', $shirt->slug), ['contact' => '01712345678'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('restock_requests', ['product_id' => $shirt->id, 'contact' => '01712345678']);
    }

    public function test_restock_request_requires_valid_contact(): void
    {
        $shirt = Product::where('slug', 'silk-twill-shirt')->firstOrFail();

        $this->postJson(route('store.product.restock', $shirt->slug), ['contact' => 'not a contact'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contact');

        $this->assertDatabaseCount('restock_requests', 0);
    }

    private function giveType(string $productName, string $value): void
    {
        $type = AttributeDefinition::where('slug', 'type')->firstOrFail();

        Product::where('name', $productName)->firstOrFail()->variants
            ->each(fn ($variant) => $variant->attributeValues()->create(['attribute_id' => $type->id, 'value' => $value]));
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->superAdmin()->create();
    }

    public function test_all_admin_index_pages_render(): void
    {
        Product::factory()->has(ProductVariant::factory()->count(2), 'variants')->create();
        Category::factory()->create();
        Coupon::factory()->create();
        User::factory()->create();
        Order::factory()->create();

        $routes = [
            'admin.dashboard',
            'admin.products.index',
            'admin.categories.index',
            'admin.orders.index',
            'admin.inventory.index',
            'admin.customers.index',
            'admin.coupons.index',
            'admin.reports.index',
            'admin.activity-logs.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($this->admin, 'admin')
                ->get(route($route))
                ->assertOk();
        }
    }

    public function test_product_create_and_edit_pages_render(): void
    {
        Category::factory()->create();
        AttributeDefinition::create(['name' => 'Size', 'slug' => 'size']);
        $product = Product::factory()->has(ProductVariant::factory()->count(1), 'variants')->create();

        $this->actingAs($this->admin, 'admin')->get(route('admin.products.create'))->assertOk();
        $this->actingAs($this->admin, 'admin')->get(route('admin.products.edit', $product))->assertOk();
    }

    public function test_admin_can_create_product_with_variants(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $size = AttributeDefinition::create(['name' => 'Size', 'slug' => 'size']);

        $response = $this->actingAs($this->admin, 'admin')->post(route('admin.products.store'), [
            'name' => 'Test Perfume',
            'short_description' => 'Nice',
            'is_active' => '1',
            'categories' => [$category->id],
            'variants' => [
                [
                    'sku' => 'TP-50',
                    'price' => 1500,
                    'stock_quantity' => 20,
                    'is_active' => '1',
                    'attributes' => [['attribute_id' => $size->id, 'value' => '50ml']],
                ],
                [
                    'sku' => 'TP-100',
                    'price' => 2500,
                    'stock_quantity' => 10,
                    'is_active' => '1',
                    'attributes' => [['attribute_id' => $size->id, 'value' => '100ml']],
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $product = Product::where('name', 'Test Perfume')->firstOrFail();
        $this->assertSame(2, $product->variants()->count());
        $this->assertDatabaseHas('product_variants', ['sku' => 'TP-50', 'stock_quantity' => 20]);
        $this->assertDatabaseHas('variant_attribute_values', ['value' => '100ml']);
        $this->assertTrue($product->categories->contains($category));
    }

    public function test_admin_can_crud_category(): void
    {
        $store = $this->actingAs($this->admin, 'admin')->post(route('admin.categories.store'), [
            'name' => 'Perfume',
            'is_active' => '1',
        ]);
        $store->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Perfume', 'slug' => 'perfume']);

        $category = Category::firstOrFail();
        $this->actingAs($this->admin, 'admin')->put(route('admin.categories.update', $category), [
            'name' => 'Perfume Updated',
            'is_active' => '1',
        ])->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Perfume Updated']);

        $this->actingAs($this->admin, 'admin')->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_admin_can_adjust_inventory(): void
    {
        $variant = ProductVariant::factory()->stock(5)->create();

        $this->actingAs($this->admin, 'admin')->post(route('admin.inventory.adjust', $variant), [
            'quantity_change' => 10,
            'reason' => 'Restock',
        ])->assertRedirect(route('admin.inventory.index'));

        $this->assertSame(15, $variant->fresh()->stock_quantity);
    }

    public function test_order_detail_renders_fulfilment_stepper(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Mark as Confirmed')
            ->assertSee('Cancel order')
            ->assertSee('Timeline');
    }

    public function test_admin_can_advance_order_status_from_detail(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.orders.update-status', $order), ['status' => 'confirmed'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('confirmed', $order->fresh()->status->value);
    }

    public function test_customer_drawer_renders_with_order_history(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.index', ['view' => $customer->id]))
            ->assertOk()
            ->assertSee('Total spent')
            ->assertSee('Order history')
            ->assertSee($order->order_number);
    }

    public function test_guest_orders_appear_in_guests_tab(): void
    {
        $order = Order::factory()->create([
            'user_id' => null,
            'guest_email' => 'guest@example.com',
            'guest_phone' => '01700000000',
            'shipping_name' => 'Walk In Guest',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.index', ['tab' => 'guests']))
            ->assertOk()
            ->assertSee('guest@example.com')
            ->assertSee('Walk In Guest');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.index', ['tab' => 'guests', 'guest' => 'guest@example.com']))
            ->assertOk()
            ->assertSee('Order history')
            ->assertSee($order->order_number);
    }

    public function test_admin_can_suspend_customer_from_drawer(): void
    {
        $customer = User::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.customers.update', $customer), ['is_active' => 0])
            ->assertRedirect(route('admin.customers.index', ['view' => $customer->id]));

        $this->assertFalse($customer->fresh()->is_active);
    }

    public function test_admin_can_toggle_category_home(): void
    {
        $category = Category::factory()->create(['show_on_home' => false]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.categories.toggle-home', $category))
            ->assertRedirect();

        $this->assertTrue($category->fresh()->show_on_home);
    }

    public function test_admin_can_upload_category_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.categories.store'), [
                'name' => 'Bags',
                'tone' => '#E7DFD2',
                'tone2' => '#CFC2AC',
                'sort_order' => 0,
                'is_active' => '1',
                'show_on_home' => '1',
                'image' => UploadedFile::fake()->image('bags.jpg', 600, 400),
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'bags')->firstOrFail();
        $this->assertNotNull($category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
    }

    public function test_admin_can_toggle_product_featured(): void
    {
        $product = Product::factory()->create(['is_featured' => false]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.products.toggle-featured', $product))
            ->assertRedirect();

        $this->assertTrue($product->fresh()->is_featured);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.products.toggle-featured', $product));

        $this->assertFalse($product->fresh()->is_featured);
    }

    public function test_admin_can_create_coupon(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.coupons.store'), [
            'code' => 'save20',
            'type' => 'percentage',
            'value' => 20,
            'min_order_amount' => 500,
            'is_active' => '1',
        ])->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', ['code' => 'SAVE20', 'value' => 20]);
    }

    public function test_coupons_index_renders_modal_trigger(): void
    {
        Coupon::factory()->create(['code' => 'WELCOME10']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.coupons.index'))
            ->assertOk()
            ->assertSee('Add coupon')
            ->assertSee('WELCOME10');
    }

    public function test_reports_render_with_quick_range(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', ['range' => '7d']))
            ->assertOk()
            ->assertSee('Net revenue')
            ->assertSee('Orders by day')
            ->assertSee('Payment method')
            ->assertSee('window.__report', false);
    }
}

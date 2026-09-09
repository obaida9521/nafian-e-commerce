<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_read_but_not_delete(): void
    {
        $viewer = Admin::factory()->create(['role' => 'viewer']);
        $product = Product::factory()->create();

        // Read allowed.
        $this->actingAs($viewer, 'admin')->get(route('admin.products.index'))->assertOk();

        // Write blocked.
        $this->actingAs($viewer, 'admin')
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_manager_can_manage_orders_but_not_coupons(): void
    {
        $manager = Admin::factory()->create(['role' => 'manager']);
        $order = Order::factory()->create(['status' => 'pending']);

        // Orders are in the manager's remit.
        $this->actingAs($manager, 'admin')
            ->patch(route('admin.orders.update-status', $order), ['status' => 'confirmed'])
            ->assertRedirect();
        $this->assertSame('confirmed', $order->fresh()->status->value);

        // Coupons are not.
        $this->actingAs($manager, 'admin')
            ->post(route('admin.coupons.store'), ['code' => 'X', 'type' => 'percentage', 'value' => 5])
            ->assertForbidden();
    }

    public function test_admin_role_can_write_everywhere(): void
    {
        $admin = Admin::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.store'), [
                'code' => 'ADMIN10', 'type' => 'percentage', 'value' => 10, 'is_active' => '1',
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', ['code' => 'ADMIN10']);
    }

    public function test_unknown_route_renders_styled_404(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminToastTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->superAdmin()->create();
    }

    /**
     * The toast payload is embedded with @js, which escapes non-ASCII as \uXXXX (and doubles the backslash).
     */
    private function assertToast(TestResponse $response, string $message): void
    {
        $encoded = str_replace('\\', '\\\\', trim(json_encode($message), '"'));

        $response->assertOk()
            ->assertSee('window.adminToast', false)
            ->assertSee($encoded, false);
    }

    /**
     * @js escapes double quotes inside its JSON as the six characters backslash-u-0-0-2-2.
     */
    private function js(string $json): string
    {
        return str_replace('"', chr(92).'u0022', $json);
    }

    public function test_successful_change_shows_a_success_snackbar(): void
    {
        $product = Product::factory()->create(['is_featured' => false]);

        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.products.index'))
            ->followingRedirects()
            ->patch(route('admin.products.toggle-featured', $product));

        $this->assertToast($response, 'ফিচার্ড করা হয়েছে।');
        $response->assertSee($this->js('"type":"success"'), false);
    }

    public function test_stock_conflict_returns_with_an_error_snackbar_instead_of_crashing(): void
    {
        $variant = ProductVariant::factory()->create(['stock_quantity' => 2, 'reserved_quantity' => 0]);

        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.inventory.index'))
            ->followingRedirects()
            ->post(route('admin.inventory.adjust', $variant), ['quantity_change' => -5, 'reason' => 'Damaged / write-off']);

        $this->assertToast($response, 'পর্যাপ্ত স্টক নেই');
        $response->assertSee($this->js('"type":"error"'), false);
        $this->assertSame(2, $variant->fresh()->stock_quantity);
    }

    public function test_validation_errors_show_an_error_snackbar_and_keep_the_inline_list(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->from(route('admin.categories.create'))
            ->followingRedirects()
            ->post(route('admin.categories.store'), ['name' => '']);

        $this->assertToast($response, 'কিছু তথ্য ঠিক নেই');
        $response->assertSee('bg-rose-soft text-rose', false);
    }

    public function test_pages_without_a_flash_render_no_initial_toasts(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('[].forEach', false);
    }
}

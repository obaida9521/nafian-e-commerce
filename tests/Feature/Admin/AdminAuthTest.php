<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_admin_can_log_in_with_valid_credentials(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@shop.com',
        ]);

        $response = $this->post(route('admin.login'), [
            'email' => 'admin@shop.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->fresh()->last_login_at);
    }

    public function test_admin_cannot_log_in_with_invalid_credentials(): void
    {
        Admin::factory()->create(['email' => 'admin@shop.com']);

        $this->post(route('admin.login'), [
            'email' => 'admin@shop.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_inactive_admin_cannot_log_in(): void
    {
        Admin::factory()->inactive()->create(['email' => 'off@shop.com']);

        $this->post(route('admin.login'), [
            'email' => 'off@shop.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_admin_can_view_dashboard(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_admin_can_log_out(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }
}

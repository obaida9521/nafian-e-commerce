<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_expense(): void
    {
        $admin = Admin::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')->post(route('admin.expenses.store'), [
            'title' => 'Shop rent — June',
            'category' => 'rent',
            'amount' => 1500,
            'spent_on' => '2026-06-01',
        ])->assertRedirect(route('admin.expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'title' => 'Shop rent — June',
            'category' => 'rent',
            'amount' => 1500,
            'admin_id' => $admin->id,
        ]);
    }

    public function test_viewer_cannot_record_expense(): void
    {
        $viewer = Admin::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer, 'admin')->post(route('admin.expenses.store'), [
            'title' => 'X', 'category' => 'other', 'amount' => 10, 'spent_on' => '2026-06-01',
        ])->assertForbidden();

        $this->assertSame(0, Expense::count());
    }

    public function test_manager_cannot_record_expense(): void
    {
        $manager = Admin::factory()->create(['role' => 'manager']);

        $this->actingAs($manager, 'admin')->post(route('admin.expenses.store'), [
            'title' => 'X', 'category' => 'other', 'amount' => 10, 'spent_on' => '2026-06-01',
        ])->assertForbidden();
    }

    public function test_index_filters_by_month(): void
    {
        $admin = Admin::factory()->create(['role' => 'admin']);
        Expense::factory()->create(['title' => 'June bill', 'spent_on' => '2026-06-10', 'amount' => 100]);
        Expense::factory()->create(['title' => 'May bill', 'spent_on' => '2026-05-10', 'amount' => 999]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.expenses.index', ['month' => '2026-06-01']))
            ->assertOk()
            ->assertSee('June bill')
            ->assertDontSee('May bill');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CategorySeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class,
        ]);
    }

    private function product(): Product
    {
        return Product::where('slug', 'marlowe-structured-tote')->firstOrFail();
    }

    public function test_guest_cannot_submit_a_review(): void
    {
        $product = $this->product();

        $this->post(route('store.product.review', $product->slug), [
            'rating' => 5,
            'body' => 'Lovely bag.',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_logged_in_customer_can_submit_a_review_and_rating_recalculates(): void
    {
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->post(route('store.product.review', $product->slug), [
                'rating' => 4,
                'title' => 'Solid',
                'body' => 'Great quality leather.',
            ])
            ->assertRedirect(route('store.product', $product->slug).'#reviews')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 4,
            'title' => 'Solid',
        ]);

        $product->refresh();
        $this->assertSame(1, $product->reviews_count);
        $this->assertSame('4.0', (string) $product->rating);
    }

    public function test_rating_is_required_and_bounded(): void
    {
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->post(route('store.product.review', $product->slug), ['rating' => 9])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_resubmitting_updates_the_existing_review(): void
    {
        $product = $this->product();
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->post(route('store.product.review', $product->slug), ['rating' => 2, 'body' => 'Meh.']);
        $this->actingAs($user, 'web')
            ->post(route('store.product.review', $product->slug), ['rating' => 5, 'body' => 'Grew on me.']);

        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertDatabaseHas('product_reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        $this->assertSame('5.0', (string) $product->fresh()->rating);
    }
}

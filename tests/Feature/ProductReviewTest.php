<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReview;
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

    /**
     * @param  list<int>  $ratings
     */
    private function reviewsFor(Product $product, array $ratings): void
    {
        foreach ($ratings as $i => $rating) {
            ProductReview::factory()->create([
                'product_id' => $product->id,
                'rating' => $rating,
                'body' => "Review body {$i}",
                'created_at' => now()->subDays(count($ratings) - $i),
            ]);
        }
        $product->recalculateRating();
    }

    public function test_product_page_previews_the_latest_reviews_and_links_to_all(): void
    {
        $product = $this->product();
        $this->reviewsFor($product, [5, 4, 5, 3, 5]);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee('Review body 4')
            ->assertSee('Review body 2')
            ->assertDontSee('Review body 1')
            ->assertDontSee('Review body 0')
            ->assertSee('সব ৫টি রিভিউ দেখুন')
            ->assertSee(route('store.product.reviews', $product->slug));
    }

    public function test_product_page_without_reviews_offers_to_write_one(): void
    {
        $product = $this->product();

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee('এখনো কোনো রিভিউ আসেনি')
            ->assertDontSee('সব ০টি রিভিউ দেখুন')
            ->assertSee(route('store.product.reviews', $product->slug).'#write-review');
    }

    public function test_reviews_page_lists_every_review_with_the_breakdown(): void
    {
        $product = $this->product();
        $this->reviewsFor($product, [5, 4, 5, 3, 5]);

        $this->get(route('store.product.reviews', $product->slug))
            ->assertOk()
            ->assertSee('Review body 0')
            ->assertSee('Review body 4')
            ->assertSee('সব · ৫')
            ->assertSee('৫ ★ · ৩')
            ->assertViewHas('ratingBreakdown', fn ($breakdown) => $breakdown->all() === [5 => 3, 4 => 1, 3 => 1, 2 => 0, 1 => 0]);
    }

    public function test_reviews_page_filters_by_star_and_paginates(): void
    {
        $product = $this->product();
        $this->reviewsFor($product, array_fill(0, 12, 5) + [12 => 2]);

        $this->get(route('store.product.reviews', [$product->slug, 'rating' => 2]))
            ->assertOk()
            ->assertSee('Review body 12')
            ->assertDontSee('Review body 0');

        $this->get(route('store.product.reviews', $product->slug))
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 10 && $reviews->total() === 13)
            ->assertSee('পরবর্তী');
    }

    public function test_posting_from_the_reviews_page_returns_there(): void
    {
        $product = $this->product();

        $this->actingAs(User::factory()->create(), 'web')
            ->post(route('store.product.review', $product->slug), ['rating' => 5, 'body' => 'Great', 'from' => 'reviews'])
            ->assertRedirect(route('store.product.reviews', $product->slug));
    }

    public function test_reviews_page_404s_for_inactive_products(): void
    {
        $product = $this->product();
        $product->update(['is_active' => false]);

        $this->get(route('store.product.reviews', $product->slug))->assertNotFound();
    }
}

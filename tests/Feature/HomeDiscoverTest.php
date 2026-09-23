<?php

namespace Tests\Feature;

use App\Services\CatalogService;
use Database\Seeders\AttributeSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeDiscoverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CategorySeeder::class, AttributeSeeder::class, ProductSeeder::class]);
    }

    public function test_home_shows_four_discover_products_that_are_not_best_sellers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('দেখতে থাকুন')
            ->assertViewHas('discoverSeed')
            ->assertViewHas('discover', function (array $discover): bool {
                $bestSellerIds = $this->app->make(CatalogService::class)->bestSellers(4)->modelKeys();

                return $discover['products']->count() === 4
                    && $discover['products']->pluck('id')->intersect($bestSellerIds)->isEmpty();
            });
    }

    public function test_load_more_returns_the_next_four_cards(): void
    {
        $this->getJson(route('store.discover', ['seed' => 42, 'offset' => 0]))
            ->assertOk()
            ->assertJson(['count' => 4, 'has_more' => true])
            ->assertJsonPath('html', fn (string $html): bool => substr_count($html, '/product/') >= 4);
    }

    public function test_load_more_reports_the_last_page(): void
    {
        $this->getJson(route('store.discover', ['seed' => 42, 'offset' => 4]))
            ->assertOk()
            ->assertJson(['count' => 2, 'has_more' => false]);
    }

    public function test_load_more_requires_a_seed_and_offset(): void
    {
        $this->getJson(route('store.discover'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['seed', 'offset']);
    }
}

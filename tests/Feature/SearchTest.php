<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_empty_query_shows_no_results_and_no_error(): void
    {
        $this->get(route('search.index'))->assertOk()->assertSee('عبارت مورد نظر');
    }

    public function test_search_finds_matching_active_products_only(): void
    {
        $match = Product::create(['sku' => 'S1', 'slug' => 'p1', 'name' => 'برس صنعتی ویژه', 'type' => 'quote', 'is_active' => true]);
        $inactiveMatch = Product::create(['sku' => 'S2', 'slug' => 'p2', 'name' => 'برس صنعتی غیرفعال', 'type' => 'quote', 'is_active' => false]);
        $noMatch = Product::create(['sku' => 'S3', 'slug' => 'p3', 'name' => 'فرچه نظافتی', 'type' => 'quote', 'is_active' => true]);

        $response = $this->get(route('search.index', ['q' => 'صنعتی']));

        $response->assertOk();
        $response->assertSee($match->name);
        $response->assertDontSee($inactiveMatch->name);
        $response->assertDontSee($noMatch->name);
    }

    public function test_category_page_can_be_searched_and_sorted(): void
    {
        $category = ProductCategory::create(['name' => 'دسته', 'slug' => 'cat', 'is_active' => true]);
        Product::create(['sku' => 'A', 'slug' => 'a', 'name' => 'آلفا', 'type' => 'quote', 'is_active' => true, 'primary_category_id' => $category->id]);
        Product::create(['sku' => 'B', 'slug' => 'b', 'name' => 'بتا', 'type' => 'quote', 'is_active' => true, 'primary_category_id' => $category->id]);

        $response = $this->get(route('categories.show', ['category' => $category, 'sort' => 'name']));
        $response->assertOk();

        $response = $this->get(route('categories.show', ['category' => $category, 'q' => 'آلفا']));
        $response->assertOk()->assertSee('آلفا')->assertDontSee('بتا');
    }
}

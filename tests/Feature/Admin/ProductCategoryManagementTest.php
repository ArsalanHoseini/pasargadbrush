<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_manage_categories(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('admin.login'));
    }

    public function test_staff_can_create_a_category_with_auto_generated_persian_slug(): void
    {
        $this->actingAs($this->staff())->post(route('admin.categories.store'), [
            'name' => 'برس صنعتی',
            'is_active' => '1',
        ])->assertRedirect(route('admin.categories.index'));

        $category = ProductCategory::first();
        $this->assertSame('برس-صنعتی', $category->slug);
        $this->assertTrue($category->is_active);
    }

    public function test_slug_must_be_unique(): void
    {
        ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'brush']);

        $this->actingAs($this->staff())->post(route('admin.categories.store'), [
            'name' => 'برس دیگر',
            'slug' => 'brush',
        ])->assertSessionHasErrors('slug');
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'brush']);
        Product::create([
            'sku' => 'SKU-1', 'slug' => 'p1', 'name' => 'محصول', 'type' => 'quote',
            'primary_category_id' => $category->id,
        ]);

        $this->actingAs($this->staff())->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'brush']);

        $this->actingAs($this->staff())->delete(route('admin.categories.destroy', $category));

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }
}

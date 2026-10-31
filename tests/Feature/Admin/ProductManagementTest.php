<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_staff_can_create_a_quote_product(): void
    {
        $category = ProductCategory::create(['name' => 'برس صنعتی', 'slug' => 'brush']);

        $this->actingAs($this->staff())->post(route('admin.products.store'), [
            'name' => 'برس تست',
            'sku' => 'SKU-100',
            'primary_category_id' => $category->id,
            'type' => 'quote',
            'is_active' => '1',
        ])->assertRedirect();

        $product = Product::first();
        $this->assertSame('quote', $product->type);
        $this->assertSame($category->id, $product->primary_category_id);
        $this->assertNotNull($product->published_at);
    }

    public function test_staff_can_mark_a_product_featured_with_a_sort_order(): void
    {
        $this->actingAs($this->staff())->post(route('admin.products.store'), [
            'name' => 'برس تست',
            'sku' => 'SKU-150',
            'type' => 'quote',
            'is_featured' => '1',
            'sort_order' => '3',
        ])->assertRedirect();

        $product = Product::first();
        $this->assertTrue($product->is_featured);
        $this->assertSame(3, $product->sort_order);
    }

    public function test_staff_can_create_a_priced_product_with_variants(): void
    {
        $this->actingAs($this->staff())->post(route('admin.products.store'), [
            'name' => 'برس تست',
            'sku' => 'SKU-200',
            'type' => 'priced',
            'variants' => [
                ['label' => '۴۰x۶۰', 'price_toman' => 150000, 'stock_quantity' => 10, 'is_default' => '1'],
                ['label' => '۸۰x۱۲۰', 'price_toman' => 300000],
            ],
        ])->assertRedirect();

        $product = Product::first();
        $this->assertSame(2, $product->variants()->count());

        $variant = $product->variants()->where('label', '۴۰x۶۰')->first();
        $this->assertSame(1500000, $variant->price_rial); // 150,000 Toman stored as 1,500,000 Rial
        $this->assertTrue($variant->is_default);
    }

    public function test_updating_variants_removes_deleted_rows_and_keeps_others(): void
    {
        $product = Product::create(['sku' => 'SKU-300', 'slug' => 'p300', 'name' => 'محصول', 'type' => 'priced']);
        $keep = ProductVariant::create(['product_id' => $product->id, 'sku' => 'V1', 'label' => 'A', 'price_rial' => 1000]);
        $remove = ProductVariant::create(['product_id' => $product->id, 'sku' => 'V2', 'label' => 'B', 'price_rial' => 2000]);

        $this->actingAs($this->staff())->put(route('admin.products.update', $product), [
            'name' => 'محصول', 'sku' => 'SKU-300', 'type' => 'priced',
            'variants' => [
                ['id' => $keep->id, 'label' => 'A (ویرایش‌شده)', 'price_toman' => 500],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('product_variants', ['id' => $keep->id, 'label' => 'A (ویرایش‌شده)']);
        $this->assertDatabaseMissing('product_variants', ['id' => $remove->id]);
    }

    public function test_images_and_categories_are_synced(): void
    {
        $primary = ProductCategory::create(['name' => 'اصلی', 'slug' => 'main']);
        $secondary = ProductCategory::create(['name' => 'ثانویه', 'slug' => 'secondary']);
        $media1 = Media::create(['disk' => 'public', 'path' => 'a.jpg', 'mime_type' => 'image/jpeg', 'size' => 10]);
        $media2 = Media::create(['disk' => 'public', 'path' => 'b.jpg', 'mime_type' => 'image/jpeg', 'size' => 10]);

        $this->actingAs($this->staff())->post(route('admin.products.store'), [
            'name' => 'محصول', 'sku' => 'SKU-400', 'type' => 'quote',
            'primary_category_id' => $primary->id,
            'category_ids' => [$secondary->id],
            'image_ids' => [$media1->id, $media2->id],
        ]);

        $product = Product::first();
        $this->assertTrue($product->categories->contains($secondary));
        $this->assertCount(2, $product->images);
    }

    public function test_specifications_are_saved_as_key_value_pairs_and_blanks_are_ignored(): void
    {
        $this->actingAs($this->staff())->post(route('admin.products.store'), [
            'name' => 'محصول', 'sku' => 'SKU-500', 'type' => 'quote',
            'specifications' => [
                ['key' => 'قطر', 'value' => '۲۰ سانتی‌متر'],
                ['key' => '', 'value' => ''],
                ['key' => 'طول', 'value' => ''],
            ],
        ]);

        $product = Product::first();
        $this->assertSame(['قطر' => '۲۰ سانتی‌متر'], $product->specifications);
    }

    public function test_sku_must_be_unique(): void
    {
        Product::create(['sku' => 'DUPLICATE', 'slug' => 'p1', 'name' => 'محصول یک', 'type' => 'quote']);

        $this->actingAs($this->staff())->post(route('admin.products.store'), [
            'name' => 'محصول دو', 'sku' => 'DUPLICATE', 'type' => 'quote',
        ])->assertSessionHasErrors('sku');
    }

    public function test_a_priced_product_cannot_have_a_zero_price_variant(): void
    {
        $this->actingAs($this->staff())->post(route('admin.products.store'), [
            'name' => 'محصول', 'sku' => 'SKU-ZERO', 'type' => 'priced',
            'variants' => [['label' => 'استاندارد', 'price_toman' => 0]],
        ])->assertSessionHasErrors('variants.0.price_toman');

        $this->assertSame(0, Product::count());
    }

    public function test_a_quote_product_may_carry_a_zero_price_variant(): void
    {
        $this->actingAs($this->staff())->post(route('admin.products.store'), [
            'name' => 'محصول', 'sku' => 'SKU-QUOTE', 'type' => 'quote',
            'variants' => [['label' => 'استاندارد', 'price_toman' => 0]],
        ])->assertSessionHasNoErrors();
    }
}

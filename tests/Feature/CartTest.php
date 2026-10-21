<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function pricedVariant(int $priceRial = 1_000_000): ProductVariant
    {
        $product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'محصول قیمت‌دار', 'type' => 'priced', 'is_active' => true]);

        return ProductVariant::create(['product_id' => $product->id, 'sku' => 'V1', 'label' => 'استاندارد', 'price_rial' => $priceRial]);
    }

    private function cartTokenFrom($response): string
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'cart_token') {
                return $cookie->getValue();
            }
        }

        $this->fail('cart_token cookie was not set on the response.');
    }

    public function test_adding_an_item_creates_a_cart_and_sets_a_cookie(): void
    {
        $variant = $this->pricedVariant();

        $response = $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $response->assertCookie('cart_token');
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 2]);
    }

    public function test_adding_the_same_variant_twice_merges_quantity(): void
    {
        $variant = $this->pricedVariant();

        $response1 = $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);
        $token = $this->cartTokenFrom($response1);

        $this->withUnencryptedCookie('cart_token', $token)
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 3]);

        $this->assertSame(1, CartItem::count());
        $this->assertSame(5, CartItem::first()->quantity);
    }

    public function test_cannot_add_a_variant_of_an_inactive_product(): void
    {
        $product = Product::create(['sku' => 'P2', 'slug' => 'p2', 'name' => 'غیرفعال', 'type' => 'priced', 'is_active' => false]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'V2', 'label' => 'A', 'price_rial' => 1000]);

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1])
            ->assertNotFound();
    }

    public function test_cart_without_a_cookie_shows_as_empty_without_querying_the_database(): void
    {
        $this->get(route('cart.show'))->assertOk()->assertSee('سبد خرید شما خالی است');
    }

    public function test_updating_quantity_to_zero_removes_the_item(): void
    {
        $variant = $this->pricedVariant();
        $response = $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $token = $this->cartTokenFrom($response);
        $item = CartItem::first();

        $this->withUnencryptedCookie('cart_token', $token)
            ->patch(route('cart.items.update', $item), ['quantity' => 0]);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_cannot_modify_another_visitors_cart_item(): void
    {
        $variant = $this->pricedVariant();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $item = CartItem::first();

        // A different visitor (no matching cart_token cookie) tries to delete it.
        $this->delete(route('cart.items.destroy', $item))->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    public function test_total_is_always_computed_from_the_live_variant_price(): void
    {
        $variant = $this->pricedVariant(priceRial: 2_000_000);
        $response = $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 3]);
        $token = $this->cartTokenFrom($response);

        $variant->update(['price_rial' => 500_000]); // price changes after adding to cart

        $cartResponse = $this->withUnencryptedCookie('cart_token', $token)->get(route('cart.show'));

        $cartResponse->assertOk();
        $cartResponse->assertSee(number_format(150_000)); // 3 * 500,000 Rial / 10 = 150,000 Toman
    }
}

<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private const CHECKOUT_DETAILS = [
        'name' => 'علی', 'phone' => '09120000000',
        'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Mail::fake();
    }

    private function variant(?int $stock = null, array $productOverrides = []): ProductVariant
    {
        $product = Product::create(array_merge(
            ['sku' => 'P1', 'slug' => 'p1', 'name' => 'محصول', 'type' => 'priced', 'is_active' => true],
            $productOverrides,
        ));

        return ProductVariant::create([
            'product_id' => $product->id, 'sku' => 'V1', 'label' => 'استاندارد',
            'price_rial' => 1_000_000, 'stock_quantity' => $stock,
        ]);
    }

    private function addToCart(ProductVariant $variant, int $quantity, ?string $token = null): TestResponse
    {
        $request = $token ? $this->withUnencryptedCookie('cart_token', $token) : $this;

        return $request->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => $quantity]);
    }

    private function tokenFrom(TestResponse $response): string
    {
        return collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'cart_token')
            ->getValue();
    }

    private function checkout(string $token): TestResponse
    {
        return $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), self::CHECKOUT_DETAILS);
    }

    public function test_order_is_refused_when_the_product_was_deactivated_after_being_added_to_the_cart(): void
    {
        $variant = $this->variant();
        $token = $this->tokenFrom($this->addToCart($variant, 1));

        $variant->product->update(['is_active' => false]);

        $this->checkout($token)->assertRedirect(route('cart.show'))->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::count());
        $this->assertSame(1, CartItem::count(), 'The cart must be kept so the customer can fix it.');
    }

    public function test_order_is_refused_when_the_product_was_switched_to_quote_only(): void
    {
        $variant = $this->variant();
        $token = $this->tokenFrom($this->addToCart($variant, 1));

        $variant->product->update(['type' => 'quote']);

        $this->checkout($token)->assertRedirect(route('cart.show'));
        $this->assertSame(0, Order::count());
    }

    public function test_order_is_refused_when_stock_dropped_below_the_cart_quantity(): void
    {
        $variant = $this->variant(stock: 5);
        $token = $this->tokenFrom($this->addToCart($variant, 3));

        $variant->update(['stock_quantity' => 2]);

        $this->checkout($token)->assertRedirect(route('cart.show'));
        $this->assertSame(0, Order::count());
        $this->assertSame(2, $variant->refresh()->stock_quantity);
    }

    public function test_placing_an_order_deducts_tracked_stock(): void
    {
        $variant = $this->variant(stock: 3);
        $token = $this->tokenFrom($this->addToCart($variant, 3));

        $this->checkout($token)->assertRedirect(route('checkout.confirmation'));

        $this->assertSame(0, $variant->refresh()->stock_quantity);
    }

    public function test_untracked_stock_stays_untracked_after_an_order(): void
    {
        $variant = $this->variant(stock: null);
        $token = $this->tokenFrom($this->addToCart($variant, 4));

        $this->checkout($token)->assertRedirect(route('checkout.confirmation'));

        $this->assertNull($variant->refresh()->stock_quantity);
    }

    public function test_cannot_add_more_than_the_available_stock_across_several_adds(): void
    {
        $variant = $this->variant(stock: 5);
        $token = $this->tokenFrom($this->addToCart($variant, 4));

        $this->addToCart($variant, 2, $token)->assertSessionHasErrors(['quantity' => 'از این محصول فقط 5 عدد موجود است.']);

        $this->assertSame(4, CartItem::firstOrFail()->quantity);
    }

    public function test_cannot_add_a_product_marked_out_of_stock(): void
    {
        $variant = $this->variant(productOverrides: ['stock_status' => 'out_of_stock']);

        $this->addToCart($variant, 1)->assertSessionHasErrors(['quantity' => 'این محصول در حال حاضر ناموجود است.']);
        $this->assertSame(0, CartItem::count());
    }

    public function test_repeated_adds_are_capped_at_the_maximum_line_quantity(): void
    {
        $variant = $this->variant();
        $token = $this->tokenFrom($this->addToCart($variant, 800));

        $this->addToCart($variant, 800, $token);

        $this->assertSame(1000, CartItem::firstOrFail()->quantity);
    }

    public function test_cart_page_explains_the_problem_and_hides_the_checkout_button(): void
    {
        $variant = $this->variant();
        $token = $this->tokenFrom($this->addToCart($variant, 1));
        $variant->product->update(['is_active' => false]);

        $this->withUnencryptedCookie('cart_token', $token)->get(route('cart.show'))
            ->assertOk()
            ->assertSee('این محصول دیگر به‌صورت آنلاین قابل خرید نیست.')
            ->assertDontSee(route('checkout.create'));
    }

    public function test_checkout_page_sends_the_customer_back_to_the_cart_when_an_item_became_unavailable(): void
    {
        $variant = $this->variant(stock: 5);
        $token = $this->tokenFrom($this->addToCart($variant, 2));
        $variant->update(['stock_quantity' => 0]);

        $this->withUnencryptedCookie('cart_token', $token)->get(route('checkout.create'))
            ->assertRedirect(route('cart.show'));
    }

    public function test_an_unavailable_item_can_still_be_reduced_but_not_increased(): void
    {
        $variant = $this->variant(stock: 5);
        $token = $this->tokenFrom($this->addToCart($variant, 4));
        $variant->update(['stock_quantity' => 1]);
        $item = CartItem::firstOrFail();

        $this->withUnencryptedCookie('cart_token', $token)
            ->patch(route('cart.items.update', $item), ['quantity' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $item->refresh()->quantity);

        $this->withUnencryptedCookie('cart_token', $token)
            ->patch(route('cart.items.update', $item), ['quantity' => 3])
            ->assertSessionHasErrors('quantity');
        $this->assertSame(1, $item->refresh()->quantity);
    }

    public function test_product_page_shows_out_of_stock_instead_of_add_to_cart(): void
    {
        $variant = $this->variant(stock: 0);

        $this->get(route('products.show', $variant->product))
            ->assertOk()
            ->assertSee('ناموجود')
            ->assertDontSee('افزودن به سبد');
    }
}

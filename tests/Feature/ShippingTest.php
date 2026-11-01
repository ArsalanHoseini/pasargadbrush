<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\Checkout\ShippingCalculator;
use App\Services\Checkout\ShippingMethod;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Mail::fake();
    }

    private function configureShipping(ShippingMethod $method, ?int $flatRateRial = null, ?int $freeThresholdRial = null): void
    {
        Setting::updateOrCreate(['key' => ShippingCalculator::METHOD_SETTING], ['value' => $method->value]);
        Setting::updateOrCreate(['key' => ShippingCalculator::FLAT_RATE_SETTING], ['value' => $flatRateRial]);
        Setting::updateOrCreate(['key' => ShippingCalculator::FREE_THRESHOLD_SETTING], ['value' => $freeThresholdRial]);
    }

    /**
     * Adds 2 × 1,000,000 Rial (subtotal 2,000,000 Rial = 200,000 Toman) to a new cart.
     */
    private function cartToken(): string
    {
        $product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'محصول', 'type' => 'priced', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'V1', 'label' => 'استاندارد', 'price_rial' => 1_000_000]);

        $response = $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);

        return collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'cart_token')->getValue();
    }

    private function placeOrder(string $token): Order
    {
        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
            'name' => 'علی', 'phone' => '09120000000',
            'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
        ])->assertRedirect(route('checkout.confirmation'));

        return Order::firstOrFail();
    }

    public function test_without_any_configuration_shipping_is_announced_later(): void
    {
        $order = $this->placeOrder($this->cartToken());

        $this->assertSame(ShippingMethod::Announced->value, $order->shipping_method);
        $this->assertSame(0, $order->shipping_cost_rial);
        $this->assertSame(2_000_000, $order->total_rial);
    }

    public function test_flat_rate_is_added_to_the_order_and_shown_as_a_grand_total(): void
    {
        $this->configureShipping(ShippingMethod::Flat, flatRateRial: 500_000);
        $token = $this->cartToken();

        $this->withUnencryptedCookie('cart_token', $token)->get(route('cart.show'))
            ->assertSee('50,000 تومان')
            ->assertSee('جمع کل')
            ->assertSee('250,000 تومان');

        $order = $this->placeOrder($token);

        $this->assertSame(ShippingMethod::Flat->value, $order->shipping_method);
        $this->assertSame(500_000, $order->shipping_cost_rial);
        $this->assertSame(2_500_000, $order->total_rial);
    }

    public function test_flat_rate_is_free_at_or_above_the_threshold(): void
    {
        $this->configureShipping(ShippingMethod::Flat, flatRateRial: 500_000, freeThresholdRial: 2_000_000);
        $token = $this->cartToken();

        $this->withUnencryptedCookie('cart_token', $token)->get(route('checkout.create'))->assertSee('رایگان');

        $order = $this->placeOrder($token);

        $this->assertSame(0, $order->shipping_cost_rial);
        $this->assertSame(2_000_000, $order->total_rial);
    }

    public function test_flat_rate_still_applies_below_the_threshold(): void
    {
        $this->configureShipping(ShippingMethod::Flat, flatRateRial: 500_000, freeThresholdRial: 3_000_000);

        $order = $this->placeOrder($this->cartToken());

        $this->assertSame(500_000, $order->shipping_cost_rial);
    }

    public function test_cash_on_delivery_charges_nothing_and_says_so(): void
    {
        $this->configureShipping(ShippingMethod::CashOnDelivery);
        $token = $this->cartToken();

        $this->withUnencryptedCookie('cart_token', $token)->get(route('checkout.create'))
            ->assertSee('پس‌کرایه')
            ->assertSee('جمع کل');

        $order = $this->placeOrder($token);

        $this->assertSame(ShippingMethod::CashOnDelivery->value, $order->shipping_method);
        $this->assertSame(0, $order->shipping_cost_rial);
        $this->get(route('checkout.confirmation'))->assertSee('پس‌کرایه');
    }

    public function test_changing_the_settings_later_does_not_change_an_existing_order(): void
    {
        $this->configureShipping(ShippingMethod::Flat, flatRateRial: 500_000);
        $order = $this->placeOrder($this->cartToken());

        $this->configureShipping(ShippingMethod::CashOnDelivery);

        $quote = $order->refresh()->shippingQuote();
        $this->assertSame(ShippingMethod::Flat, $quote->method);
        $this->assertSame(500_000, $quote->costRial);
    }
}

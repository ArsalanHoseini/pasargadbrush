<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
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

    private function addToCart(int $priceRial = 1_000_000, int $quantity = 2): string
    {
        $product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'محصول', 'type' => 'priced', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'V1', 'label' => 'استاندارد', 'price_rial' => $priceRial]);

        $response = $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => $quantity]);

        return $this->cartTokenFrom($response);
    }

    public function test_checkout_page_redirects_when_cart_is_empty(): void
    {
        $this->get(route('checkout.create'))->assertRedirect(route('cart.show'));
    }

    public function test_submitting_checkout_creates_a_real_order_with_correct_totals(): void
    {
        $token = $this->addToCart(priceRial: 1_000_000, quantity: 2);

        $response = $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
            'name' => 'علی رضایی',
            'phone' => '09120000000',
            'province' => 'فارس',
            'city' => 'مرودشت',
            'address_line' => 'خیابان اصلی، پلاک ۱',
        ]);

        $response->assertRedirect(route('checkout.confirmation'));

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame(2_000_000, $order->subtotal_rial);
        $this->assertSame(0, $order->shipping_cost_rial);
        $this->assertSame(2_000_000, $order->total_rial);
        $this->assertSame('pending', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(1, $order->items()->count());
    }

    public function test_checkout_reuses_an_existing_customer_without_overwriting_their_details(): void
    {
        $existing = Customer::create(['name' => 'نام قدیمی', 'phone' => '09120000000', 'email' => 'old@example.com']);
        $token = $this->addToCart();

        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
            'name' => 'نام جدید', 'phone' => '09120000000', 'email' => 'new@example.com',
            'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
        ]);

        $this->assertSame(1, Customer::count());
        $existing->refresh();
        $this->assertSame('نام قدیمی', $existing->name);
        $this->assertSame('old@example.com', $existing->email);

        $order = Order::firstOrFail();
        $this->assertTrue($order->customer->is($existing));
        $this->assertSame('نام جدید', $order->customer_name);
        $this->assertSame('09120000000', $order->customer_phone);
        $this->assertSame('new@example.com', $order->customer_email);
    }

    public function test_the_same_mobile_written_differently_is_one_customer(): void
    {
        $existing = Customer::create(['name' => 'علی', 'phone' => '09121234567']);
        $this->addToCart();
        $variant = ProductVariant::firstOrFail();

        foreach (['۰۹۱۲۱۲۳۴۵۶۷', '+98 912 123 4567'] as $spelling) {
            $token = $this->cartTokenFrom(
                $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1])
            );

            $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
                'name' => 'علی', 'phone' => $spelling,
                'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
            ])->assertRedirect(route('checkout.confirmation'));
        }

        $this->assertSame(1, Customer::count());
        $this->assertSame(2, $existing->orders()->count());
        $this->assertSame(['09121234567'], Order::pluck('customer_phone')->unique()->values()->all());
    }

    public function test_an_invalid_mobile_number_is_rejected(): void
    {
        $token = $this->addToCart();

        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
            'name' => 'علی', 'phone' => '07143334444',
            'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
        ])->assertSessionHasErrors(['phone' => 'شماره موبایل معتبر نیست — مثال: ۰۹۱۲۱۲۳۴۵۶۷']);

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_empties_the_cart_after_order_is_placed(): void
    {
        $token = $this->addToCart();

        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
            'name' => 'علی', 'phone' => '09120000000',
            'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
        ]);

        $cartResponse = $this->withUnencryptedCookie('cart_token', $token)->get(route('cart.show'));
        $cartResponse->assertSee('سبد خرید شما خالی است');
    }

    public function test_required_fields_are_validated(): void
    {
        $token = $this->addToCart();

        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [])
            ->assertSessionHasErrors(['name', 'phone', 'province', 'city', 'address_line']);

        $this->assertSame(0, Order::count());
    }

    public function test_validation_errors_are_shown_in_persian(): void
    {
        $token = $this->addToCart();

        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors([
                'phone' => 'وارد کردن شماره تماس الزامی است.',
                'address_line' => 'وارد کردن آدرس الزامی است.',
                'email' => 'ایمیل واردشده معتبر نیست.',
            ]);
    }

    public function test_confirmation_page_is_not_accessible_without_a_real_completed_checkout(): void
    {
        $this->get(route('checkout.confirmation'))->assertRedirect(route('home'));
    }

    public function test_confirmation_page_shows_the_real_order_after_checkout(): void
    {
        $token = $this->addToCart(priceRial: 1_500_000, quantity: 1);

        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
            'name' => 'علی', 'phone' => '09120000000',
            'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
        ]);

        $order = Order::first();

        $this->get(route('checkout.confirmation'))
            ->assertOk()
            ->assertSee($order->order_number);
    }
}

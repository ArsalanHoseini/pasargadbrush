<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function order(array $overrides = []): Order
    {
        $customer = Customer::create(['name' => 'علی رضایی', 'phone' => '09121234567']);
        $product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'برس', 'type' => 'priced', 'is_active' => true]);

        $order = Order::create(array_merge([
            'order_number' => 'PB-20261031-ABC123',
            'customer_id' => $customer->id,
            'customer_name' => 'علی رضایی',
            'customer_phone' => '09121234567',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal_rial' => 2_000_000,
            'shipping_cost_rial' => 0,
            'total_rial' => 2_000_000,
            'placed_at' => '2026-10-31 10:00:00',
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id, 'name_snapshot' => 'برس — استاندارد', 'sku_snapshot' => 'V1',
            'unit_price_rial' => 1_000_000, 'quantity' => 2, 'line_total_rial' => 2_000_000,
        ]);

        return $order;
    }

    private function track(string $orderNumber, string $phone)
    {
        return $this->post(route('orders.track.store'), ['order_number' => $orderNumber, 'phone' => $phone]);
    }

    public function test_tracking_form_is_reachable_from_the_footer(): void
    {
        $this->get(route('home'))->assertSee(route('orders.track.create'));
        $this->get(route('orders.track.create'))->assertOk()->assertSee('پیگیری سفارش');
    }

    public function test_correct_order_number_and_mobile_show_the_order(): void
    {
        $this->order();

        $this->track('PB-20261031-ABC123', '09121234567')->assertRedirect(route('orders.track.show'));

        $this->get(route('orders.track.show'))
            ->assertOk()
            ->assertSee('PB-20261031-ABC123')
            ->assertSee('برس — استاندارد')
            ->assertSee('ثبت شد')
            ->assertSee('پرداخت‌نشده')
            ->assertSee('۹ آبان ۱۴۰۵');
    }

    public function test_lookup_accepts_persian_digits_lowercase_and_other_mobile_spellings(): void
    {
        $this->order();

        $this->track(' pb-۲۰۲۶۱۰۳۱-abc123 ', '+98 912 123 4567')->assertRedirect(route('orders.track.show'));
    }

    public function test_a_wrong_mobile_reveals_nothing(): void
    {
        $this->order();

        $this->track('PB-20261031-ABC123', '09129999999')->assertSessionHasErrors('order_number');

        $this->get(route('orders.track.show'))->assertRedirect(route('orders.track.create'));
    }

    public function test_a_wrong_order_number_reveals_nothing(): void
    {
        $this->order();

        $this->track('PB-20261031-XXXXXX', '09121234567')->assertSessionHasErrors('order_number');
    }

    public function test_status_page_requires_a_successful_lookup_first(): void
    {
        $this->order();

        $this->get(route('orders.track.show'))->assertRedirect(route('orders.track.create'));
    }

    public function test_a_shipped_order_shows_its_parcel_tracking_code(): void
    {
        $this->order(['status' => 'shipped', 'shipment_tracking_code' => '123456789012345678']);

        $this->track('PB-20261031-ABC123', '09121234567');

        $this->get(route('orders.track.show'))
            ->assertSee('ارسال شد')
            ->assertSee('123456789012345678');
    }

    public function test_once_staff_set_a_shipping_cost_the_full_total_is_shown(): void
    {
        $this->order(['shipping_cost_rial' => 500_000, 'total_rial' => 2_500_000]);

        $this->track('PB-20261031-ABC123', '09121234567');

        $this->get(route('orders.track.show'))
            ->assertSee('50,000 تومان')
            ->assertSee('جمع کل')
            ->assertSee('250,000 تومان')
            ->assertDontSee('پس از هماهنگی اعلام می‌شود');
    }

    public function test_without_a_shipping_cost_the_page_says_it_will_be_announced(): void
    {
        $this->order();

        $this->track('PB-20261031-ABC123', '09121234567');

        $this->get(route('orders.track.show'))
            ->assertSee('پس از هماهنگی اعلام می‌شود')
            ->assertDontSee('جمع کل');
    }

    public function test_a_cancelled_order_says_so_instead_of_showing_progress(): void
    {
        $this->order(['status' => 'cancelled']);

        $this->track('PB-20261031-ABC123', '09121234567');

        $this->get(route('orders.track.show'))
            ->assertSee('این سفارش لغو شده است.')
            ->assertDontSee('در حال آماده‌سازی');
    }

    public function test_lookups_are_rate_limited(): void
    {
        $this->order();

        foreach (range(1, 10) as $attempt) {
            $this->track('PB-20261031-WRONG'.$attempt, '09121234567');
        }

        $this->track('PB-20261031-ABC123', '09121234567')->assertStatus(429);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\ShippingCalculator;
use App\Services\Checkout\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingSettingTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function order(ShippingMethod $method, int $shippingCostRial = 0): Order
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '09120000000']);

        return Order::create([
            'order_number' => 'PB-TEST-'.uniqid(),
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'shipping_method' => $method->value,
            'subtotal_rial' => 2_000_000,
            'shipping_cost_rial' => $shippingCostRial,
            'total_rial' => 2_000_000 + $shippingCostRial,
            'placed_at' => now(),
        ]);
    }

    public function test_guest_cannot_view_shipping_settings(): void
    {
        $this->get(route('admin.shipping.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_page_defaults_to_announced(): void
    {
        $this->actingAs($this->staff())->get(route('admin.shipping.edit'))
            ->assertOk()
            ->assertSee('پس از هماهنگی اعلام می‌شود')
            ->assertSee('value="announced"', false);
    }

    public function test_staff_can_set_a_flat_rate_entered_in_toman_and_stored_in_rial(): void
    {
        $this->actingAs($this->staff())->put(route('admin.shipping.update'), [
            'shipping_method' => 'flat',
            'flat_rate_toman' => 150000,
            'free_threshold_toman' => 5000000,
        ])->assertRedirect(route('admin.shipping.edit'));

        $this->assertSame('flat', setting(ShippingCalculator::METHOD_SETTING));
        $this->assertSame('1500000', setting(ShippingCalculator::FLAT_RATE_SETTING));
        $this->assertSame('50000000', setting(ShippingCalculator::FREE_THRESHOLD_SETTING));
    }

    public function test_flat_rate_requires_an_amount(): void
    {
        $this->actingAs($this->staff())->put(route('admin.shipping.update'), [
            'shipping_method' => 'flat',
        ])->assertSessionHasErrors('flat_rate_toman');
    }

    public function test_an_unknown_method_is_rejected(): void
    {
        $this->actingAs($this->staff())->put(route('admin.shipping.update'), [
            'shipping_method' => 'teleport',
        ])->assertSessionHasErrors('shipping_method');
    }

    public function test_staff_enter_the_agreed_shipping_cost_on_an_announced_order_and_the_total_follows(): void
    {
        $order = $this->order(ShippingMethod::Announced);

        $this->actingAs($this->staff())->get(route('admin.orders.edit', $order))
            ->assertSee('هزینه ارسال این سفارش هنوز وارد نشده');

        $this->actingAs($this->staff())->put(route('admin.orders.update', $order), [
            'status' => 'confirmed', 'payment_status' => 'unpaid', 'shipping_cost_toman' => 80000,
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(800_000, $order->shipping_cost_rial);
        $this->assertSame(2_800_000, $order->total_rial);
        $this->assertTrue($order->shippingQuote()->isTotalKnown());
    }

    public function test_shipping_cost_is_never_added_to_a_cash_on_delivery_order(): void
    {
        $order = $this->order(ShippingMethod::CashOnDelivery);

        $this->actingAs($this->staff())->put(route('admin.orders.update', $order), [
            'status' => 'confirmed', 'payment_status' => 'unpaid', 'shipping_cost_toman' => 80000,
        ]);

        $order->refresh();
        $this->assertSame(0, $order->shipping_cost_rial);
        $this->assertSame(2_000_000, $order->total_rial);
    }

    public function test_leaving_the_shipping_field_empty_keeps_the_current_cost(): void
    {
        $order = $this->order(ShippingMethod::Flat, shippingCostRial: 500_000);

        $this->actingAs($this->staff())->put(route('admin.orders.update', $order), [
            'status' => 'confirmed', 'payment_status' => 'unpaid',
        ]);

        $this->assertSame(500_000, $order->refresh()->shipping_cost_rial);
    }
}

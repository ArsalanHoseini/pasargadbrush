<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_view_customers(): void
    {
        $this->get(route('admin.customers.index'))->assertRedirect(route('admin.login'));
    }

    public function test_staff_can_list_customers_with_order_counts(): void
    {
        $customer = Customer::create(['name' => 'علی رضایی', 'phone' => '09120000000']);
        Order::create([
            'order_number' => 'PB-1', 'customer_id' => $customer->id, 'status' => 'pending', 'payment_status' => 'unpaid',
            'subtotal_rial' => 1000, 'shipping_cost_rial' => 0, 'total_rial' => 1000, 'placed_at' => now(),
        ]);

        $response = $this->actingAs($this->staff())->get(route('admin.customers.index'));

        $response->assertOk()->assertSee('علی رضایی');
    }

    public function test_staff_can_view_a_customer_with_their_orders_and_addresses(): void
    {
        $customer = Customer::create(['name' => 'علی رضایی', 'phone' => '09120000000']);
        $customer->addresses()->create([
            'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس تست',
            'recipient_name' => 'علی', 'recipient_phone' => '09120000000',
        ]);
        $order = Order::create([
            'order_number' => 'PB-2', 'customer_id' => $customer->id, 'status' => 'pending', 'payment_status' => 'unpaid',
            'subtotal_rial' => 5000, 'shipping_cost_rial' => 0, 'total_rial' => 5000, 'placed_at' => now(),
        ]);

        $response = $this->actingAs($this->staff())->get(route('admin.customers.show', $customer));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee('آدرس تست');
    }
}

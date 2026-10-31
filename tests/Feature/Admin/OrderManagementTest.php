<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function order(string $status = 'pending'): Order
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '0912'.random_int(1000, 9999)]);

        return Order::create([
            'order_number' => 'PB-TEST-'.uniqid(),
            'customer_id' => $customer->id,
            'status' => $status,
            'payment_status' => 'unpaid',
            'subtotal_rial' => 1000,
            'shipping_cost_rial' => 0,
            'total_rial' => 1000,
            'placed_at' => now(),
        ]);
    }

    public function test_order_page_shows_the_contact_details_entered_on_the_order_and_flags_a_name_mismatch(): void
    {
        $order = $this->order();
        $order->update(['customer_name' => 'شخص دیگر', 'customer_phone' => $order->customer->phone]);

        $this->actingAs($this->staff())->get(route('admin.orders.edit', $order))
            ->assertOk()
            ->assertSee('شخص دیگر')
            ->assertSee('یکی نیست');
    }

    public function test_order_page_shows_no_mismatch_warning_when_names_match(): void
    {
        $order = $this->order();
        $order->update(['customer_name' => $order->customer->name]);

        $this->actingAs($this->staff())->get(route('admin.orders.edit', $order))
            ->assertOk()
            ->assertDontSee('یکی نیست');
    }

    public function test_guest_cannot_view_orders(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect(route('admin.login'));
    }

    public function test_staff_can_filter_orders_by_status(): void
    {
        $pending = $this->order('pending');
        $completed = $this->order('completed');

        $response = $this->actingAs($this->staff())->get(route('admin.orders.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertSee($pending->order_number);
        $response->assertDontSee($completed->order_number);
    }

    public function test_staff_can_update_order_status_and_an_audit_log_is_recorded(): void
    {
        $order = $this->order();
        $staff = $this->staff();

        $this->actingAs($staff)->put(route('admin.orders.update', $order), [
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ])->assertRedirect(route('admin.orders.index'));

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $order->payment_status);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id,
            'action' => 'order.updated',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_no_audit_log_is_recorded_when_nothing_actually_changes(): void
    {
        $order = $this->order('pending');

        $this->actingAs($this->staff())->put(route('admin.orders.update', $order), [
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $this->assertSame(0, AuditLog::count());
    }
}

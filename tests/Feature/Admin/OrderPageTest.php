<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPageTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->staff = User::factory()->create(['role' => 'admin', 'name' => 'مریم کارشناس']);
    }

    private function order(): Order
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '09121234567']);
        $address = Address::create(['customer_id' => $customer->id, 'province' => 'فارس', 'city' => 'شیراز', 'address_line' => 'خیابان زند', 'postal_code' => '7134567890', 'recipient_name' => 'علی', 'recipient_phone' => '09121234567']);
        $product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'برس سیلندری', 'type' => 'priced']);

        $order = $customer->orders()->create([
            'order_number' => 'PB-20261114-TEST01', 'customer_name' => 'علی', 'customer_phone' => '09121234567',
            'status' => 'pending', 'payment_status' => 'unpaid', 'shipping_method' => 'flat',
            'shipping_address_id' => $address->id,
            'subtotal_rial' => 2_000_000, 'shipping_cost_rial' => 500_000, 'total_rial' => 2_500_000, 'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id, 'name_snapshot' => 'برس سیلندری', 'sku_snapshot' => 'P1-A',
            'unit_price_rial' => 1_000_000, 'quantity' => 2, 'line_total_rial' => 2_000_000,
        ]);

        return $order;
    }

    public function test_staff_can_add_an_internal_note_that_appears_in_the_timeline(): void
    {
        $order = $this->order();

        $this->actingAs($this->staff)
            ->post(route('admin.orders.notes.store', $order), ['body' => 'مشتری تماس گرفت؛ ارسال با باربری.'])
            ->assertRedirect(route('admin.orders.edit', $order).'#timeline');

        $this->assertSame('order.note', AuditLog::sole()->action);

        $this->get(route('admin.orders.edit', $order))
            ->assertOk()
            ->assertSeeInOrder(['تاریخچه و یادداشت‌های داخلی', 'یادداشت داخلی', 'مریم کارشناس', 'مشتری تماس گرفت؛ ارسال با باربری.', 'سفارش توسط مشتری در سایت ثبت شد']);
    }

    public function test_an_empty_note_is_rejected(): void
    {
        $this->actingAs($this->staff)->post(route('admin.orders.notes.store', $this->order()), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, AuditLog::count());
    }

    public function test_timeline_describes_saved_changes_in_persian(): void
    {
        $order = $this->order();

        $this->actingAs($this->staff)->put(route('admin.orders.update', $order), [
            'status' => 'shipped', 'payment_status' => 'paid', 'shipment_tracking_code' => 'TR123', 'notify_customer' => '0',
        ]);

        $this->get(route('admin.orders.edit', $order))
            ->assertSee('به‌روزرسانی سفارش')
            ->assertSee('وضعیت: ارسال‌شده')
            ->assertSee('پرداخت: پرداخت‌شده')
            ->assertSee('کد رهگیری: TR123');
    }

    public function test_printable_invoice_lists_items_totals_and_buyer(): void
    {
        $order = $this->order();

        $this->actingAs($this->staff)->get(route('admin.orders.print', [$order, 'invoice']))
            ->assertOk()
            ->assertSee('فاکتور فروش')
            ->assertSee('PB-20261114-TEST01')
            ->assertSee('برس سیلندری')
            ->assertSee('P1-A')
            ->assertSee('250,000 تومان')
            ->assertSee('window.print()', false);
    }

    public function test_shipping_label_shows_the_recipient_address(): void
    {
        $order = $this->order();

        $this->actingAs($this->staff)->get(route('admin.orders.print', [$order, 'label']))
            ->assertOk()
            ->assertSee('گیرنده')
            ->assertSee('شیراز')
            ->assertSee('7134567890')
            ->assertDontSee('فاکتور فروش');
    }

    public function test_unknown_print_documents_are_not_found(): void
    {
        $this->actingAs($this->staff)->get('/admin/orders/'.$this->order()->id.'/print/receipt')->assertNotFound();
    }

    public function test_order_page_links_to_the_invoice_and_label(): void
    {
        $order = $this->order();

        $this->actingAs($this->staff)->get(route('admin.orders.edit', $order))
            ->assertSee(route('admin.orders.print', [$order, 'invoice']), false)
            ->assertSee(route('admin.orders.print', [$order, 'label']), false);
    }
}

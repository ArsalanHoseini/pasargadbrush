<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-14 12:00'));
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  list<array{0: Product, 1: int, 2: int}>  $lines  product, quantity, line total (Rial)
     */
    private function order(Carbon $placedAt, array $lines, string $status = 'confirmed', string $payment = 'unpaid', ?string $province = null): Order
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '0912'.random_int(1000000, 9999999)]);
        $address = $province ? Address::create(['customer_id' => $customer->id, 'province' => $province, 'city' => 'c', 'address_line' => 'a', 'recipient_name' => 'علی', 'recipient_phone' => '0912']) : null;
        $total = array_sum(array_column($lines, 2));

        $order = $customer->orders()->create([
            'order_number' => 'PB-'.uniqid(), 'status' => $status, 'payment_status' => $payment, 'shipping_address_id' => $address?->id,
            'subtotal_rial' => $total, 'shipping_cost_rial' => 0, 'total_rial' => $total, 'placed_at' => $placedAt,
        ]);

        foreach ($lines as [$product, $quantity, $lineTotal]) {
            $order->items()->create(['product_id' => $product->id, 'name_snapshot' => $product->name, 'sku_snapshot' => $product->sku, 'unit_price_rial' => intdiv($lineTotal, $quantity), 'quantity' => $quantity, 'line_total_rial' => $lineTotal]);
        }

        return $order;
    }

    public function test_report_summarises_the_range_ranks_products_and_groups_by_shamsi_month(): void
    {
        $cup = Product::create(['sku' => 'CUP', 'slug' => 'cup', 'name' => 'برس کاسه‌ای', 'type' => 'priced']);
        $disc = Product::create(['sku' => 'DSC', 'slug' => 'dsc', 'name' => 'برس دیسکی', 'type' => 'priced']);

        $this->order(Carbon::parse('2026-11-10'), [[$cup, 2, 2_000_000]], payment: 'paid', province: 'فارس');
        $this->order(Carbon::parse('2026-10-20'), [[$disc, 5, 5_000_000], [$cup, 1, 1_000_000]], province: 'تهران');
        $this->order(Carbon::parse('2026-11-12'), [[$disc, 9, 90_000_000]], status: 'cancelled');
        $this->order(Carbon::parse('2026-08-01'), [[$disc, 1, 1_000_000]]);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('800,000 تومان')
            ->assertSee('پرداخت‌شده: 200,000 تومان')
            ->assertSee('1 سفارش لغوشده (حساب نشده)')
            ->assertSee('400,000 تومان')
            ->assertSeeInOrder(['پرفروش‌ترین محصولات', 'برس دیسکی', 'برس کاسه‌ای'])
            ->assertSeeInOrder(['مهر ۱۴۰۵', 'آبان ۱۴۰۵'])
            ->assertSee('فارس')
            ->assertSee('تهران');
    }

    public function test_presets_and_custom_ranges(): void
    {
        $cup = Product::create(['sku' => 'CUP', 'slug' => 'cup', 'name' => 'برس کاسه‌ای', 'type' => 'priced']);
        $this->order(Carbon::parse('2026-10-20'), [[$cup, 1, 3_000_000]]);

        $this->actingAs($this->admin);

        // This Shamsi month (Aban) starts on 2026-10-23.
        $this->get(route('admin.reports.index', ['preset' => 'this-month']))->assertSee('در این بازه سفارشی ثبت نشده است.');
        $this->get(route('admin.reports.index', ['preset' => 'last-month']))->assertSee('300,000 تومان');
        $this->get(route('admin.reports.index', ['from' => '۱۴۰۵/۰۷/۲۸', 'to' => '۱۴۰۵/۰۷/۲۸']))->assertSee('300,000 تومان');
    }

    public function test_product_sales_export(): void
    {
        $cup = Product::create(['sku' => 'CUP', 'slug' => 'cup', 'name' => 'برس کاسه‌ای', 'type' => 'priced']);
        $this->order(now(), [[$cup, 3, 4_500_000]]);

        $csv = $this->actingAs($this->admin)->get(route('admin.reports.export'))->streamedContent();

        $this->assertStringContainsString('"برس کاسه‌ای",3,450000', $csv);
    }
}

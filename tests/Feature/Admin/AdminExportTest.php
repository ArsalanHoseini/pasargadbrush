<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function order(string $orderNumber, Carbon $placedAt, string $status = 'pending'): Order
    {
        $customer = Customer::create(['name' => 'علی رضایی', 'phone' => '0912'.random_int(1000000, 9999999)]);

        return Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'status' => $status,
            'payment_status' => 'unpaid',
            'subtotal_rial' => 1_250_000,
            'shipping_cost_rial' => 0,
            'total_rial' => 1_250_000,
            'placed_at' => $placedAt,
        ]);
    }

    public function test_order_export_is_an_excel_friendly_csv_that_follows_the_list_filters(): void
    {
        $this->order('PB-SHIPPED', now(), 'shipped');
        $this->order('PB-PENDING', now());

        $response = $this->actingAs($this->staff())->get(route('admin.orders.export', ['status' => 'shipped']));

        $response->assertOk()->assertDownload();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('PB-SHIPPED', $csv);
        $this->assertStringContainsString('ارسال‌شده', $csv);
        $this->assertStringContainsString('125000', $csv);
        $this->assertStringNotContainsString('PB-PENDING', $csv);
    }

    public function test_order_list_and_export_filter_by_shamsi_date_range(): void
    {
        $this->travelTo(Carbon::parse('2026-11-14 12:00'));
        $this->order('PB-OCT', Carbon::parse('2026-10-31 23:30'));
        $this->order('PB-NOV', Carbon::parse('2026-11-10 10:00'));

        $this->actingAs($this->staff());

        // ۱۴۰۵/۰۸/۰۹ is 2026-10-31 (Tehran).
        $this->get(route('admin.orders.index', ['from' => '۱۴۰۵/۰۸/۰۹', 'to' => '۱۴۰۵/۰۸/۰۹']))
            ->assertSee('PB-OCT')->assertDontSee('PB-NOV');

        $csv = $this->get(route('admin.orders.export', ['from' => '1405/08/15']))->streamedContent();
        $this->assertStringContainsString('PB-NOV', $csv);
        $this->assertStringNotContainsString('PB-OCT', $csv);
    }

    public function test_an_unreadable_date_is_ignored_and_marked(): void
    {
        $this->order('PB-ANY', now());

        $this->actingAs($this->staff())->get(route('admin.orders.index', ['from' => 'دیروز']))
            ->assertOk()
            ->assertSee('PB-ANY')
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_customer_and_quote_request_exports(): void
    {
        Customer::create(['name' => 'شرکت نمونه', 'phone' => '09121112233']);
        QuoteRequest::create(['name' => 'کامران', 'phone' => '0912', 'message' => 'قیمت برس', 'status' => 'new']);

        $this->actingAs($this->staff());

        $this->assertStringContainsString('شرکت نمونه', $this->get(route('admin.customers.export'))->streamedContent());

        $quotes = $this->get(route('admin.quote-requests.export'))->streamedContent();
        $this->assertStringContainsString('کامران', $quotes);
        $this->assertStringContainsString('جدید', $quotes);
    }

    public function test_guests_cannot_export(): void
    {
        $this->get(route('admin.orders.export'))->assertRedirect(route('admin.login'));
    }
}

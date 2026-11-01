<?php

namespace Tests\Feature\Admin;

use App\Mail\NewOrderReceived;
use App\Mail\OrderPlacedConfirmation;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDimensionField;
use App\Models\ProductVariant;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ManualOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Product $quoteProduct;

    private ProductDimensionField $diameter;

    /** @var array<int, string> */
    private array $sentSmsTo = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Mail::fake();

        $this->app->instance(SmsSender::class, new class($this->sentSmsTo) implements SmsSender
        {
            public function __construct(private array &$sentTo) {}

            public function send(string $mobile, string $message): void
            {
                $this->sentTo[] = $mobile;
            }
        });

        $this->staff = User::factory()->create(['role' => 'admin', 'name' => 'کارشناس فروش']);
        $this->quoteProduct = Product::create(['sku' => 'Q1', 'slug' => 'disk', 'name' => 'برس دیسکی', 'type' => 'quote', 'is_active' => true]);
        $this->diameter = ProductDimensionField::factory()->create([
            'product_id' => $this->quoteProduct->id, 'code' => 'E', 'label' => 'قطر خارجی', 'unit' => 'mm', 'max_value' => 600,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'شرکت نمونه',
            'phone' => '۰۹۱۲۱۲۳۴۵۶۷',
            'email' => 'buyer@example.com',
            'province' => 'تهران',
            'city' => 'تهران',
            'address_line' => 'خیابان آزادی',
            'items' => [[
                'product_id' => $this->quoteProduct->id,
                'quantity' => 10,
                'unit_price_toman' => 250000,
                'dimensions' => [$this->diameter->id => '۳۰۰'],
                'customization_notes' => 'موی سیمی',
            ]],
            'shipping_method' => 'announced',
            'shipping_cost_toman' => 120000,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'notify_customer' => '1',
        ], $overrides);
    }

    private function store(array $payload): TestResponse
    {
        return $this->actingAs($this->staff)->post(route('admin.orders.store'), $payload);
    }

    public function test_guest_cannot_enter_orders(): void
    {
        $this->get(route('admin.orders.create'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.orders.store'), $this->payload())->assertRedirect(route('admin.login'));
    }

    public function test_entry_page_lists_every_product_including_quote_only_ones(): void
    {
        $this->actingAs($this->staff)->get(route('admin.orders.create'))
            ->assertOk()
            ->assertSee('برس دیسکی (استعلامی)')
            ->assertSee('manualOrderForm', false);

        $this->actingAs($this->staff)->get(route('admin.orders.index'))->assertSee(route('admin.orders.create'));
    }

    public function test_staff_register_an_order_for_a_quote_only_product_with_its_dimensions_and_price(): void
    {
        $this->store($this->payload())->assertSessionHasNoErrors();

        $order = Order::with('items')->firstOrFail();
        $item = $order->items->first();

        $this->assertSame(Order::SOURCE_MANUAL, $order->source);
        $this->assertSame($this->staff->id, $order->created_by_user_id);
        $this->assertSame('09121234567', $order->customer_phone);
        $this->assertSame('confirmed', $order->status);
        $this->assertFalse($order->requires_price_confirmation);
        $this->assertSame(25_000_000, $order->subtotal_rial);
        $this->assertSame(1_200_000, $order->shipping_cost_rial);
        $this->assertSame(26_200_000, $order->total_rial);
        $this->assertSame('برس دیسکی', $item->name_snapshot);
        $this->assertSame('300', $item->customization['dimensions'][0]['value']);
        $this->assertSame('موی سیمی', $item->customization['notes']);
        $this->assertSame('order.created_manually', AuditLog::firstOrFail()->action);
    }

    public function test_dimension_rules_apply_with_the_row_number_in_the_message(): void
    {
        $this->store($this->payload(['items' => [['dimensions' => [$this->diameter->id => '900']]]]))
            ->assertSessionHasErrors(['items.0.dimensions.'.$this->diameter->id => 'ردیف 1 — قطر خارجی (E) نباید بیشتر از 600 باشد.']);

        $this->assertSame(0, Order::count());
    }

    public function test_a_variant_of_another_product_is_rejected(): void
    {
        $other = Product::create(['sku' => 'P2', 'slug' => 'p2', 'name' => 'دیگر', 'type' => 'priced']);
        $variant = ProductVariant::create(['product_id' => $other->id, 'sku' => 'V2', 'label' => 'x', 'price_rial' => 10]);

        $this->store($this->payload(['items' => [['product_variant_id' => $variant->id]]]))
            ->assertSessionHasErrors('items.0.product_variant_id');
    }

    public function test_tracked_stock_is_deducted_and_overselling_is_refused(): void
    {
        $priced = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'برس قالیشویی', 'type' => 'priced', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $priced->id, 'sku' => 'V1', 'label' => 'بزرگ', 'price_rial' => 10, 'stock_quantity' => 5]);
        $line = ['product_id' => $priced->id, 'product_variant_id' => $variant->id, 'quantity' => 3, 'unit_price_toman' => 1000];

        $this->store(array_replace($this->payload(), ['items' => [$line]]))->assertSessionHasNoErrors();
        $this->assertSame(2, $variant->refresh()->stock_quantity);
        $this->assertSame('برس قالیشویی — بزرگ', Order::firstOrFail()->items()->first()->name_snapshot);

        $this->store(array_replace($this->payload(), ['items' => [$line]]))->assertSessionHasErrors('items');
        $this->assertSame(1, Order::count());
        $this->assertSame(2, $variant->refresh()->stock_quantity);
    }

    public function test_an_existing_customer_is_reused_without_being_overwritten(): void
    {
        $existing = Customer::create(['name' => 'نام اصلی', 'phone' => '09121234567']);

        $this->store($this->payload())->assertSessionHasNoErrors();

        $this->assertSame(1, Customer::count());
        $this->assertSame('نام اصلی', $existing->refresh()->name);
        $this->assertTrue(Order::firstOrFail()->customer->is($existing));
    }

    public function test_the_customer_is_notified_only_when_staff_ask_for_it(): void
    {
        $this->store($this->payload());

        Mail::assertSent(OrderPlacedConfirmation::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
        Mail::assertNotSent(NewOrderReceived::class);
        $this->assertSame(['09121234567'], $this->sentSmsTo);

        $this->sentSmsTo = [];
        Order::query()->delete();
        $this->store($this->payload(['notify_customer' => '0']));

        Mail::assertSentCount(1);
        $this->assertSame([], $this->sentSmsTo);
    }

    public function test_cash_on_delivery_never_charges_shipping(): void
    {
        $this->store($this->payload(['shipping_method' => 'cash_on_delivery']))->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame(0, $order->shipping_cost_rial);
        $this->assertSame($order->subtotal_rial, $order->total_rial);
    }

    public function test_the_customer_can_track_a_staff_entered_order(): void
    {
        $this->store($this->payload());
        $order = Order::firstOrFail();

        $this->post(route('orders.track.store'), ['order_number' => $order->order_number, 'phone' => '09121234567'])
            ->assertRedirect(route('orders.track.show'));
        $this->get(route('orders.track.show'))->assertSee('قطر خارجی')->assertSee('تأیید شد');
    }

    public function test_entering_an_order_from_a_quote_request_prefills_it_and_links_them(): void
    {
        $quoteRequest = QuoteRequest::create([
            'product_id' => $this->quoteProduct->id, 'name' => 'آقای مشتری', 'phone' => '09125556677',
            'company' => 'کارخانه نمونه', 'message' => 'برای خط تولید', 'requested_quantity' => '۲۰ عدد',
            'customization' => ['dimensions' => [['code' => 'E', 'label' => 'قطر خارجی', 'unit' => 'mm', 'value' => '450']], 'notes' => null],
        ]);

        $this->actingAs($this->staff)->get(route('admin.quote-requests.edit', $quoteRequest))
            ->assertSee(route('admin.orders.create', ['quote_request' => $quoteRequest->id]), false);

        $this->actingAs($this->staff)->get(route('admin.orders.create', ['quote_request' => $quoteRequest->id]))
            ->assertOk()
            ->assertSee('value="آقای مشتری"', false)
            ->assertSee('کارخانه نمونه')
            // Inside @js(...), where every quote is escaped as \u0022.
            ->assertSee(str_replace('"', $this->jsQuote(), '"'.$this->diameter->id.'":"450"'), false)
            ->assertSee(str_replace('"', $this->jsQuote(), '"quantity":20'), false);

        $this->store($this->payload(['quote_request_id' => $quoteRequest->id]))->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $quoteRequest->refresh();
        $this->assertSame($order->id, $quoteRequest->order_id);
        $this->assertSame('closed', $quoteRequest->status);

        $this->actingAs($this->staff)->get(route('admin.orders.create', ['quote_request' => $quoteRequest->id]))
            ->assertRedirect(route('admin.orders.edit', $order));
        $this->store($this->payload(['quote_request_id' => $quoteRequest->id]))->assertSessionHasErrors('quote_request_id');
        $this->actingAs($this->staff)->get(route('admin.quote-requests.edit', $quoteRequest))->assertSee($order->order_number);
    }

    private function jsQuote(): string
    {
        return chr(92).'u0022';
    }

    public function test_order_page_shows_who_entered_it(): void
    {
        $this->store($this->payload());

        $this->actingAs($this->staff)->get(route('admin.orders.edit', Order::firstOrFail()))
            ->assertSee('ثبت دستی توسط کارشناس فروش');
    }
}

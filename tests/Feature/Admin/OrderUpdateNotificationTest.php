<?php

namespace Tests\Feature\Admin;

use App\Mail\OrderUpdated;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class OrderUpdateNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    /** @var array<int, array{mobile: string, message: string}> */
    private array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Mail::fake();

        $this->app->instance(SmsSender::class, new class($this->sms) implements SmsSender
        {
            public function __construct(private array &$sent) {}

            public function send(string $mobile, string $message): void
            {
                $this->sent[] = ['mobile' => $mobile, 'message' => $message];
            }
        });

        $this->staff = User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function order(array $overrides = []): Order
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '09121234567']);
        $product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'برس سیلندری', 'type' => 'quote', 'is_active' => true]);

        $order = $customer->orders()->create(array_merge([
            'order_number' => 'PB-20261101-ABC123', 'customer_name' => 'علی', 'customer_phone' => '09121234567',
            'customer_email' => 'ali@example.com', 'status' => 'pending', 'payment_status' => 'unpaid',
            'shipping_method' => 'flat', 'subtotal_rial' => 0, 'shipping_cost_rial' => 500_000, 'total_rial' => 500_000,
            'placed_at' => now(),
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id, 'name_snapshot' => 'برس سیلندری', 'sku_snapshot' => 'P1',
            'unit_price_rial' => 0, 'quantity' => 2, 'line_total_rial' => 0,
        ]);

        return $order;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(Order $order, array $data): TestResponse
    {
        return $this->actingAs($this->staff)->put(route('admin.orders.update', $order), array_merge([
            'status' => $order->status, 'payment_status' => $order->payment_status, 'notify_customer' => '1',
        ], $data));
    }

    public function test_the_order_page_offers_the_notify_checkbox_ticked_by_default(): void
    {
        $this->actingAs($this->staff)->get(route('admin.orders.edit', $this->order()))
            ->assertSee('اطلاع‌رسانی به مشتری')
            ->assertSee('name="notify_customer" value="1" class="mt-1" checked', false);
    }

    public function test_shipping_an_order_texts_and_emails_the_customer_with_the_tracking_code(): void
    {
        $order = $this->order();

        $this->save($order, ['status' => 'shipped', 'shipment_tracking_code' => '1234567890'])
            ->assertSessionHas('status', 'سفارش به‌روزرسانی شد و به مشتری پیامک/ایمیل ارسال شد.');

        $this->assertCount(1, $this->sms);
        $this->assertSame('09121234567', $this->sms[0]['mobile']);
        $this->assertStringContainsString('PB-20261101-ABC123', $this->sms[0]['message']);
        $this->assertStringContainsString('سفارش شما ارسال شد.', $this->sms[0]['message']);
        $this->assertStringContainsString('کد رهگیری مرسوله: 1234567890', $this->sms[0]['message']);
        $this->assertStringContainsString(route('orders.track.create'), $this->sms[0]['message']);

        Mail::assertSent(OrderUpdated::class, fn ($mail) => $mail->hasTo('ali@example.com') && in_array('سفارش شما ارسال شد.', $mail->lines, true));
        $this->assertTrue(AuditLog::firstOrFail()->changes['customer_notified']);
    }

    public function test_confirming_the_final_price_tells_the_customer_the_amount(): void
    {
        $order = $this->order(['requires_price_confirmation' => true]);
        $item = $order->items()->firstOrFail();

        $this->save($order, ['item_prices' => [$item->id => 1_500_000], 'price_confirmed' => '1']);

        $this->assertCount(1, $this->sms);
        $this->assertStringContainsString('قیمت نهایی تأیید شد — مبلغ کل: 3,050,000 تومان', $this->sms[0]['message']);
    }

    public function test_status_change_and_price_confirmation_together_send_one_message(): void
    {
        $order = $this->order(['requires_price_confirmation' => true]);
        $item = $order->items()->firstOrFail();

        $this->save($order, ['status' => 'confirmed', 'item_prices' => [$item->id => 1_000_000], 'price_confirmed' => '1']);

        $this->assertCount(1, $this->sms);
        $this->assertStringContainsString('سفارش شما تأیید شد', $this->sms[0]['message']);
        $this->assertStringContainsString('قیمت نهایی تأیید شد', $this->sms[0]['message']);
        Mail::assertSentCount(1);
    }

    public function test_when_shipping_is_still_unknown_the_message_says_so(): void
    {
        $order = $this->order(['requires_price_confirmation' => true, 'shipping_method' => 'announced', 'shipping_cost_rial' => 0, 'total_rial' => 0]);
        $item = $order->items()->firstOrFail();

        $this->save($order, ['item_prices' => [$item->id => 1_000_000], 'price_confirmed' => '1']);

        $this->assertStringContainsString('جمع کالاها: 2,000,000 تومان (هزینه ارسال جداگانه اعلام می‌شود)', $this->sms[0]['message']);
    }

    public function test_nothing_is_sent_when_unticked_or_when_nothing_the_customer_cares_about_changed(): void
    {
        $order = $this->order();

        $this->save($order, ['status' => 'confirmed', 'notify_customer' => '0']);
        $this->save($order->refresh(), ['notes' => 'یادداشت داخلی']);
        $this->save($order->refresh(), ['status' => 'pending']);

        $this->assertSame([], $this->sms);
        Mail::assertNothingSent();
    }

    public function test_no_email_without_an_address_and_a_failing_sms_never_blocks_the_save(): void
    {
        $order = $this->order(['customer_email' => null]);
        $this->app->instance(SmsSender::class, new class implements SmsSender
        {
            public function send(string $mobile, string $message): void
            {
                throw new RuntimeException('SMS provider down');
            }
        });

        $this->save($order, ['status' => 'completed'])->assertSessionHasNoErrors();

        $this->assertSame('completed', $order->refresh()->status);
        Mail::assertNothingSent();
    }

    public function test_the_update_email_renders_what_changed_and_the_order(): void
    {
        $order = $this->order();

        (new OrderUpdated($order, ['سفارش شما ارسال شد.', 'کد رهگیری مرسوله: 1234567890']))
            ->assertSeeInHtml('PB-20261101-ABC123')
            ->assertSeeInHtml('سفارش شما ارسال شد.')
            ->assertSeeInHtml('کد رهگیری مرسوله: 1234567890')
            ->assertSeeInHtml('برس سیلندری');
    }
}

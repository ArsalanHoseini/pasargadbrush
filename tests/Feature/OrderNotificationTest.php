<?php

namespace Tests\Feature;

use App\Mail\NewOrderReceived;
use App\Mail\OrderPlacedConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Sms\SmsSender;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{mobile: string, message: string}> */
    private array $sentSms = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);

        $this->app->instance(SmsSender::class, new class($this->sentSms) implements SmsSender
        {
            public function __construct(private array &$sent) {}

            public function send(string $mobile, string $message): void
            {
                $this->sent[] = ['mobile' => $mobile, 'message' => $message];
            }
        });
    }

    private function placeOrder(array $overrides = []): TestResponse
    {
        $product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'محصول', 'type' => 'priced', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'V1', 'label' => 'استاندارد', 'price_rial' => 1_000_000]);

        $response = $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $token = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'cart_token')->getValue();

        return $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), array_merge([
            'name' => 'علی رضایی',
            'phone' => '09120000000',
            'email' => 'customer@example.com',
            'province' => 'فارس',
            'city' => 'مرودشت',
            'address_line' => 'خیابان اصلی',
        ], $overrides));
    }

    public function test_placing_an_order_emails_the_business_and_the_customer(): void
    {
        Mail::fake();

        $this->placeOrder();

        $order = Order::firstOrFail();

        Mail::assertSent(NewOrderReceived::class, fn ($mail) => $mail->hasTo(setting('contact_email')) && $mail->order->is($order));
        Mail::assertSent(OrderPlacedConfirmation::class, fn ($mail) => $mail->hasTo('customer@example.com') && $mail->order->is($order));
    }

    public function test_placing_an_order_texts_the_business_and_the_customer(): void
    {
        Mail::fake();

        $this->placeOrder();

        $order = Order::firstOrFail();
        $recipients = array_column($this->sentSms, 'mobile');

        $this->assertEqualsCanonicalizing([setting('contact_mobile'), '09120000000'], $recipients);
        foreach ($this->sentSms as $sms) {
            $this->assertStringContainsString($order->order_number, $sms['message']);
        }
    }

    public function test_confirmation_goes_to_the_email_entered_on_this_order_not_the_existing_customer_record(): void
    {
        Mail::fake();
        Customer::create(['name' => 'مشتری قبلی', 'phone' => '09120000000', 'email' => 'existing@example.com']);

        $this->placeOrder(['email' => 'this-order@example.com']);

        Mail::assertSent(OrderPlacedConfirmation::class, fn ($mail) => $mail->hasTo('this-order@example.com'));
        Mail::assertNotSent(OrderPlacedConfirmation::class, fn ($mail) => $mail->hasTo('existing@example.com'));
    }

    public function test_customer_without_an_email_gets_no_confirmation_email(): void
    {
        Mail::fake();

        $this->placeOrder(['email' => null]);

        Mail::assertSent(NewOrderReceived::class);
        Mail::assertNotSent(OrderPlacedConfirmation::class);
    }

    public function test_a_failing_notification_channel_never_loses_the_order(): void
    {
        Mail::fake();
        $this->app->instance(SmsSender::class, new class implements SmsSender
        {
            public function send(string $mobile, string $message): void
            {
                throw new RuntimeException('SMS provider down');
            }
        });

        $this->placeOrder()->assertRedirect(route('checkout.confirmation'));

        $this->assertSame(1, Order::count());
        Mail::assertSent(NewOrderReceived::class);
    }

    public function test_order_emails_render_the_real_order_details(): void
    {
        Mail::fake();
        $this->placeOrder();
        $order = Order::with('items')->firstOrFail();

        (new NewOrderReceived($order))
            ->assertSeeInHtml($order->order_number)
            ->assertSeeInHtml('علی رضایی')
            ->assertSeeInHtml($order->items->first()->name_snapshot);

        (new OrderPlacedConfirmation($order))
            ->assertSeeInHtml($order->order_number)
            ->assertSeeInHtml($order->items->first()->name_snapshot);
    }
}

<?php

namespace Tests\Feature;

use App\Mail\NewQuoteRequestReceived;
use App\Models\Product;
use App\Models\QuoteRequest;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuoteRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_a_visitor_can_submit_a_quote_request_for_a_product(): void
    {
        Mail::fake();
        $product = Product::create(['sku' => 'S1', 'slug' => 'p1', 'name' => 'محصول', 'type' => 'quote', 'is_active' => true]);

        $response = $this->post(route('quote-requests.store'), [
            'product_id' => $product->id,
            'name' => 'علی رضایی',
            'phone' => '09120000000',
            'message' => 'نیاز به ۱۰۰ عدد دارم.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quote_requests', [
            'product_id' => $product->id,
            'name' => 'علی رضایی',
            'status' => 'new',
        ]);
        Mail::assertSent(NewQuoteRequestReceived::class);
    }

    public function test_general_quote_request_without_a_product_is_allowed(): void
    {
        Mail::fake();

        $this->post(route('quote-requests.store'), [
            'name' => 'علی',
            'phone' => '09120000000',
            'message' => 'سفارش خاص دارم.',
        ])->assertRedirect();

        $this->assertDatabaseHas('quote_requests', ['product_id' => null, 'name' => 'علی']);
    }

    public function test_name_phone_and_message_are_required(): void
    {
        $this->post(route('quote-requests.store'), [])->assertSessionHasErrors(['name', 'phone', 'message']);
        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_honeypot_field_rejects_the_submission(): void
    {
        $this->post(route('quote-requests.store'), [
            'name' => 'Bot', 'phone' => '0000000000', 'message' => 'spam', 'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_requests_are_rate_limited(): void
    {
        $payload = ['name' => 'علی', 'phone' => '09120000000', 'message' => 'پیام تست'];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('quote-requests.store'), $payload);
        }

        $this->post(route('quote-requests.store'), $payload)->assertStatus(429);
    }

    public function test_mail_failure_does_not_prevent_the_request_from_being_saved(): void
    {
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP down'));

        $this->post(route('quote-requests.store'), [
            'name' => 'علی', 'phone' => '09120000000', 'message' => 'پیام تست',
        ])->assertRedirect();

        $this->assertSame(1, QuoteRequest::count());
    }
}

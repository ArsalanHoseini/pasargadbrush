<?php

namespace Tests\Feature;

use App\Mail\NewCustomerMessage;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\MessageAttachment;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Services\Messaging\ConversationService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerMessagingTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $this->customer = Customer::create(['name' => 'علی', 'phone' => '09121234567', 'phone_verified_at' => now()]);
    }

    private function order(Customer $customer, string $number = 'PB-1'): Order
    {
        return $customer->orders()->create([
            'order_number' => $number, 'status' => 'pending', 'payment_status' => 'unpaid',
            'subtotal_rial' => 10, 'total_rial' => 10, 'placed_at' => now(),
        ]);
    }

    public function test_guests_cannot_reach_messages(): void
    {
        $this->get(route('account.conversations.index'))->assertRedirect(route('account.login'));
    }

    public function test_customer_opens_a_general_ticket_with_attachments_and_staff_are_emailed(): void
    {
        $this->actingAs($this->customer, 'customer')->post(route('account.conversations.store'), [
            'subject' => 'سؤال درباره‌ی برس قالیشویی',
            'body' => 'آیا قطر ۵۰ سانتی‌متر هم دارید؟',
            'attachments' => [
                UploadedFile::fake()->image('drawing.jpg'),
                UploadedFile::fake()->create('spec.pdf', 200, 'application/pdf'),
            ],
        ])->assertSessionHasNoErrors();

        $conversation = Conversation::with('messages.attachments')->firstOrFail();
        $this->assertSame(Conversation::STATUS_OPEN, $conversation->status);
        $this->assertTrue($conversation->staff_unread);
        $this->assertNull($conversation->order_id);

        $attachments = $conversation->messages->first()->attachments;
        $this->assertCount(2, $attachments);
        $this->assertSame('drawing.jpg', $attachments[0]->original_name);
        Storage::disk('local')->assertExists($attachments[0]->path);
        $this->assertStringNotContainsString('drawing', $attachments[0]->path, 'Stored under a random name.');

        Mail::assertSent(NewCustomerMessage::class, fn ($mail) => $mail->hasTo(setting('contact_email')));
    }

    public function test_only_images_and_pdfs_up_to_the_limit_are_accepted(): void
    {
        $this->actingAs($this->customer, 'customer')->post(route('account.conversations.store'), [
            'subject' => 'x', 'body' => 'x',
            'attachments' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
        ])->assertSessionHasErrors('attachments.0');

        $this->actingAs($this->customer, 'customer')->post(route('account.conversations.store'), [
            'subject' => 'x', 'body' => 'x',
            'attachments' => array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"), range(1, ConversationService::MAX_ATTACHMENTS + 1)),
        ])->assertSessionHasErrors('attachments');

        $this->assertSame(0, Conversation::count());
    }

    public function test_a_conversation_about_an_order_is_linked_and_reused(): void
    {
        $order = $this->order($this->customer);

        $this->actingAs($this->customer, 'customer')->get(route('account.orders.show', $order))
            ->assertSee(route('account.conversations.create', ['order' => $order->id]), false);

        $this->actingAs($this->customer, 'customer')->get(route('account.conversations.create', ['order' => $order->id]))
            ->assertSee('value="سفارش PB-1"', false);

        $this->actingAs($this->customer, 'customer')->post(route('account.conversations.store', ['order' => $order->id]), [
            'subject' => 'سفارش PB-1', 'body' => 'کی ارسال می‌شود؟',
        ]);

        $conversation = Conversation::firstOrFail();
        $this->assertSame($order->id, $conversation->order_id);

        $this->actingAs($this->customer, 'customer')->get(route('account.conversations.create', ['order' => $order->id]))
            ->assertRedirect(route('account.conversations.show', $conversation));
    }

    public function test_cannot_open_a_conversation_about_someone_elses_order(): void
    {
        $theirs = $this->order(Customer::create(['name' => 'دیگری', 'phone' => '09129999999']), 'PB-2');

        $this->actingAs($this->customer, 'customer')->get(route('account.conversations.create', ['order' => $theirs->id]))->assertNotFound();
        $this->actingAs($this->customer, 'customer')->post(route('account.conversations.store', ['order' => $theirs->id]), [
            'subject' => 'x', 'body' => 'x',
        ])->assertNotFound();
    }

    public function test_a_conversation_about_an_earlier_guest_quote_request_with_the_same_mobile(): void
    {
        $quoteRequest = QuoteRequest::create(['name' => 'علی', 'phone' => '09121234567', 'message' => 'سلام']);

        $this->actingAs($this->customer, 'customer')->post(route('account.conversations.store', ['quote_request' => $quoteRequest->id]), [
            'subject' => 'استعلام', 'body' => 'قیمت چه شد؟',
        ])->assertSessionHasNoErrors();

        $this->assertSame($quoteRequest->id, Conversation::firstOrFail()->quote_request_id);
    }

    public function test_conversations_and_attachments_of_other_customers_are_hidden(): void
    {
        $other = Customer::create(['name' => 'دیگری', 'phone' => '09129999999']);
        $conversation = app(ConversationService::class)->start($other, $other, 'خصوصی', 'متن', [UploadedFile::fake()->image('secret.jpg')]);
        $attachment = MessageAttachment::firstOrFail();

        $this->actingAs($this->customer, 'customer')->get(route('account.conversations.index'))->assertDontSee('خصوصی');
        $this->actingAs($this->customer, 'customer')->get(route('account.conversations.show', $conversation))->assertNotFound();
        $this->actingAs($this->customer, 'customer')->post(route('account.conversations.reply', $conversation), ['body' => 'x'])->assertNotFound();
        $this->actingAs($this->customer, 'customer')->get(route('account.conversations.attachment', $attachment))->assertNotFound();

        $this->actingAs($other, 'customer')->get(route('account.conversations.attachment', $attachment))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_reading_clears_the_unread_badge_and_replying_reopens_a_closed_conversation(): void
    {
        $conversation = Conversation::create([
            'customer_id' => $this->customer->id, 'subject' => 'پیگیری', 'status' => Conversation::STATUS_CLOSED, 'customer_unread' => true,
        ]);

        $this->actingAs($this->customer, 'customer')->get(route('account.dashboard'))->assertSee('پیام خوانده‌نشده');

        $this->actingAs($this->customer, 'customer')->get(route('account.conversations.show', $conversation))
            ->assertSee('با ارسال پیام جدید دوباره باز می‌شود');
        $this->assertFalse($conversation->refresh()->customer_unread);

        $this->actingAs($this->customer, 'customer')->post(route('account.conversations.reply', $conversation), ['body' => 'هنوز سؤال دارم']);

        $conversation->refresh();
        $this->assertSame(Conversation::STATUS_OPEN, $conversation->status);
        $this->assertTrue($conversation->staff_unread);
    }
}

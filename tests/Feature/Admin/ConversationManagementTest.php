<?php

namespace Tests\Feature\Admin;

use App\Mail\NewStaffReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\MessageAttachment;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\Messaging\ConversationService;
use App\Services\Sms\SmsSender;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConversationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Customer $customer;

    /** @var array<int, array{mobile: string, message: string}> */
    private array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $this->app->instance(SmsSender::class, new class($this->sms) implements SmsSender
        {
            public function __construct(private array &$sent) {}

            public function send(string $mobile, string $message): void
            {
                $this->sent[] = ['mobile' => $mobile, 'message' => $message];
            }
        });

        $this->staff = User::factory()->create(['role' => 'admin', 'name' => 'کارشناس فروش']);
        $this->customer = Customer::create(['name' => 'علی', 'phone' => '09121234567', 'email' => 'ali@example.com']);
    }

    private function customerTicket(): Conversation
    {
        return app(ConversationService::class)->start($this->customer, $this->customer, 'سؤال فنی', 'سلام', [UploadedFile::fake()->image('a.jpg')]);
    }

    public function test_guest_cannot_view_conversations(): void
    {
        $this->get(route('admin.conversations.index'))->assertRedirect(route('admin.login'));
    }

    public function test_conversations_awaiting_a_reply_are_badged_and_listed_first(): void
    {
        $answered = Conversation::create(['customer_id' => $this->customer->id, 'subject' => 'قدیمی', 'status' => 'answered', 'last_message_at' => now()]);
        $this->customerTicket();

        $this->actingAs($this->staff)->get(route('admin.conversations.index'))
            ->assertOk()
            ->assertSeeInOrder(['سؤال فنی', $answered->subject]);
    }

    public function test_staff_reply_answers_the_conversation_and_notifies_the_customer(): void
    {
        $conversation = $this->customerTicket();

        $this->actingAs($this->staff)->get(route('admin.conversations.show', $conversation))
            ->assertOk()
            ->assertSee('مشتری — علی')
            ->assertSee('a.jpg');
        $this->assertFalse($conversation->refresh()->staff_unread);

        $this->actingAs($this->staff)->post(route('admin.conversations.reply', $conversation), [
            'body' => 'بله، موجود است.', 'attachments' => [UploadedFile::fake()->create('proforma.pdf', 50, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $conversation->refresh();
        $this->assertSame(Conversation::STATUS_ANSWERED, $conversation->status);
        $this->assertTrue($conversation->customer_unread);
        $this->assertSame($this->staff->id, $conversation->messages()->reorder()->latest('id')->first()->user_id);

        $this->assertSame('09121234567', $this->sms[0]['mobile']);
        $this->assertStringContainsString(route('account.conversations.show', $conversation), $this->sms[0]['message']);
        Mail::assertSent(NewStaffReply::class, fn ($mail) => $mail->hasTo('ali@example.com'));

        $this->actingAs($this->customer, 'customer')->get(route('account.conversations.show', $conversation))
            ->assertSee('بله، موجود است.')
            ->assertSee('proforma.pdf');
    }

    public function test_staff_start_a_conversation_about_an_order(): void
    {
        $order = $this->customer->orders()->create([
            'order_number' => 'PB-9', 'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal_rial' => 1, 'total_rial' => 1, 'placed_at' => now(),
        ]);

        $this->actingAs($this->staff)->get(route('admin.orders.edit', $order))
            ->assertSee(route('admin.conversations.create', ['order' => $order->id]), false);

        $this->actingAs($this->staff)->post(route('admin.conversations.store', ['order' => $order->id]), [
            'subject' => 'سفارش PB-9', 'body' => 'لطفاً نقشه را بفرستید.',
        ])->assertSessionHasNoErrors();

        $conversation = Conversation::firstOrFail();
        $this->assertSame($order->id, $conversation->order_id);
        $this->assertSame(Conversation::STATUS_ANSWERED, $conversation->status);
        $this->assertCount(1, $this->sms);
    }

    public function test_messaging_about_a_guest_quote_request_creates_the_customer_only_on_send(): void
    {
        $quoteRequest = QuoteRequest::create(['name' => 'مهمان', 'phone' => '09125556677', 'message' => 'قیمت؟']);

        $this->actingAs($this->staff)->get(route('admin.conversations.create', ['quote_request' => $quoteRequest->id]))
            ->assertOk()
            ->assertSee('این مشتری هنوز حساب ندارد');
        $this->assertNull(Customer::where('phone', '09125556677')->first());

        $this->actingAs($this->staff)->post(route('admin.conversations.store', ['quote_request' => $quoteRequest->id]), [
            'subject' => 'استعلام', 'body' => 'قیمت: ۲ میلیون تومان',
        ])->assertSessionHasNoErrors();

        $guest = Customer::where('phone', '09125556677')->firstOrFail();
        $this->assertSame('مهمان', $guest->name);
        $this->assertSame($guest->id, Conversation::firstOrFail()->customer_id);
    }

    public function test_a_landline_only_quote_request_cannot_be_messaged(): void
    {
        $quoteRequest = QuoteRequest::create(['name' => 'شرکت', 'phone' => '071-43334444', 'message' => 'قیمت؟']);

        $this->actingAs($this->staff)->from(route('admin.quote-requests.edit', $quoteRequest))
            ->get(route('admin.conversations.create', ['quote_request' => $quoteRequest->id]))
            ->assertRedirect(route('admin.quote-requests.edit', $quoteRequest))
            ->assertSessionHas('status');
    }

    public function test_staff_can_close_and_reopen(): void
    {
        $conversation = $this->customerTicket();

        $this->actingAs($this->staff)->put(route('admin.conversations.status', $conversation), ['status' => 'closed']);
        $this->assertSame(Conversation::STATUS_CLOSED, $conversation->refresh()->status);

        $this->actingAs($this->staff)->put(route('admin.conversations.status', $conversation), ['status' => 'open']);
        $this->assertSame(Conversation::STATUS_OPEN, $conversation->refresh()->status);
    }

    public function test_staff_can_download_attachments_but_guests_cannot(): void
    {
        $this->customerTicket();
        $attachment = MessageAttachment::firstOrFail();

        $this->get(route('admin.conversations.attachment', $attachment))->assertRedirect(route('admin.login'));
        $this->actingAs($this->staff)->get(route('admin.conversations.attachment', $attachment))->assertOk();
    }
}

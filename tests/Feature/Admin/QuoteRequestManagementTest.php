<?php

namespace Tests\Feature\Admin;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_view_quote_requests(): void
    {
        $this->get(route('admin.quote-requests.index'))->assertRedirect(route('admin.login'));
    }

    public function test_staff_can_filter_by_status(): void
    {
        QuoteRequest::create(['name' => 'جدید', 'phone' => '0912', 'message' => 'م', 'status' => 'new']);
        QuoteRequest::create(['name' => 'بسته‌شده', 'phone' => '0913', 'message' => 'م', 'status' => 'closed']);

        $response = $this->actingAs($this->staff())->get(route('admin.quote-requests.index', ['status' => 'new']));

        $response->assertOk()->assertSee('جدید')->assertDontSee('بسته‌شده');
    }

    public function test_staff_can_update_status_and_notes(): void
    {
        $quoteRequest = QuoteRequest::create(['name' => 'علی', 'phone' => '0912', 'message' => 'م']);

        $this->actingAs($this->staff())->put(route('admin.quote-requests.update', $quoteRequest), [
            'status' => 'contacted',
            'admin_notes' => 'تماس گرفته شد، منتظر پاسخ.',
        ])->assertRedirect(route('admin.quote-requests.index'));

        $quoteRequest->refresh();
        $this->assertSame('contacted', $quoteRequest->status);
        $this->assertSame('تماس گرفته شد، منتظر پاسخ.', $quoteRequest->admin_notes);
    }

    public function test_staff_can_delete_a_quote_request(): void
    {
        $quoteRequest = QuoteRequest::create(['name' => 'علی', 'phone' => '0912', 'message' => 'م']);

        $this->actingAs($this->staff())->delete(route('admin.quote-requests.destroy', $quoteRequest));

        $this->assertDatabaseMissing('quote_requests', ['id' => $quoteRequest->id]);
    }

    public function test_dashboard_shows_new_quote_request_count(): void
    {
        QuoteRequest::create(['name' => 'علی', 'phone' => '0912', 'message' => 'م', 'status' => 'new']);
        QuoteRequest::create(['name' => 'رضا', 'phone' => '0913', 'message' => 'م', 'status' => 'closed']);

        $this->actingAs($this->staff())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('علی');
    }
}

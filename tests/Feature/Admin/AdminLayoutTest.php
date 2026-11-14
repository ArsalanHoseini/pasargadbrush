<?php

namespace Tests\Feature\Admin;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin', 'name' => 'مدیر سایت']);
    }

    public function test_sidebar_groups_sections_and_marks_the_current_page(): void
    {
        $this->actingAs($this->staff())->get(route('admin.products.index'))
            ->assertOk()
            ->assertSeeInOrder(['فروش', 'سفارش‌ها', 'کاتالوگ', 'محصولات', 'محتوا', 'تنظیمات'])
            ->assertSee('aria-current="page"', false)
            ->assertSee('مدیر سایت');
    }

    public function test_sidebar_shows_waiting_counts(): void
    {
        QuoteRequest::create(['name' => 'علی', 'phone' => '0912', 'message' => 'م', 'status' => 'new']);

        $this->actingAs($this->staff())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['درخواست‌های استعلام', '1']);
    }

    public function test_status_message_is_shown_as_a_toast(): void
    {
        $this->actingAs($this->staff())
            ->withSession(['status' => 'با موفقیت ذخیره شد.'])
            ->get(route('admin.dashboard'))
            ->assertSee('role="status"', false)
            ->assertSee('با موفقیت ذخیره شد.');
    }

    public function test_delete_forms_ask_through_the_confirmation_dialog(): void
    {
        $this->actingAs($this->staff())->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('$store.confirmDialog.open', false)
            ->assertDontSee('return confirm(', false);
    }
}

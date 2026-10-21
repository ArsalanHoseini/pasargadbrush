<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_cannot_view_settings(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_staff_can_update_settings(): void
    {
        $this->actingAs($this->staff())->put(route('admin.settings.update'), [
            'contact_mobile' => '09120000000',
            'contact_landline' => '07133000000',
            'contact_email' => 'info@example.com',
            'social_instagram_url' => 'https://instagram.com/example',
        ])->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('09120000000', Setting::get('contact_mobile'));
        $this->assertSame('07133000000', Setting::get('contact_landline'));
        $this->assertSame('https://instagram.com/example', Setting::get('social_instagram_url'));
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->actingAs($this->staff())->put(route('admin.settings.update'), [
            'contact_email' => 'not-an-email',
        ])->assertSessionHasErrors('contact_email');
    }

    public function test_invalid_social_url_is_rejected(): void
    {
        $this->actingAs($this->staff())->put(route('admin.settings.update'), [
            'social_instagram_url' => 'not-a-url',
        ])->assertSessionHasErrors('social_instagram_url');
    }

    public function test_updating_settings_invalidates_the_cache_used_by_the_setting_helper(): void
    {
        config(['cache.default' => 'database']);
        Setting::create(['key' => 'contact_mobile', 'value' => 'old']);
        setting('contact_mobile'); // warm the cache

        $this->actingAs($this->staff())->put(route('admin.settings.update'), [
            'contact_mobile' => 'new-number',
        ]);

        $this->assertSame('new-number', setting('contact_mobile'));
    }
}

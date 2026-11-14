<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_update_their_own_name_and_email(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)->get(route('admin.profile.edit'))->assertOk()->assertSee('نقش: ویرایشگر');

        $this->put(route('admin.profile.update'), ['name' => 'نام تازه', 'email' => 'new@example.com'])
            ->assertSessionHas('status', 'مشخصات شما ذخیره شد.');

        $this->assertSame('new@example.com', $editor->refresh()->email);
        $this->assertSame('profile.updated', AuditLog::sole()->action);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create(['role' => 'editor', 'password' => 'current-password']);

        $this->actingAs($user)->put(route('admin.profile.password'), [
            'current_password' => 'wrong', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->assertTrue(Hash::check('current-password', $user->refresh()->password));

        $this->put(route('admin.profile.password'), [
            'current_password' => 'current-password', 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1',
        ])->assertSessionHas('status', 'رمز عبور شما تغییر کرد.');

        $this->assertTrue(Hash::check('new-password-1', $user->refresh()->password));
        $this->assertSame('profile.password_changed', AuditLog::sole()->action);
    }

    public function test_sidebar_links_to_the_profile(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.dashboard'))
            ->assertSee(route('admin.profile.edit'), false);
    }
}

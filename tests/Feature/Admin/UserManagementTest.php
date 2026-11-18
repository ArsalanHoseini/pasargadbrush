<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'مدیر اصلی']);
    }

    public function test_admin_lists_staff_with_roles(): void
    {
        User::factory()->create(['role' => 'editor', 'name' => 'نویسنده']);

        $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeInOrder(['مدیر اصلی', 'مدیر', 'نویسنده', 'ویرایشگر']);
    }

    public function test_admin_creates_an_editor_and_it_is_audited(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'سارا', 'email' => 'sara@example.com', 'role' => 'editor',
            'password' => 'long-password', 'password_confirmation' => 'long-password',
        ])->assertRedirect(route('admin.users.index'));

        $sara = User::where('email', 'sara@example.com')->sole();
        $this->assertSame('editor', $sara->role);
        $this->assertTrue(Hash::check('long-password', $sara->password));

        $log = AuditLog::where('action', 'user.created')->sole();
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertArrayNotHasKey('password', $log->changes);
    }

    public function test_new_accounts_need_a_confirmed_password_of_eight_characters_and_a_unique_email(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'x', 'email' => $this->admin->email, 'role' => 'editor', 'password' => 'short', 'password_confirmation' => 'other',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_editing_without_a_password_keeps_the_old_one(): void
    {
        $editor = User::factory()->create(['role' => 'editor', 'password' => 'old-password']);

        $this->actingAs($this->admin)->put(route('admin.users.update', $editor), [
            'name' => 'نام جدید', 'email' => $editor->email, 'role' => 'admin', 'password' => '',
        ])->assertRedirect(route('admin.users.index'));

        $editor->refresh();
        $this->assertSame('admin', $editor->role);
        $this->assertTrue(Hash::check('old-password', $editor->password));
        $this->assertSame(['name' => 'نام جدید', 'role' => 'admin'], AuditLog::where('action', 'user.updated')->sole()->changes);
    }

    public function test_an_admin_cannot_demote_or_delete_themself(): void
    {
        User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'editor',
        ])->assertSessionHasErrors('role');

        $this->delete(route('admin.users.destroy', $this->admin))->assertSessionHas('error');

        $this->assertTrue($this->admin->refresh()->isAdmin());
    }

    public function test_another_admin_can_remove_an_admin_but_the_remaining_one_stays(): void
    {
        $other = User::factory()->create(['role' => 'admin']);

        $this->actingAs($other)->delete(route('admin.users.destroy', $this->admin))->assertRedirect(route('admin.users.index'));
        $this->assertModelMissing($this->admin);

        $this->put(route('admin.users.update', $other), ['name' => 'o', 'email' => $other->email, 'role' => 'editor'])
            ->assertSessionHasErrors('role');
        $this->assertTrue($other->refresh()->isAdmin());
    }

    public function test_admin_deletes_an_editor(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $editor))->assertRedirect(route('admin.users.index'));

        $this->assertModelMissing($editor);
        $this->assertSame('user.deleted', AuditLog::sole()->action);
    }
}

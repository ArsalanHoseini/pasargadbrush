<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_successful_login_is_recorded(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'password' => 'secret-pass']);

        $this->post(route('admin.login'), ['email' => $user->email, 'password' => 'secret-pass'])->assertRedirect();

        $log = AuditLog::sole();
        $this->assertSame('auth.login', $log->action);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_activity_page_lists_actions_in_persian_with_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'مدیر']);
        $editor = User::factory()->create(['role' => 'editor', 'name' => 'ویراستار']);
        $product = Product::create(['sku' => 'A', 'slug' => 'a', 'name' => 'برس کاسه‌ای', 'type' => 'quote']);

        AuditLog::record('product.updated', $product, ['name' => 'برس کاسه‌ای'], $editor->id);
        AuditLog::record('product.bulk', null, ['action' => 'deactivate', 'ids' => [1, 2]], $editor->id);
        AuditLog::record('settings.updated', null, [], $admin->id);

        $this->actingAs($admin)->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('ویرایش محصول')
            ->assertSee(route('admin.products.edit', $product), false)
            ->assertSee('غیرفعال‌سازی — 2 مورد')
            ->assertSee('ویرایش تنظیمات سایت');

        $this->get(route('admin.activity.index', ['user' => $editor->id]))
            ->assertSee('ویرایش محصول')
            ->assertDontSee('ویرایش تنظیمات سایت');

        $this->get(route('admin.activity.index', ['group' => 'settings']))
            ->assertSee('ویرایش تنظیمات سایت')
            ->assertDontSee('ویرایش محصول');
    }

    public function test_product_changes_are_audited(): void
    {
        $product = Product::create(['sku' => 'A', 'slug' => 'a', 'name' => 'برس', 'type' => 'quote', 'is_active' => true]);

        $this->actingAs(User::factory()->create(['role' => 'editor']))
            ->post(route('admin.products.bulk'), ['ids' => [$product->id], 'action' => 'deactivate']);

        $this->assertSame(['action' => 'deactivate', 'ids' => [$product->id]], AuditLog::where('action', 'product.bulk')->sole()->changes);
    }
}

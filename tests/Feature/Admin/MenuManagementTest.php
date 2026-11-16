<?php

namespace Tests\Feature\Admin;

use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'editor'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @param  list<array<string, mixed>>  $menu
     * @return array{menu_json: string}
     */
    private function payload(array $menu): array
    {
        return ['menu_json' => json_encode($menu)];
    }

    public function test_guest_cannot_edit_menus(): void
    {
        $this->get(route('admin.menus.edit', 'header'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.menus.update', 'header'), $this->payload([]))->assertRedirect(route('admin.login'));
    }

    public function test_the_migration_seeds_the_previous_hard_coded_links(): void
    {
        $this->assertSame(
            ['صفحه اصلی', 'محصولات', 'مقالات', 'درباره ما', 'تماس با ما'],
            array_column(MenuItem::tree('header'), 'label'),
        );
        $this->assertSame('/order-tracking', MenuItem::tree('footer')[4]['url']);
    }

    public function test_staff_can_open_the_menu_editor(): void
    {
        $this->actingAs($this->staff())
            ->get(route('admin.menus.edit', 'footer'))
            ->assertOk()
            ->assertSee('دسترسی سریع (فوتر)')
            ->assertSee('پیگیری سفارش');
    }

    public function test_unknown_menu_location_is_not_found(): void
    {
        $this->actingAs($this->staff())->get(route('admin.menus.edit', 'sidebar'))->assertNotFound();
    }

    public function test_staff_can_replace_a_menu_with_nested_items(): void
    {
        $this->actingAs($this->staff())
            ->put(route('admin.menus.update', 'header'), $this->payload([
                ['label' => 'محصولات', 'type' => 'categories', 'url' => null, 'open_in_new_tab' => false, 'children' => []],
                ['label' => 'خدمات', 'type' => 'link', 'url' => '/page/services', 'open_in_new_tab' => false, 'children' => [
                    ['label' => 'برس سفارشی', 'type' => 'link', 'url' => '/page/custom', 'open_in_new_tab' => false],
                    ['label' => 'کاتالوگ', 'type' => 'link', 'url' => 'https://example.com/catalog.pdf', 'open_in_new_tab' => true],
                ]],
            ]))
            ->assertRedirect(route('admin.menus.edit', 'header'))
            ->assertSessionHasNoErrors();

        $tree = MenuItem::tree('header');
        $this->assertSame(['محصولات', 'خدمات'], array_column($tree, 'label'));
        $this->assertSame(['برس سفارشی', 'کاتالوگ'], array_column($tree[1]['children'], 'label'));
        $this->assertTrue($tree[1]['children'][1]['open_in_new_tab']);
        $this->assertSame(5, MenuItem::where('location', 'footer')->count(), 'Other menus are untouched.');
    }

    public function test_public_header_and_footer_show_the_edited_menus(): void
    {
        $this->actingAs($this->staff());
        $this->put(route('admin.menus.update', 'header'), $this->payload([
            ['label' => 'نمایندگی‌ها', 'type' => 'link', 'url' => '/page/agencies', 'open_in_new_tab' => false, 'children' => []],
        ]));
        $this->put(route('admin.menus.update', 'footer'), $this->payload([
            ['label' => 'قوانین سایت', 'type' => 'link', 'url' => '/page/terms', 'open_in_new_tab' => false, 'children' => []],
        ]));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('نمایندگی‌ها')
            ->assertSee('/page/agencies', false)
            ->assertSee('قوانین سایت')
            ->assertDontSee('پیگیری سفارش</a>', false);
    }

    public function test_links_must_have_a_safe_address(): void
    {
        $this->actingAs($this->staff())
            ->put(route('admin.menus.update', 'header'), $this->payload([
                ['label' => 'بد', 'type' => 'link', 'url' => 'javascript:alert(1)', 'open_in_new_tab' => false, 'children' => []],
            ]))
            ->assertSessionHasErrors('menu.0.url');

        $this->assertSame('صفحه اصلی', MenuItem::tree('header')[0]['label'], 'The menu is left as it was.');
    }

    public function test_link_items_need_a_label_and_address(): void
    {
        $this->actingAs($this->staff())
            ->put(route('admin.menus.update', 'header'), $this->payload([
                ['label' => '', 'type' => 'link', 'url' => '', 'open_in_new_tab' => false, 'children' => []],
            ]))
            ->assertSessionHasErrors(['menu.0.label', 'menu.0.url']);
    }

    public function test_an_empty_menu_can_be_saved(): void
    {
        $this->actingAs($this->staff())
            ->put(route('admin.menus.update', 'footer'), $this->payload([]))
            ->assertSessionHasNoErrors();

        $this->assertSame([], MenuItem::tree('footer'));
    }
}

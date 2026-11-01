<?php

namespace Tests\Feature\Admin;

use App\Models\SeoRedirect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoRedirectManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create(['role' => 'admin']);
    }

    public function test_guests_cannot_manage_redirects(): void
    {
        $this->get(route('admin.redirects.index'))->assertRedirect(route('admin.login'));
    }

    public function test_staff_add_a_redirect_by_pasting_a_full_old_url(): void
    {
        $this->actingAs($this->staff)->post(route('admin.redirects.store'), [
            'from_path' => 'https://pasargadbrush.com/product/'.rawurlencode('برس-قدیمی').'/',
            'status_code' => 301,
            'to_path' => '/products/برس-جدید',
            'is_active' => '1',
        ])->assertRedirect(route('admin.redirects.index'));

        $redirect = SeoRedirect::firstOrFail();
        $this->assertSame('/product/برس-قدیمی', $redirect->from_path);
        $this->assertSame(SeoRedirect::SOURCE_MANUAL, $redirect->source);

        $this->actingAs($this->staff)->get(route('admin.redirects.index', ['q' => 'برس-قدیمی']))->assertSee('/product/برس-قدیمی');
    }

    public function test_a_410_needs_no_target_and_a_301_does(): void
    {
        $this->actingAs($this->staff)->post(route('admin.redirects.store'), [
            'from_path' => '/gone', 'status_code' => 410, 'to_path' => '/ignored', 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertNull(SeoRedirect::firstOrFail()->to_path);

        $this->actingAs($this->staff)->post(route('admin.redirects.store'), [
            'from_path' => '/moved', 'status_code' => 301, 'is_active' => '1',
        ])->assertSessionHasErrors('to_path');

        $this->actingAs($this->staff)->post(route('admin.redirects.store'), [
            'from_path' => '/gone/', 'status_code' => 301, 'to_path' => '/x',
        ])->assertSessionHasErrors('from_path');
    }

    public function test_editing_a_generated_redirect_makes_it_manual(): void
    {
        $redirect = SeoRedirect::create(['from_path' => '/old', 'to_path' => '/a', 'status_code' => 301, 'source' => SeoRedirect::SOURCE_WORDPRESS, 'is_active' => true]);

        $this->actingAs($this->staff)->put(route('admin.redirects.update', $redirect), [
            'from_path' => '/old', 'status_code' => 301, 'to_path' => '/b', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $redirect->refresh();
        $this->assertSame('/b', $redirect->to_path);
        $this->assertSame(SeoRedirect::SOURCE_MANUAL, $redirect->source);

        $this->actingAs($this->staff)->delete(route('admin.redirects.destroy', $redirect));
        $this->assertModelMissing($redirect);
    }
}

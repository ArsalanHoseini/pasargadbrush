<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_staff_can_log_in_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_authenticated_staff_can_access_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}

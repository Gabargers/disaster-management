<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign In');
    }

    public function test_active_user_can_authenticate_and_is_redirected_to_role_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'responder@example.com',
            'password' => 'secure-password',
        ]);

        Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        $user->assignRole('superadmin');

        $this->post(route('authenticate'), [
            'email' => 'RESPONDER@example.com',
            'password' => 'secure-password',
        ])
            ->assertRedirect(route('superadmin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'responder@example.com',
            'password' => 'secure-password',
        ]);

        $this->from(route('login'))->post(route('authenticate'), [
            'email' => 'responder@example.com',
            'password' => 'wrong-password',
        ])
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_is_rejected_after_valid_credentials(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => 'secure-password',
        ]);

        $this->from(route('login'))->post(route('authenticate'), [
            'email' => 'inactive@example.com',
            'password' => 'secure-password',
        ])
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_repeated_invalid_credentials_are_rate_limited(): void
    {
        $email = 'locked@example.com';

        User::factory()->create([
            'email' => $email,
            'password' => 'secure-password',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->from(route('login'))->post(route('authenticate'), [
                'email' => $email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->from(route('login'))->post(route('authenticate'), [
            'email' => $email,
            'password' => 'secure-password',
        ])
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        RateLimiter::clear('login:user:'.$email.'|127.0.0.1');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
    }
}

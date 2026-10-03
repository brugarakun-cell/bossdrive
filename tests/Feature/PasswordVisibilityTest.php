<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_screens_include_password_visibility_toggle(): void
    {
        $user = User::factory()->create();
        $resetToken = Password::createToken($user);
        $urls = [
            route('login'),
            route('register'),
            route('admin.login'),
            route('staff.login'),
            route('password.reset', ['token' => $resetToken, 'email' => $user->email]),
        ];

        foreach ($urls as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('password-toggle-wrapper')
                ->assertSee('input[type="password"]', false)
                ->assertSee('Show password')
                ->assertSee('eyeSlashIcon')
                ->assertSee('background: #f8f9fa');
        }
    }

    public function test_user_staff_and_admin_password_settings_include_visibility_toggle(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('password-toggle-wrapper');

        $staff = User::factory()->create(['role' => 'staff']);
        $this->withSession([
            'is_staff' => true,
            'staff_user_id' => $staff->id,
            'staff_name' => $staff->name,
        ])->get(route('staff.profile'))
            ->assertOk()
            ->assertSee('password-toggle-wrapper');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->withSession([
            'is_admin' => true,
            'admin_user_id' => $admin->id,
            'admin_name' => $admin->name,
        ])->get(route('admin.profile'))
            ->assertOk()
            ->assertSee('password-toggle-wrapper');

        $this->withSession([
            'is_admin' => true,
            'admin_user_id' => $admin->id,
            'admin_name' => $admin->name,
        ])->get(route('admin.users'))
            ->assertOk()
            ->assertSee('password-toggle-wrapper');
    }
}

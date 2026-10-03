<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class UserPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_asks_for_email_and_a_six_digit_code(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Send 6-Digit Code')
            ->assertSee('name="email"', false);

        $this->get(route('password.reset.code'))
            ->assertOk()
            ->assertSee('name="code"', false)
            ->assertSee('name="password"', false);
    }

    public function test_registered_user_receives_a_six_digit_code_and_can_set_a_new_password(): void
    {
        Notification::fake();
        config([
            'mail.mailers.smtp.username' => 'sender@example.com',
            'mail.mailers.smtp.password' => 'configured-secret',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $code = null;

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.reset.code', ['email' => $user->email]))
            ->assertSessionHas('success', 'A 6-digit password reset code has been sent to your email address.');
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('sender@example.com', config('mail.from.address'));

        Notification::assertSentTo($user, PasswordResetCodeNotification::class, function (PasswordResetCodeNotification $notification) use (&$code): bool {
            $code = $notification->code;

            return preg_match('/^\d{6}$/', $code) === 1;
        });
        $this->assertIsString($code);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $this->assertSame(60, config('auth.passwords.users.expire'));

        $this->post(route('password.update'), [
            'email' => $user->email,
            'code' => $code,
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('StrongPassword123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertFalse(Auth::validate(['email' => $user->email, 'password' => 'password']));
        $this->assertTrue(Auth::validate(['email' => $user->email, 'password' => 'StrongPassword123']));

        $this->post(route('password.update'), [
            'email' => $user->email,
            'code' => $code,
            'password' => 'AnotherStrongPassword123',
            'password_confirmation' => 'AnotherStrongPassword123',
        ])->assertRedirect()->assertSessionHasErrors('email');
        $this->assertTrue(Auth::validate(['email' => $user->email, 'password' => 'StrongPassword123']));
    }

    public function test_code_is_sent_only_for_existing_registered_user_accounts(): void
    {
        Notification::fake();
        config([
            'mail.mailers.smtp.username' => 'sender@example.com',
            'mail.mailers.smtp.password' => 'configured-secret',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['missing@example.com', $admin->email] as $email) {
            $this->post(route('password.email'), ['email' => $email])
                ->assertRedirect()
                ->assertSessionHasErrors([
                    'email' => 'No user account is registered with that email address.',
                ]);
        }

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_smtp_authentication_failure_explains_that_a_google_app_password_is_needed(): void
    {
        config([
            'mail.mailers.smtp.username' => 'sender@example.com',
            'mail.mailers.smtp.password' => 'configured-secret',
        ]);
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new TransportException('SMTP authentication failed.'));
        $user = User::factory()->create(['role' => 'user']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'email' => 'Gmail rejected the email login. Set a valid Google App Password in .env (MAIL_PASSWORD), then restart the app and try again.',
            ]);

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_expired_code_cannot_change_the_password(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(61),
        ]);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
        ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('StrongPassword123', $user->fresh()->password));
    }

    public function test_wrong_code_cannot_change_the_password(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'code' => '654321',
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
        ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('StrongPassword123', $user->fresh()->password));
    }

    public function test_reset_code_cannot_reset_an_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post(route('password.update'), [
            'email' => $admin->email,
            'code' => '123456',
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
        ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('StrongPassword123', $admin->fresh()->password));
    }
}

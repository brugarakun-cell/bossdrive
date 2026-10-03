<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AuthController extends Controller
{
    public function showLogin(Request $request): View
    {
        $vehicleId = $request->integer('vehicle_id');
        if ($vehicleId > 0) {
            $request->session()->put('booking_vehicle_id', $vehicleId);
        }

        return view('user.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The email or password is incorrect.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $vehicleId = $request->session()->pull('booking_vehicle_id');

        return $vehicleId
            ? redirect()->route('user.reservations', ['vehicle_id' => $vehicleId])
            : redirect()->intended(route('user.dashboard'));
    }

    public function showRegister(Request $request): View
    {
        return view('user.register', ['step' => $request->session()->get('register_step', 1)]);
    }

    public function registerPersonal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact_number' => ['required', 'string', 'max:30'],
            'age' => ['required', 'integer', 'min:18', 'max:120'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'gender' => ['required', 'in:Male,Female,Other'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ]);
        $calculatedAge = Carbon::parse($data['birth_date'])->age;
        if ($calculatedAge !== (int) $data['age']) {
            throw ValidationException::withMessages([
                'age' => 'The age does not match the birthday provided. Please check both fields.',
            ]);
        }

        $request->session()->put('registration', [
            'name' => $data['name'],
            'email' => $data['email'],
            'contact_number' => $data['contact_number'],
            'age' => $data['age'],
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now()->toDateTimeString(),
        ]);
        $request->session()->put('register_step', 3);

        return redirect()->route('register')->with('success', 'Personal information saved. Complete your address to finish registration.');
    }

    public function verifyRegistrationEmail(Request $request): RedirectResponse
    {
        $registration = $request->session()->get('registration');
        $data = $request->validate(['verification_code' => ['required', 'digits:6']]);

        if (! $registration || now()->timestamp > ($registration['verification_expires_at'] ?? 0)
            || ! Hash::check($data['verification_code'], $registration['verification_code'])) {
            return back()->withErrors(['verification_code' => 'The verification code is invalid or expired.'])->withInput();
        }

        $request->session()->put('register_step', 3);
        $request->session()->put('registration.email_verified_at', now()->toDateTimeString());

        return redirect()->route('register');
    }

    public function resendRegistrationEmail(Request $request): RedirectResponse
    {
        $registration = $request->session()->get('registration');
        abort_unless($registration, 419, 'Registration session expired.');

        $code = (string) random_int(100000, 999999);
        $newRegistration = $registration;
        $newRegistration['verification_code'] = Hash::make($code);
        $newRegistration['verification_expires_at'] = now()->addMinutes(10)->timestamp;

        if (blank(config('mail.mailers.smtp.username')) || blank(config('mail.mailers.smtp.password'))) {
            return back()->withErrors([
                'email' => 'Gmail SMTP is not configured. Set MAIL_USERNAME and a Google App Password in your .env file.',
            ]);
        }

        try {
            Mail::raw("Your Big Boss Car Rental verification code is {$code}. It expires in 10 minutes.", function ($message) use ($newRegistration) {
                $message->to($newRegistration['email'])->subject('Big Boss Car Rental Email Verification');
            });
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->withErrors([
                'email' => 'The verification email could not be sent. Check the Google App Password and confirm MAIL_USERNAME matches MAIL_FROM_ADDRESS.',
            ]);
        }

        $request->session()->put('registration', $newRegistration);

        return back()->with('success', 'A new verification code was sent.');
    }

    public function registrationBack(Request $request): RedirectResponse
    {
        $request->session()->put('register_step', 1);

        return redirect()->route('register');
    }

    public function completeRegistration(Request $request): RedirectResponse
    {
        $registration = $request->session()->get('registration');
        abort_unless($registration && in_array($request->session()->get('register_step'), [2, 3], true), 419, 'Registration session expired.');

        $data = $request->validate([
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'barangay' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $registration['name'],
            'email' => $registration['email'],
            'contact_number' => $registration['contact_number'],
            'age' => $registration['age'],
            'birth_date' => $registration['birth_date'],
            'gender' => $registration['gender'],
            'password' => $registration['password'],
            'province' => $data['province'],
            'city' => $data['city'],
            'barangay' => $data['barangay'],
            'address' => $data['address'],
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        Auth::login($user);
        $vehicleId = $request->session()->pull('booking_vehicle_id');
        $request->session()->forget(['registration', 'register_step']);
        $request->session()->regenerate();

        return $vehicleId
            ? redirect()->route('user.reservations', ['vehicle_id' => $vehicleId])->with('success', 'Your account was created successfully.')
            : redirect()->route('user.reservations')->with('success', 'Your account was created successfully.');
    }

    public function showForgotPassword(): View
    {
        return view('user.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower($data['email']);
        $limiterKey = 'password-reset-request:'.hash('sha256', $email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            return back()->withErrors(['email' => 'Too many reset requests. Please wait a minute and try again.']);
        }
        RateLimiter::hit($limiterKey, 60);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('role', 'user')
            ->first();

        if (! $user) {
            return back()
                ->withErrors(['email' => 'No user account is registered with that email address.'])
                ->withInput(['email' => $data['email']]);
        }

        if (blank(config('mail.mailers.smtp.username')) || blank(config('mail.mailers.smtp.password'))) {
            return back()->withErrors(['email' => 'Password reset email is not configured. Please contact support.']);
        }

        config([
            'mail.default' => 'smtp',
            'mail.from.address' => config('mail.mailers.smtp.username'),
        ]);

        $resetTable = config('auth.passwords.users.table');
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::table($resetTable)->where('email', $user->email)->delete();
        DB::table($resetTable)->insert([
            'email' => $user->email,
            'token' => Hash::make($code),
            'created_at' => now(),
        ]);

        try {
            $user->notify(new PasswordResetCodeNotification($code));
        } catch (TransportExceptionInterface $exception) {
            report($exception);
            DB::table($resetTable)->where('email', $user->email)->delete();

            return back()->withErrors([
                'email' => 'Gmail rejected the email login. Set a valid Google App Password in .env (MAIL_PASSWORD), then restart the app and try again.',
            ])->withInput(['email' => $data['email']]);
        }

        return redirect()
            ->route('password.reset.code', ['email' => $user->email])
            ->with('success', 'A 6-digit password reset code has been sent to your email address.');
    }

    public function showResetPassword(Request $request, ?string $token = null): View
    {
        return view('user.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ]);

        $email = Str::lower($data['email']);
        $limiterKey = 'password-reset-submit:'.hash('sha256', $email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            return back()->withErrors(['email' => 'Too many reset attempts. Request a new code and try again later.'])->withInput(['email' => $email]);
        }
        RateLimiter::hit($limiterKey, 600);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('role', 'user')
            ->first();
        if (! $user) {
            return back()->withErrors(['email' => 'This reset code is invalid or expired.'])->withInput(['email' => $data['email']]);
        }

        $resetTable = config('auth.passwords.users.table');
        $resetRecord = DB::table($resetTable)->where('email', $user->email)->first();
        $expiresAt = now()->subMinutes((int) config('auth.passwords.users.expire'));
        if (! $resetRecord
            || Carbon::parse($resetRecord->created_at)->lt($expiresAt)
            || ! Hash::check($data['code'], $resetRecord->token)) {
            return back()->withErrors(['email' => 'This reset code is invalid or expired.'])->withInput(['email' => $data['email']]);
        }

        $user->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();
        DB::table($resetTable)->where('email', $user->email)->delete();
        RateLimiter::clear($limiterKey);

        return redirect()->route('login')->with('success', 'Your password was changed successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget(['registration', 'register_step']);
        $request->session()->regenerateToken();

        return redirect()->route('reservations');
    }
}

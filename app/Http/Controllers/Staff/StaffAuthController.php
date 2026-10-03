<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffAuthController extends Controller
{
    public function showLogin(): View|Response|RedirectResponse
    {
        if (request()->session()->get('is_staff') === true) {
            return redirect()->route('staff.reservations');
        }

        return response()
            ->view('staff.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', trim($credentials['email']))
            ->whereIn('role', ['staff', 'admin'])
            ->first();

        $valid = $user && Hash::check($credentials['password'], $user->password);

        if (!$valid) {
            return back()->withErrors(['email' => 'Invalid staff credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('is_staff', true);
        if ($user->role === 'admin') {
            $request->session()->put('is_admin', true);
        }
        $request->session()->put('staff_name', $user->name);
        $request->session()->put('staff_user_id', $user->id);
        $request->session()->regenerateToken();

        return redirect()->route('staff.reservations');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['is_staff', 'staff_name', 'staff_user_id']);
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}

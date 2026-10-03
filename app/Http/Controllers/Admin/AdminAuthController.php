<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    private function adminUser(Request $request): ?User
    {
        $admin = User::whereKey($request->session()->get('admin_user_id'))
            ->where('role', 'admin')
            ->first();

        if (!$admin) {
            $admin = User::where('email', trim((string) config('admin.email')))
                ->where('role', 'admin')
                ->first();
        }

        if (!$admin && filled($request->session()->get('admin_name'))) {
            $admin = User::where('name', $request->session()->get('admin_name'))
                ->where('role', 'admin')
                ->first();
        }

        if ($admin) {
            $request->session()->put([
                'admin_user_id' => $admin->id,
                'admin_name' => $admin->name,
            ]);
        }

        return $admin;
    }

    public function profile(Request $request): View
    {
        return view('admin.Profile', ['adminUser' => $this->adminUser($request)]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $admin = $this->adminUser($request);
        abort_unless($admin, 403, 'Administrator account was not found.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$admin->id],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'age' => ['nullable', 'integer', 'min:18', 'max:120'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($admin->profile_photo_path) {
                Storage::disk('public')->delete($admin->profile_photo_path);
            }
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        unset($data['profile_photo']);
        $admin->update($data);
        $request->session()->put('admin_name', $admin->name);

        return back()->with('success', 'Admin profile updated successfully.');
    }

    public function showLogin(): View|Response|RedirectResponse
    {
        if (request()->session()->get('is_admin') === true) {
            return redirect()->route('admin.dashboard');
        }

        return response()
            ->view('admin.login')
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

        $adminEmail = trim((string) config('admin.email'));
        $adminPassword = (string) config('admin.password');

        $admin = User::where('email', trim($credentials['email']))->where('role', 'admin')->first();
        $databaseLogin = $admin && Hash::check($credentials['password'], $admin->password);
        $configuredLogin = $adminPassword !== ''
            && hash_equals(strtolower($adminEmail), strtolower(trim($credentials['email'])))
            && Hash::check($credentials['password'], $adminPassword);

        if (!$databaseLogin && !$configuredLogin) {
            return back()->withErrors(['email' => 'Invalid admin credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('is_admin', true);
        $request->session()->regenerateToken();
        $request->session()->put('admin_name', $admin?->name ?? 'Admin Patrick');
        if ($admin) {
            $request->session()->put('admin_user_id', $admin->id);
        } else {
            $fallbackAdmin = User::where('role', 'admin')->orderBy('id')->first();
            if ($fallbackAdmin) {
                $request->session()->put([
                    'admin_user_id' => $fallbackAdmin->id,
                    'admin_name' => $fallbackAdmin->name,
                ]);
            }
        }

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['is_admin', 'admin_name', 'admin_user_id']);
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

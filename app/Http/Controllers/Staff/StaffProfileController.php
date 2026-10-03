<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StaffProfileController extends Controller
{
    private function staffUser(Request $request): User
    {
        $user = User::whereKey($request->session()->get('staff_user_id'))
            ->whereIn('role', ['staff', 'admin'])
            ->first();

        if (!$user && $request->session()->has('staff_name')) {
            $user = User::where('name', $request->session()->get('staff_name'))
                ->whereIn('role', ['staff', 'admin'])
                ->first();
            if ($user) {
                $request->session()->put('staff_user_id', $user->id);
            }
        }

        if (!$user) {
            $staffUsers = User::where('role', 'staff')->get();
            if ($staffUsers->count() === 1) {
                $user = $staffUsers->first();
                $request->session()->put([
                    'staff_user_id' => $user->id,
                    'staff_name' => $user->name,
                ]);
            }
        }

        abort_unless($user, 403, 'Staff account was not found.');

        return $user;
    }

    public function edit(Request $request): View
    {
        return view('staff.profile', ['staffUser' => $this->staffUser($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->staffUser($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'contact_number' => ['required', 'string', 'max:30'],
            'age' => ['required', 'integer', 'min:18', 'max:100'],
            'gender' => ['required', 'in:Male,Female,Other'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'current_password' => ['nullable', 'string'],
            'password' => [
                'nullable',
                'required_with:current_password',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ], [
            'password.regex' => 'New password must contain an uppercase letter, lowercase letter, number, and special character.',
            'password.required_with' => 'Enter a new password when changing your password.',
        ]);

        $passwordChangeRequested = filled($data['password'] ?? null) || filled($data['current_password'] ?? null);
        if ($passwordChangeRequested && !Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.'])->withInput();
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'contact_number' => $data['contact_number'],
            'age' => $data['age'],
            'gender' => $data['gender'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $request->session()->put('staff_name', $user->name);

        return back()->with('success', 'Staff profile updated successfully.');
    }
}

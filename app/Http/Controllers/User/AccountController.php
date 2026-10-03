<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('user.account', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
            'contact_number' => ['required', 'string', 'max:30'],
            'age' => ['nullable', 'integer', 'min:18', 'max:120'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'barangay' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if (($data['age'] ?? null) !== null && ($data['birth_date'] ?? null) !== null
            && Carbon::parse($data['birth_date'])->age !== (int) $data['age']) {
            throw ValidationException::withMessages([
                'age' => 'The age does not match the birthday provided. Please check both fields.',
            ]);
        }

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        unset($data['profile_photo']);
        $request->user()->update($data);

        return back()->with('success', 'Account details updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password updated successfully.');
    }
}

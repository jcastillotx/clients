<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'profile_photo' => ['nullable', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $user->fill(collect($validated)->except('profile_photo')->all());

        if ($request->hasFile('profile_photo')) {
            $useProfilePhotoPathColumn = Schema::hasColumn('users', 'profile_photo_path');

            // Delete old photo if present
            $oldPath = $useProfilePhotoPathColumn ? ($user->profile_photo_path ?? null) : ($user->avatar ?? null);
            if ($oldPath) {
                Storage::disk('public')->delete((string) $oldPath);
            }

            $path = $request->file('profile_photo')->store('profile-photos', 'public');

            // Backwards compatible: if migration isn't applied yet, store in existing `avatar` column.
            if ($useProfilePhotoPathColumn) {
                $user->profile_photo_path = $path;
            } else {
                $user->avatar = $path;
            }
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's company information.
     */
    public function updateCompany(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        if (!$user->client) {
            return back()->with('error', 'No company associated with this account.');
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
        ]);

        // Map the validated data to match the client model fields
        $clientData = [
            'company_name' => $validated['company_name'],
            'contact_name' => $validated['contact_name'],
            'phone' => $validated['company_phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'country' => $validated['country'] ?? null,
            'website' => $validated['website'] ?? null,
            'industry' => $validated['industry'] ?? null,
        ];

        $user->client()->update($clientData);

        return back()->with('success', 'Company information updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

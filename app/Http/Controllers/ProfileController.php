<?php
// app/Http/Controllers/ProfileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form
     */
    public function edit()
    {
        $user = Auth::user();
        $employee = $user->employee;

        return view('profile.edit', compact('user', 'employee'));
    }

    /**
     * Update the user's profile information
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'employee_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'uae_contact' => 'nullable|string|max:20',
            'home_country_contact' => 'nullable|string|max:20',
        ], [
            'employee_name.required' => 'Employee name is required',
            'employee_name.max' => 'Employee name cannot exceed 255 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email address is already in use',
        ]);

        DB::beginTransaction();
        try {
            // Update user - using fill() and save() as alternative
            $user->fill([
                'employee_name' => $validated['employee_name'],
                'email' => $validated['email'],
            ])->save();

            // Update employee if exists
            if ($user->employee) {
                $user->employee->fill([
                    'employee_name' => $validated['employee_name'],
                    'uae_contact' => $validated['uae_contact'] ?? null,
                    'home_country_contact' => $validated['home_country_contact'] ?? null,
                ])->save();
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully!'
                ]);
            }

            return back()->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to update profile. Please try again.']);
        }
    }

    /**
     * Update the user's password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Current password is required',
            'password.required' => 'New password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
        ]);

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($validated['current_password'], $user->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                    'errors' => [
                        'current_password' => ['Current password is incorrect']
                    ]
                ], 422);
            }

            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        // Update password
        $user->fill([
            'password' => Hash::make($validated['password'])
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully!'
            ]);
        }

        return back()->with('success', 'Password updated successfully!');
    }

    /**
     * Show the user's profile
     */
    public function show()
    {
        $user = Auth::user();
        $employee = $user->employee;

        $recentAttendances = [];
        if ($employee) {
            $recentAttendances = $employee->attendances()
                ->orderBy('attendance_date', 'desc')
                ->limit(10)
                ->get();
        }

        return view('profile.show', compact('user', 'employee', 'recentAttendances'));
    }
}

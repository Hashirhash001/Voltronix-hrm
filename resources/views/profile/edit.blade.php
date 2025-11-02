{{-- resources/views/profile/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Profile</span>
        </li>
    </ul>

    <div class="pt-5">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Profile Information -->
            <div class="panel">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Profile Information</h5>
                </div>

                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-8">
                        <div class="flex items-center justify-center">
                            <div class="h-24 w-24 rounded-full bg-primary/10 flex items-center justify-center">
                                <svg class="h-12 w-12 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
                                    <ellipse opacity="0.5" cx="12" cy="18" rx="7" ry="4" stroke="currentColor" stroke-width="1.5"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label for="staff_number">Staff Number</label>
                        <input id="staff_number" type="text" class="form-input bg-gray-100" value="{{ $user->staff_number }}" disabled/>
                    </div>

                    <div class="mb-5">
                        <label for="employee_name">Employee Name <span class="text-danger">*</span></label>
                        <input id="employee_name" type="text" name="employee_name" class="form-input" placeholder="Enter name" value="{{ old('employee_name', $user->employee_name) }}" required/>
                        @error('employee_name')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input id="email" type="email" name="email" class="form-input" placeholder="Enter email" value="{{ old('email', $user->email) }}" required/>
                        @error('email')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    @if($employee)
                    <div class="mb-5">
                        <label for="uae_contact">UAE Contact</label>
                        <input id="uae_contact" type="text" name="uae_contact" class="form-input" placeholder="Enter UAE contact" value="{{ old('uae_contact', $employee->uae_contact) }}"/>
                    </div>

                    <div class="mb-5">
                        <label for="home_country_contact">Home Country Contact</label>
                        <input id="home_country_contact" type="text" name="home_country_contact" class="form-input" placeholder="Enter home country contact" value="{{ old('home_country_contact', $employee->home_country_contact) }}"/>
                    </div>
                    @endif

                    <button type="submit" class="btn btn-primary !mt-6">Update Profile</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="panel">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Change Password</h5>
                </div>

                <form action="{{ route('profile.password.update') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-5">
                        <label for="current_password">Current Password <span class="text-danger">*</span></label>
                        <input id="current_password" type="password" name="current_password" class="form-input" placeholder="Enter current password" required/>
                        @error('current_password')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label for="password">New Password <span class="text-danger">*</span></label>
                        <input id="password" type="password" name="password" class="form-input" placeholder="Enter new password" required/>
                        @error('password')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <label for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" placeholder="Confirm new password" required/>
                    </div>

                    <button type="submit" class="btn btn-primary !mt-6">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
        });
    });
</script>
@endif
@endsection

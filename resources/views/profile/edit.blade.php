@extends('layouts.app')
@section('title', 'My Profile')
@section('content')
<div class="max-w-2xl mx-auto px-6 py-10">
    <h1 class="text-h2 text-slate-900 mb-6">My Profile</h1>

    <div class="space-y-6">
        {{-- Profile Info --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-semibold text-slate-800">Personal Information</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-span-2">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input">
                            @error('email')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">Company</label>
                            <input type="text" name="company" value="{{ old('company', $user->company) }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Country</label>
                            <input type="text" name="country" value="{{ old('country', $user->country) }}" class="form-input">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary btn-md">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Change Password --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-semibold text-slate-800">Change Password</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" required class="form-input">
                        @error('current_password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" required class="form-input" placeholder="Min. 8 characters">
                            @error('password')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" required class="form-input">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary btn-md">Update Password</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Account Info --}}
        <div class="card">
            <div class="card-body">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-slate-400 text-xs font-medium uppercase tracking-wide mb-0.5">Role</dt>
                        <dd class="font-medium text-slate-800">{{ $user->getRoleNames()->implode(', ') ?: 'No role' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs font-medium uppercase tracking-wide mb-0.5">Member Since</dt>
                        <dd class="font-medium text-slate-800">{{ $user->created_at->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 text-xs font-medium uppercase tracking-wide mb-0.5">Status</dt>
                        <dd>
                            <x-ui.badge color="{{ $user->is_active ? 'green' : 'red' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </x-ui.badge>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

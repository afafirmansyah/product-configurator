@extends('layouts.admin')
@section('title', 'Edit User')
@section('heading', 'Edit User')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="hover:text-slate-600">Users</a>
    <x-ui.icon name="chevron-right" class="w-3.5 h-3.5"/>
    <span class="text-slate-600">{{ $user->name }}</span>
@endsection
@section('header-actions')
    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Reset password for {{ $user->name }}?')">
            <x-ui.icon name="lock" class="w-3.5 h-3.5"/>
            Reset Password
        </button>
    </form>
@endsection
@section('content')
<div class="max-w-2xl space-y-4">
    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background:#2563eb">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                    <div class="text-xs text-slate-400">Member since {{ $user->created_at->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input">
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
                    <div>
                        <label class="form-label">Role *</label>
                        <select name="role" required class="form-select">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ $user->is_active ? 'checked' : '' }} class="w-4 h-4 rounded" style="accent-color:#2563eb">
                    <label for="is_active" class="text-sm text-slate-700">Account is active</label>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn btn-primary btn-md">Save Changes</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-md">Cancel</a>
                    @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                              onsubmit="return confirm('Permanently delete {{ $user->name }}?')" class="ml-auto">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-md">Delete User</button>
                        </form>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

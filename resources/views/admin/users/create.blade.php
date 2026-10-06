@extends('layouts.admin')
@section('title', 'Create User')
@section('heading', 'Create User')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="hover:text-slate-600">Users</a>
    <x-ui.icon name="chevron-right" class="w-3.5 h-3.5"/>
    <span class="text-slate-600">Create</span>
@endsection
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-header">
            <h2 class="text-sm font-semibold text-slate-800">User Details</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="form-input @error('name') error @enderror">
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="form-input @error('email') error @enderror">
                        @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" required class="form-input" placeholder="Min. 8 characters">
                        @error('password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="password_confirmation" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Company</label>
                        <input type="text" name="company" value="{{ old('company') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Country</label>
                        <input type="text" name="country" value="{{ old('country') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Role *</label>
                        <select name="role" required class="form-select @error('role') error @enderror">
                            <option value="">Select role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 rounded" style="accent-color:#2563eb">
                    <label for="is_active" class="text-sm text-slate-700">Account is active</label>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn btn-primary btn-md">Create User</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-md">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

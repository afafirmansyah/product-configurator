@extends('layouts.auth')
@section('title', 'Reset Password')
@section('content')
<h1 class="text-2xl font-bold text-slate-900 mb-1">Set new password</h1>
<p class="text-slate-500 text-sm mb-8">Choose a strong password for your account.</p>

<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label class="form-label">Email address</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="form-input @error('email') error @enderror">
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="form-label">New password</label>
        <input type="password" name="password" required
               class="form-input"
               placeholder="Min. 8 characters">
        @error('password')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="form-label">Confirm new password</label>
        <input type="password" name="password_confirmation" required
               class="form-input">
    </div>
    <button type="submit" class="btn btn-primary btn-md w-full">Reset password</button>
</form>
@endsection

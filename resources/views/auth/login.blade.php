@extends('layouts.auth')
@section('title', 'Sign In')
@section('content')
@if(session('status'))
    <x-ui.alert type="success" class="mb-4">{{ session('status') }}</x-ui.alert>
@endif

<form method="POST" action="{{ route('login.post') }}" class="space-y-4">
    @csrf
    <div>
        <label class="form-label">Email address</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               class="form-input @error('email') error @enderror"
               placeholder="you@company.com">
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <div class="flex items-center justify-between mb-1">
            <label class="form-label mb-0">Password</label>
            <a href="{{ route('password.request') }}" class="text-xs text-blue-600 hover:text-blue-700">Forgot password?</a>
        </div>
        <input type="password" name="password" required
               class="form-input"
               placeholder="••••••••">
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded" style="accent-color:#2563eb">
        <label for="remember" class="text-sm text-slate-600">Remember me for 30 days</label>
    </div>
    <button type="submit" class="btn btn-primary btn-md w-full">Sign in</button>
</form>


@endsection

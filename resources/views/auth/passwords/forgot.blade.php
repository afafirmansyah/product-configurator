@extends('layouts.auth')
@section('title', 'Forgot Password')
@section('content')
<a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-6">
    <x-ui.icon name="arrow-left" class="w-3.5 h-3.5"/>
    Back to sign in
</a>
<h1 class="text-2xl font-bold text-slate-900 mb-1">Reset your password</h1>
<p class="text-slate-500 text-sm mb-8">Enter your email and we'll send you a reset link.</p>

@if(session('status'))
    <x-ui.alert type="success" class="mb-4">{{ session('status') }}</x-ui.alert>
@endif

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div>
        <label class="form-label">Email address</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               class="form-input @error('email') error @enderror"
               placeholder="you@company.com">
        @error('email')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="btn btn-primary btn-md w-full">Send reset link</button>
</form>
@endsection

@extends('layouts.auth')
@section('title', 'Create Account')
@section('content')
<h1 class="text-2xl font-bold text-slate-900 mb-1">Create your account</h1>
<p class="text-slate-500 text-sm mb-8">Start configuring industrial products today</p>

<form method="POST" action="{{ route('register.post') }}" class="space-y-4">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <div class="col-span-2">
            <label class="form-label">Full name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="form-input @error('name') error @enderror"
                   placeholder="John Smith">
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="col-span-2">
            <label class="form-label">Company</label>
            <input type="text" name="company" value="{{ old('company') }}"
                   class="form-input"
                   placeholder="Acme Industrial Ltd.">
        </div>
        <div class="col-span-2">
            <label class="form-label">Email address</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="form-input @error('email') error @enderror"
                   placeholder="you@company.com">
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Password</label>
            <input type="password" name="password" required
                   class="form-input"
                   placeholder="Min. 8 characters">
            @error('password')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="form-label">Confirm password</label>
            <input type="password" name="password_confirmation" required
                   class="form-input"
                   placeholder="Repeat password">
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-md w-full">Create account</button>
</form>

<p class="text-center text-sm text-slate-500 mt-6">
    Already have an account?
    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">Sign in</a>
</p>
@endsection

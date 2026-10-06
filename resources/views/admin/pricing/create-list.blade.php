@extends('layouts.admin')
@section('title', 'Create Price List')
@section('heading', 'Create Price List')
@section('content')
<div class="max-w-lg">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.pricing.lists.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="EUR Standard Price List">
                </div>
                <div>
                    <label class="form-label">Code *</label>
                    <input type="text" name="code" value="{{ old('code') }}" required class="form-input font-mono" placeholder="EUR_STANDARD">
                </div>
                <div>
                    <label class="form-label">Currency *</label>
                    <select name="currency" class="form-select">
                        @foreach(['EUR','USD','IDR','SGD','INR'] as $cur)
                            <option value="{{ $cur }}" {{ old('currency','EUR') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Valid From</label>
                        <input type="date" name="valid_from" value="{{ old('valid_from') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Valid To</label>
                        <input type="date" name="valid_to" value="{{ old('valid_to') }}" class="form-input">
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_default" value="1" class="w-4 h-4 rounded" style="accent-color:#2563eb">
                        Set as default price list
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded" style="accent-color:#2563eb">
                        Active
                    </label>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary btn-md">Create Price List</button>
                    <a href="{{ route('admin.pricing.index') }}" class="btn btn-secondary btn-md">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

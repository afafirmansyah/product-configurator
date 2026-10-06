@extends('layouts.admin')
@section('title', 'Settings')
@section('heading', 'System Settings')
@section('content')
<div class="max-w-2xl space-y-4">
    <div class="card">
        <div class="card-header"><h3 class="text-sm font-semibold text-slate-800">Application</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label">Application Name</label>
                    <input type="text" name="app_name" value="{{ $settings['app_name'] }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Application URL</label>
                    <input type="url" name="app_url" value="{{ $settings['app_url'] }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Default Currency</label>
                    <select name="default_currency" class="form-select w-40">
                        @foreach(['EUR','USD','IDR','SGD','INR'] as $cur)
                            <option value="{{ $cur }}" {{ $settings['default_currency'] === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Quotation Validity (days)</label>
                    <input type="number" name="quotation_validity_days" value="{{ $settings['quotation_validity_days'] }}" min="1" class="form-input w-32">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary btn-md">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="text-sm font-semibold text-slate-800">System Information</h3></div>
        <div class="card-body">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-400 text-xs">Laravel Version</dt><dd class="font-medium">{{ app()->version() }}</dd></div>
                <div><dt class="text-slate-400 text-xs">PHP Version</dt><dd class="font-medium">{{ PHP_VERSION }}</dd></div>
                <div><dt class="text-slate-400 text-xs">Environment</dt><dd><x-ui.badge color="{{ app()->isProduction() ? 'green' : 'yellow' }}">{{ app()->environment() }}</x-ui.badge></dd></div>
                <div><dt class="text-slate-400 text-xs">Debug Mode</dt><dd><x-ui.badge color="{{ config('app.debug') ? 'red' : 'green' }}">{{ config('app.debug') ? 'On' : 'Off' }}</x-ui.badge></dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'My Configurations')
@section('content')
<div class="max-w-screen-xl mx-auto px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-h2 text-slate-900">My Configurations</h1>
            <p class="text-slate-500 text-sm mt-1">Your saved product configurations</p>
        </div>
        <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-md">
            <x-ui.icon name="plus" class="w-4 h-4"/>
            New Configuration
        </a>
    </div>

    <div class="card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Configuration</th>
                    <th>Product</th>
                    <th>Code</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($configurations as $cfg)
                    <tr>
                        <td class="font-mono text-sm font-medium text-slate-900">{{ $cfg->configuration_number }}</td>
                        <td class="text-slate-700">{{ $cfg->product->name }}</td>
                        <td class="text-data text-xs text-blue-700">{{ $cfg->configuration_code ?? '—' }}</td>
                        <td class="font-medium text-slate-900">{{ $cfg->currency }} {{ number_format($cfg->total_price, 0) }}</td>
                        <td>
                            <x-ui.badge color="{{ $cfg->status === 'finalized' ? 'green' : ($cfg->status === 'validated' ? 'blue' : 'gray') }}">
                                {{ ucfirst($cfg->status) }}
                            </x-ui.badge>
                        </td>
                        <td class="text-slate-400 text-xs">{{ $cfg->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('configurator.show', $cfg->ulid) }}" class="btn btn-ghost btn-sm">
                                <x-ui.icon name="eye" class="w-3.5 h-3.5"/>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">
                        <x-ui.empty-state icon="configurations" title="No configurations yet"
                            description="Start by browsing the product catalog and configuring a product."
                            actionLabel="Browse Catalog" actionHref="{{ route('catalog.index') }}"/>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $configurations->links() }}</div>
    </div>
</div>
@endsection

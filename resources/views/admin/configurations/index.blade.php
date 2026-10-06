@extends('layouts.admin')
@section('title', 'Configurations')
@section('heading', 'Saved Configurations')
@section('content')
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Configuration</th>
                <th>Product</th>
                <th>User</th>
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
                    <td class="text-data font-medium text-slate-900">{{ $cfg->configuration_number }}</td>
                    <td class="text-slate-700">{{ $cfg->product?->name ?? '—' }}</td>
                    <td class="text-slate-500">{{ $cfg->user?->name ?? 'Guest' }}</td>
                    <td class="text-data text-blue-700">{{ $cfg->configuration_code ?? '—' }}</td>
                    <td class="font-medium text-slate-900">{{ $cfg->currency }} {{ number_format($cfg->total_price, 0) }}</td>
                    <td>
                        @php $colors = ['draft'=>'gray','validated'=>'blue','finalized'=>'green']; @endphp
                        <x-ui.badge color="{{ $colors[$cfg->status] ?? 'gray' }}">{{ ucfirst($cfg->status) }}</x-ui.badge>
                    </td>
                    <td class="text-slate-400">{{ $cfg->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('configurator.show', $cfg->ulid) }}" class="btn btn-ghost btn-sm">
                            <x-ui.icon name="eye" class="w-3.5 h-3.5"/>
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">
                    <x-ui.empty-state icon="configurations" title="No configurations yet" description="Configurations will appear here once users start configuring products."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $configurations->links() }}</div>
</div>
@endsection

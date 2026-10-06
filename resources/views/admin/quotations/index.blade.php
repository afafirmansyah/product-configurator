@extends('layouts.admin')
@section('title', 'Quotation Requests')
@section('heading', 'Quotation Requests')
@section('content')
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Company</th>
                <th>Contact</th>
                <th>Product</th>
                <th>Configuration</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quotations as $q)
                <tr>
                    <td class="text-data font-medium text-slate-900">{{ $q->reference_number }}</td>
                    <td class="font-medium text-slate-900">{{ $q->company }}</td>
                    <td>
                        <div class="text-slate-700">{{ $q->contact_person }}</div>
                        <div class="text-xs text-slate-400">{{ $q->email }}</div>
                    </td>
                    <td class="text-slate-600">{{ $q->configuration?->product?->name ?? '—' }}</td>
                    <td class="text-data text-blue-700">{{ $q->configuration?->configuration_number ?? '—' }}</td>
                    <td>
                        @php $colors = ['pending'=>'yellow','reviewed'=>'blue','quoted'=>'green','closed'=>'gray']; @endphp
                        <x-ui.badge color="{{ $colors[$q->status] ?? 'gray' }}">{{ ucfirst($q->status) }}</x-ui.badge>
                    </td>
                    <td class="text-slate-400">{{ $q->created_at->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <x-ui.empty-state icon="quotations" title="No quotation requests yet" description="Quotation requests will appear here once submitted."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $quotations->links() }}</div>
</div>
@endsection

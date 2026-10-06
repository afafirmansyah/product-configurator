@extends('layouts.admin')
@section('title', 'Pricing')
@section('heading', 'Price Lists')
@section('header-actions')
    <x-ui.button href="{{ route('admin.pricing.lists.create') }}" variant="primary" size="sm">
        <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
        New Price List
    </x-ui.button>
@endsection
@section('content')
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Price List</th>
                <th>Currency</th>
                <th>Products</th>
                <th>Options</th>
                <th>Valid</th>
                <th>Default</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($priceLists as $list)
                <tr>
                    <td>
                        <div class="font-medium text-slate-900">{{ $list->name }}</div>
                        <div class="text-data text-xs text-slate-400">{{ $list->code }}</div>
                    </td>
                    <td><x-ui.badge color="blue">{{ $list->currency }}</x-ui.badge></td>
                    <td class="text-slate-600">{{ $list->product_prices_count }}</td>
                    <td class="text-slate-600">{{ $list->option_prices_count }}</td>
                    <td class="text-slate-500">
                        @if($list->valid_from || $list->valid_to)
                            {{ $list->valid_from?->format('d M Y') ?? '∞' }} — {{ $list->valid_to?->format('d M Y') ?? '∞' }}
                        @else
                            <span class="text-slate-300">No expiry</span>
                        @endif
                    </td>
                    <td>
                        @if($list->is_default)
                            <x-ui.badge color="green">Default</x-ui.badge>
                        @endif
                    </td>
                    <td>
                        <x-ui.badge color="{{ $list->is_active ? 'green' : 'gray' }}">
                            {{ $list->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <a href="{{ route('admin.pricing.lists.edit', $list) }}" class="btn btn-ghost btn-sm">
                            <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                            Edit
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">
                    <x-ui.empty-state icon="pricing" title="No price lists"
                        description="Create price lists to manage product and option pricing."
                        actionLabel="Create Price List" actionHref="{{ route('admin.pricing.lists.create') }}"/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

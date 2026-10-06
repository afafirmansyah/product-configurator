@extends('layouts.admin')
@section('title', 'Compatibility Rules')
@section('heading', 'Compatibility Rules')
@section('header-actions')
    <x-ui.button href="{{ route('admin.compatibility.create') }}" variant="primary" size="sm">
        <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
        New Rule
    </x-ui.button>
@endsection
@section('content')

<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="flex gap-3" id="filter-form">
            @php $selectedProduct = $products->firstWhere('id', request('product_id')); @endphp
            <div class="relative" x-data="{ open: false }">
                <input type="hidden" name="product_id" value="{{ request('product_id') }}" x-ref="productInput">
                <button type="button" @click="open = !open"
                    class="flex items-center gap-2 pl-3 pr-2.5 py-1.5 rounded-lg border bg-white text-sm transition-colors hover:bg-slate-50"
                    style="border-color:#e2e8f0; min-width:220px">
                    <span class="flex-1 text-left px-1 {{ $selectedProduct ? 'text-slate-800' : 'text-slate-400' }}">
                        {{ $selectedProduct?->name ?? 'All Products' }}
                    </span>
                    <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mr-1"/>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                    class="absolute left-0 mt-1.5 w-64 bg-white rounded-xl border shadow-md py-1 z-50"
                    style="border-color:#e2e8f0">
                    <button type="button" @click="$refs.productInput.value = ''; open = false; $el.closest('form').submit()"
                        class="w-full text-left px-3 py-2 text-sm {{ !request('product_id') ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                        All Products
                    </button>
                    @foreach($products as $p)
                        <button type="button"
                            @click="$refs.productInput.value = '{{ $p->id }}'; open = false; $el.closest('form').submit()"
                            class="w-full text-left px-3 py-2 text-sm {{ request('product_id') == $p->id ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                            {{ $p->name }}
                        </button>
                    @endforeach
                </div>
            </div>
            @if(request('product_id'))
                <a href="{{ route('admin.compatibility.index') }}" class="btn btn-ghost btn-md">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>When</th>
                <th>Type</th>
                <th>Target</th>
                <th>Reason</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rules as $rule)
                <tr>
                    <td class="text-slate-600">{{ $rule->product?->name ?? '—' }}</td>
                    <td>
                        <span class="font-medium text-slate-700">{{ $rule->attribute?->name ?? '—' }}</span>
                        <span class="text-slate-400 mx-1">=</span>
                        <span class="text-data text-blue-700">{{ $rule->option?->name ?? '—' }}</span>
                    </td>
                    <td>
                        <x-ui.badge color="{{ $rule->type === 'compatible' ? 'green' : 'red' }}">
                            {{ ucfirst($rule->type) }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <span class="font-medium text-slate-700">{{ $rule->targetAttribute?->name ?? '—' }}</span>
                        <span class="text-slate-400 mx-1">=</span>
                        <span class="text-data text-blue-700">{{ $rule->targetOption?->name ?? '—' }}</span>
                    </td>
                    <td class="text-slate-500 max-w-xs truncate">{{ $rule->reason ?? '—' }}</td>
                    <td>
                        <x-ui.badge color="{{ $rule->is_active ? 'green' : 'gray' }}">
                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <button type="button"
                            @click="$dispatch('open-modal', 'del-compat-{{ $rule->id }}')"
                            class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                            <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                            Delete
                        </button>
                        <x-ui.confirm-modal
                            id="del-compat-{{ $rule->id }}"
                            title="Delete Compatibility Rule"
                            action="{{ route('admin.compatibility.destroy', $rule) }}"
                        />
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <x-ui.empty-state icon="compatibility" title="No compatibility rules"
                        description="Define which options are compatible or incompatible with each other."
                        actionLabel="Create Rule" actionHref="{{ route('admin.compatibility.create') }}"/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $rules->links() }}</div>
</div>
@endsection

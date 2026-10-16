@extends('layouts.admin')
@section('title', 'BOM Rules')
@section('heading', 'BOM Rules')
@section('content')

<div class="card mb-4">
    <div class="card-body py-3">
        <div class="flex items-center justify-between">
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
            </form>
            <div class="text-xs text-slate-400">Select a product to add BOM rules from its edit page</div>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Rule Name</th>
                <th>Product</th>
                <th>Conditions</th>
                <th>Components</th>
                <th>Logic</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rules as $rule)
                <tr>
                    <td>
                        <div class="font-medium text-slate-900">{{ $rule->name }}</div>
                        <div class="text-data text-xs text-slate-400">{{ $rule->code }}</div>
                    </td>
                    <td class="text-slate-600">{{ $rule->product?->name ?? '—' }}</td>
                    <td>
                        @if($rule->conditions->isEmpty())
                            <span class="text-xs text-slate-400">Always applies</span>
                        @else
                            <span class="text-slate-600">{{ $rule->conditions->count() }} condition(s)</span>
                        @endif
                    </td>
                    <td>
                        <div class="space-y-0.5">
                            @foreach($rule->items->take(3) as $item)
                                <div class="text-xs text-slate-600">
                                    {{ $item->quantity }}× {{ $item->component?->name ?? $item->childProduct?->name ?? '—' }}
                                    @if($item->child_product_id)
                                        <x-ui.badge color="blue">Assembly</x-ui.badge>
                                    @endif
                                    @if($item->quantity_mode && $item->quantity_mode !== \App\Enums\QuantityMode::Fixed)
                                        <x-ui.badge color="orange">qty: {{ $item->quantity_mode->value }}</x-ui.badge>
                                    @endif
                                    @if($item->valueSets->isNotEmpty())
                                        <x-ui.badge color="purple">{{ $item->valueSets->count() }} value set(s)</x-ui.badge>
                                    @endif
                                </div>
                            @endforeach
                            @if($rule->items->count() > 3)
                                <div class="text-xs text-slate-400">+{{ $rule->items->count() - 3 }} more</div>
                            @endif
                        </div>
                    </td>
                    <td><x-ui.badge color="blue">{{ $rule->condition_logic }}</x-ui.badge></td>
                    <td>
                        <x-ui.badge color="{{ $rule->is_active ? 'green' : 'gray' }}">
                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.bom.edit', $rule) }}" class="btn btn-ghost btn-sm">
                                <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                                Edit
                            </a>
                            <button type="button"
                                @click="$dispatch('open-modal', 'del-bom-{{ $rule->id }}')"
                                class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                                <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                                Delete
                            </button>
                        </div>
                        <x-ui.confirm-modal
                            id="del-bom-{{ $rule->id }}"
                            title="Delete BOM Rule"
                            name="{{ $rule->name }}"
                            action="{{ route('admin.bom.destroy', $rule) }}"
                        />
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <x-ui.empty-state icon="bom" title="No BOM rules"
                        description="BOM rules define which components are added based on configuration selections."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $rules->links() }}</div>
</div>
@endsection

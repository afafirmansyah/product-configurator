@extends('layouts.admin')
@section('title', 'Components')
@section('heading', 'Components')
@section('header-actions')
    <x-ui.button href="{{ route('admin.components.create') }}" variant="primary" size="sm">
        <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
        New Component
    </x-ui.button>
@endsection
@section('content')
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Component</th>
                <th>Code</th>
                <th>Category</th>
                <th>Cost</th>
                <th>Sales Price</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($components as $component)
                <tr>
                    <td>
                        <div class="font-medium text-slate-900">{{ $component->name }}</div>
                        @if($component->manufacturer)
                            <div class="text-xs text-slate-400">{{ $component->manufacturer }}</div>
                        @endif
                    </td>
                    <td class="text-data text-slate-600">{{ $component->code }}</td>
                    <td class="text-slate-600">{{ $component->category?->name ?? '—' }}</td>
                    <td class="text-slate-600">{{ $component->currency }} {{ number_format($component->cost, 0) }}</td>
                    <td class="font-medium text-slate-900">{{ $component->currency }} {{ number_format($component->sales_price, 0) }}</td>
                    <td>
                        @php $colors = ['active'=>'green','inactive'=>'gray','discontinued'=>'red']; @endphp
                        <x-ui.badge color="{{ $colors[$component->status] ?? 'gray' }}">
                            {{ ucfirst($component->status) }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.components.edit', $component) }}" class="btn btn-ghost btn-sm">
                                <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                                Edit
                            </a>
                            <button type="button"
                                @click="$dispatch('open-modal', 'del-comp-{{ $component->id }}')"
                                class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                                <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                                Delete
                            </button>
                        </div>
                        <x-ui.confirm-modal
                            id="del-comp-{{ $component->id }}"
                            title="Delete Component"
                            name="{{ $component->name }}"
                            action="{{ route('admin.components.destroy', $component) }}"
                        />
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <x-ui.empty-state icon="components" title="No components yet" description="Add components to use in BOM rules."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $components->links() }}</div>
</div>
@endsection

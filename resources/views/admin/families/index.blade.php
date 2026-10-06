@extends('layouts.admin')
@section('title', 'Product Families')
@section('heading', 'Product Families')
@section('header-actions')
    <x-ui.button href="{{ route('admin.families.create') }}" variant="primary" size="sm">
        <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
        New Family
    </x-ui.button>
@endsection
@section('content')
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Family</th>
                <th>Category</th>
                <th>Products</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($families as $family)
                <tr>
                    <td>
                        <div class="font-medium text-slate-900">{{ $family->name }}</div>
                        <div class="text-data text-xs text-slate-400">{{ $family->code }}</div>
                    </td>
                    <td class="text-slate-600">{{ $family->category?->name ?? '—' }}</td>
                    <td class="text-slate-600">{{ $family->products_count }}</td>
                    <td>
                        <x-ui.badge color="{{ $family->is_active ? 'green' : 'gray' }}">
                            {{ $family->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.families.edit', $family) }}" class="btn btn-ghost btn-sm">
                                <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                                Edit
                            </a>
                            <button type="button"
                                @click="$dispatch('open-modal', 'del-fam-{{ $family->id }}')"
                                class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                                <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                                Delete
                            </button>
                        </div>
                        <x-ui.confirm-modal
                            id="del-fam-{{ $family->id }}"
                            title="Delete Family"
                            name="{{ $family->name }}"
                            action="{{ route('admin.families.destroy', $family) }}"
                        />
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">
                    <x-ui.empty-state icon="family" title="No families yet" description="Create your first product family."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $families->links() }}</div>
</div>
@endsection

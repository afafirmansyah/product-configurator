@extends('layouts.admin')
@section('title', 'Attributes')
@section('heading', 'Attributes')
@section('header-actions')
    <x-ui.button href="{{ route('admin.attributes.create') }}" variant="primary" size="sm">
        <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
        New Attribute
    </x-ui.button>
@endsection
@section('content')
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Type</th>
                <th>Unit</th>
                <th>Options</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($attributes as $attribute)
                <tr>
                    <td class="font-medium text-slate-900">{{ $attribute->name }}</td>
                    <td class="text-data text-slate-600">{{ $attribute->code }}</td>
                    <td><x-ui.badge color="blue">{{ $attribute->type }}</x-ui.badge></td>
                    <td class="text-slate-500">{{ $attribute->unit ?? '—' }}</td>
                    <td class="text-slate-600">{{ $attribute->options_count }}</td>
                    <td>
                        <x-ui.badge color="{{ $attribute->is_active ? 'green' : 'gray' }}">
                            {{ $attribute->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn btn-ghost btn-sm">
                                <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                                Edit
                            </a>
                            <button type="button"
                                @click="$dispatch('open-modal', 'del-attr-{{ $attribute->id }}')"
                                class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                                <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                                Delete
                            </button>
                        </div>
                        <x-ui.confirm-modal
                            id="del-attr-{{ $attribute->id }}"
                            title="Delete Attribute"
                            name="{{ $attribute->name }}"
                            action="{{ route('admin.attributes.destroy', $attribute) }}"
                        />
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <x-ui.empty-state icon="attributes" title="No attributes yet" description="Create attributes to define product options."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $attributes->links() }}</div>
</div>
@endsection

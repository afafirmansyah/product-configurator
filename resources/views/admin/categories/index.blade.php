@extends('layouts.admin')
@section('title', 'Categories')
@section('heading', 'Categories')
@section('header-actions')
    <x-ui.button href="{{ route('admin.categories.create') }}" variant="primary" size="sm">
        <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
        New Category
    </x-ui.button>
@endsection
@section('content')
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Families</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr>
                    <td class="font-medium text-slate-900">{{ $category->name }}</td>
                    <td class="text-data text-slate-600">{{ $category->code }}</td>
                    <td class="text-slate-600">{{ $category->families_count }}</td>
                    <td>
                        <x-ui.badge color="{{ $category->is_active ? 'green' : 'gray' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-ghost btn-sm">
                                <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                                Edit
                            </a>
                            <button type="button"
                                @click="$dispatch('open-modal', 'del-cat-{{ $category->id }}')"
                                class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                                <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                                Delete
                            </button>
                        </div>
                        <x-ui.confirm-modal
                            id="del-cat-{{ $category->id }}"
                            title="Delete Category"
                            name="{{ $category->name }}"
                            action="{{ route('admin.categories.destroy', $category) }}"
                        />
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">
                    <x-ui.empty-state icon="category" title="No categories yet" description="Create your first category to get started."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $categories->links() }}</div>
</div>
@endsection

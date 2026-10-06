@extends('layouts.admin')
@section('title', 'Rules')
@section('heading', 'Configuration Rules')
@section('content')

<div class="alert alert-info mb-4">
    Rules are managed per product. Go to a product and click <strong>Manage Rules</strong> to create rules for that product.
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Rule</th>
                <th>Product</th>
                <th>Type</th>
                <th>Conditions</th>
                <th>Actions</th>
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
                        @php $typeColors = ['required'=>'red','visibility'=>'purple','warning'=>'yellow','bom'=>'blue']; @endphp
                        <x-ui.badge color="{{ $typeColors[$rule->type] ?? 'gray' }}">{{ $rule->type }}</x-ui.badge>
                    </td>
                    <td class="text-slate-500">{{ $rule->conditions->count() }} condition(s)</td>
                    <td class="text-slate-500">{{ $rule->actions->count() }} action(s)</td>
                    <td>
                        <x-ui.badge color="{{ $rule->is_active ? 'green' : 'gray' }}">
                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.rules.edit', $rule) }}" class="btn btn-ghost btn-sm">
                                <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                                Edit
                            </a>
                            <button type="button"
                                @click="$dispatch('open-modal', 'del-rule-{{ $rule->id }}')"
                                class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                                <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                                Delete
                            </button>
                        </div>
                        <x-ui.confirm-modal
                            id="del-rule-{{ $rule->id }}"
                            title="Delete Rule"
                            name="{{ $rule->name }}"
                            action="{{ route('admin.rules.destroy', $rule) }}"
                        />
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <x-ui.empty-state icon="rules" title="No rules yet" description="Create rules from the product builder."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $rules->links() }}</div>
</div>
@endsection

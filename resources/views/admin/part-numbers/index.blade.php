@extends('layouts.admin')
@section('title', 'Part Number Patterns')
@section('heading', 'Part Number Patterns')
@section('content')
<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Pattern Name</th>
                <th>Product</th>
                <th>Segments</th>
                <th>Preview</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($patterns as $pattern)
                <tr>
                    <td class="font-medium text-slate-900">{{ $pattern->name }}</td>
                    <td class="text-slate-600">{{ $pattern->product?->name ?? '—' }}</td>
                    <td>
                        <div class="flex items-center gap-1 flex-wrap">
                            @foreach($pattern->segments as $seg)
                                <span class="text-xs px-1.5 py-0.5 rounded font-mono
                                    {{ $seg['type'] === 'literal' ? 'bg-slate-100 text-slate-600' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $seg['type'] === 'literal' ? $seg['value'] : '{' . $seg['value'] . '}' }}
                                </span>
                                @if(!$loop->last)<span class="text-slate-300 text-xs">-</span>@endif
                            @endforeach
                        </div>
                    </td>
                    <td class="text-data text-blue-700">
                        {{ collect($pattern->segments)->map(fn($s) => $s['type'] === 'literal' ? $s['value'] : strtoupper($s['value']))->implode('-') }}
                    </td>
                    <td>
                        <x-ui.badge color="{{ $pattern->is_active ? 'green' : 'gray' }}">
                            {{ $pattern->is_active ? 'Active' : 'Inactive' }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.part-numbers.edit', $pattern) }}" class="btn btn-ghost btn-sm">
                                <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                                Edit
                            </a>
                            <button type="button"
                                @click="$dispatch('open-modal', 'del-pn-{{ $pattern->id }}')"
                                class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                                <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                                Delete
                            </button>
                        </div>
                        <x-ui.confirm-modal
                            id="del-pn-{{ $pattern->id }}"
                            title="Delete Part Number Pattern"
                            name="{{ $pattern->name }}"
                            action="{{ route('admin.part-numbers.destroy', $pattern) }}"
                        />
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">
                    <x-ui.empty-state icon="part-number" title="No part number patterns"
                        description="Create patterns from the product builder to auto-generate part numbers."/>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">{{ $patterns->links() }}</div>
</div>
@endsection

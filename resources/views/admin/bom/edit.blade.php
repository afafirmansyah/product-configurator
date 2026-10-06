@extends('layouts.admin')
@section('title', 'Edit BOM Rule')
@section('heading', 'Edit BOM Rule: ' . $rule->name)
@section('content')
<div class="max-w-2xl space-y-4">
    <div class="card">
        <div class="card-header">
            <h3 class="text-sm font-semibold text-slate-800">Rule Details</h3>
        </div>
        <div class="card-body">
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-400 text-xs">Product</dt><dd class="font-medium">{{ $rule->product->name }}</dd></div>
                <div><dt class="text-slate-400 text-xs">Logic</dt><dd><x-ui.badge color="blue">{{ $rule->condition_logic }}</x-ui.badge></dd></div>
                <div><dt class="text-slate-400 text-xs">Code</dt><dd class="text-data text-xs">{{ $rule->code }}</dd></div>
                <div><dt class="text-slate-400 text-xs">Status</dt><dd><x-ui.badge color="{{ $rule->is_active ? 'green' : 'gray' }}">{{ $rule->is_active ? 'Active' : 'Inactive' }}</x-ui.badge></dd></div>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="text-sm font-semibold text-slate-800">Conditions</h3></div>
        <div class="card-body">
            @if($rule->conditions->isEmpty())
                <p class="text-sm text-slate-400">No conditions — this rule always applies.</p>
            @else
                <div class="space-y-2">
                    @foreach($rule->conditions as $cond)
                        <div class="flex items-center gap-2 text-sm bg-slate-50 rounded-lg px-3 py-2">
                            <span class="font-medium text-blue-700">{{ $cond->attribute->name }}</span>
                            <span class="text-slate-400">{{ str_replace('_',' ',$cond->operator) }}</span>
                            <span class="text-data text-xs bg-white border rounded px-2 py-0.5" style="border-color:#e2e8f0">{{ $cond->value }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="text-sm font-semibold text-slate-800">Components</h3></div>
        <div class="card-body">
            <div class="space-y-2">
                @foreach($rule->items as $item)
                    <div class="flex items-center justify-between text-sm bg-slate-50 rounded-lg px-3 py-2">
                        <div>
                            <span class="font-medium text-slate-900">{{ $item->quantity }}×</span>
                            <span class="text-slate-700 ml-1">{{ $item->component->name }}</span>
                            <span class="text-slate-400 text-xs ml-2 font-mono">{{ $item->component->code }}</span>
                        </div>
                        <x-ui.badge color="{{ $item->is_mandatory ? 'blue' : 'gray' }}">
                            {{ $item->is_mandatory ? 'Mandatory' : 'Optional' }}
                        </x-ui.badge>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <a href="{{ route('admin.bom.index') }}" class="btn btn-secondary btn-md">← Back</a>
        <form method="POST" action="{{ route('admin.bom.destroy', $rule) }}" onsubmit="return confirm('Delete this BOM rule?')">
            @csrf @method('DELETE')
            <button class="btn btn-danger btn-md">Delete Rule</button>
        </form>
    </div>
</div>
@endsection

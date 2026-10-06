@extends('layouts.admin')
@section('title', 'Edit Rule')
@section('heading', 'Edit Rule: ' . $rule->name)
@section('content')
<div class="max-w-2xl space-y-4">

    {{-- Rule Info --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Rule Details</h3>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-gray-500">Product</dt><dd class="font-medium">{{ $rule->product->name }}</dd></div>
            <div><dt class="text-gray-500">Type</dt><dd><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-xs">{{ $rule->type }}</span></dd></div>
            <div><dt class="text-gray-500">Logic</dt><dd class="font-medium">{{ $rule->condition_logic }}</dd></div>
            <div><dt class="text-gray-500">Priority</dt><dd class="font-medium">{{ $rule->priority }}</dd></div>
            <div><dt class="text-gray-500">Version</dt><dd class="font-medium">{{ $rule->version }}</dd></div>
            <div><dt class="text-gray-500">Status</dt>
                <dd>
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $rule->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $rule->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </dd>
            </div>
        </dl>
    </div>

    {{-- Conditions --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-3">WHEN (Conditions)</h3>
        @if($rule->conditions->count() > 0)
            <div class="space-y-2">
                @foreach($rule->conditions as $condition)
                    <div class="flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2 text-sm">
                        <span class="font-medium text-blue-700">{{ $condition->attribute->name }}</span>
                        <span class="text-gray-500">{{ str_replace('_', ' ', $condition->operator) }}</span>
                        <span class="font-mono text-gray-900 bg-white border border-gray-200 rounded px-2 py-0.5 text-xs">{{ $condition->value }}</span>
                        @if(!$loop->last)
                            <span class="text-xs font-bold text-gray-400">{{ $rule->condition_logic }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">No conditions (rule always applies).</p>
        @endif
    </div>

    {{-- Actions --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-3">THEN (Actions)</h3>
        @if($rule->actions->count() > 0)
            <div class="space-y-2">
                @foreach($rule->actions as $action)
                    <div class="flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2 text-sm">
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-orange-50 text-orange-700">{{ str_replace('_', ' ', $action->action_type) }}</span>
                        <span class="text-gray-500">{{ $action->target_type }}</span>
                        @if($action->target_id)
                            <span class="font-mono text-xs text-gray-600">#{{ $action->target_id }}</span>
                        @endif
                        @if($action->value)
                            <span class="text-gray-700 italic text-xs">{{ $action->value }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">No actions defined.</p>
        @endif
    </div>

    <div class="flex gap-3">
        <a href="{{ route('admin.rules.index') }}" class="border border-gray-300 text-gray-700 px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-50">← Back to Rules</a>
        <form method="POST" action="{{ route('admin.rules.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?')">
            @csrf @method('DELETE')
            <button class="border border-red-300 text-red-600 px-5 py-2 rounded-md text-sm font-medium hover:bg-red-50">Delete Rule</button>
        </form>
    </div>
</div>
@endsection

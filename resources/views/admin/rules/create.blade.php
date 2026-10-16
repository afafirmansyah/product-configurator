@extends('layouts.admin')
@section('title', 'Create Rule')
@section('heading', 'Create Rule for: ' . $product->name)
@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.rules.index') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rule Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="type" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['visibility','required','dependency','compatibility','exclusion','default_value','bom','pricing','part_number','validation','warning'] as $t)
                            <option value="{{ $t }}" {{ old('type') === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Condition Logic</label>
                    <select name="condition_logic" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="AND">AND (all conditions must match)</option>
                        <option value="OR">OR (any condition must match)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <input type="number" name="priority" value="{{ old('priority', 0) }}" min="0"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Conditions --}}
            <div class="border-t border-gray-100 pt-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">WHEN (Conditions)</h4>
                <div id="conditions" class="space-y-2">
                    <div class="grid grid-cols-3 gap-2 condition-row">
                        <select name="conditions[0][attribute_id]" class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">Select attribute...</option>
                            @foreach($attributes as $attr)
                                <option value="{{ $attr->id }}">{{ $attr->name }}</option>
                            @endforeach
                        </select>
                        <select name="conditions[0][operator]" class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                            @foreach(['equals','not_equals','in','not_in','greater_than','greater_than_or_equal','less_than','less_than_or_equal','is_empty','is_not_empty'] as $op)
                                <option value="{{ $op }}">{{ str_replace('_',' ',$op) }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="conditions[0][value]" placeholder="Value"
                               class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
                <button type="button" onclick="addCondition()" class="mt-2 text-sm text-blue-600 hover:underline">+ Add Condition</button>
            </div>

            {{-- Actions --}}
            <div class="border-t border-gray-100 pt-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">THEN (Actions)</h4>
                <div id="actions" class="space-y-2">
                    <div class="grid grid-cols-3 gap-2 action-row">
                        <select name="actions[0][action_type]" class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                            @foreach(['show','hide','require','optional','set_value','warn','error'] as $at)
                                <option value="{{ $at }}">{{ ucfirst(str_replace('_',' ',$at)) }}</option>
                            @endforeach
                        </select>
                        <select name="actions[0][target_type]" class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="attribute">Attribute</option>
                            <option value="option">Option</option>
                            <option value="component">Component</option>
                        </select>
                        <input type="text" name="actions[0][value]" placeholder="Target ID or message"
                               class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
                <button type="button" onclick="addAction()" class="mt-2 text-sm text-blue-600 hover:underline">+ Add Action</button>
                <p class="mt-1 text-xs text-gray-500">For add_component actions the value is the quantity to add (defaults to 1).</p>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked class="rounded">
                <label for="is_active" class="text-sm text-gray-700">Active</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-blue-800">Save Rule</button>
                <a href="{{ route('admin.rules.index') }}" class="border border-gray-300 text-gray-700 px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
let conditionCount = 1;
let actionCount = 1;

function addCondition() {
    const i = conditionCount++;
    const html = `<div class="grid grid-cols-3 gap-2 condition-row">
        <select name="conditions[${i}][attribute_id]" class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">Select attribute...</option>
            @foreach($attributes as $attr)
            <option value="{{ $attr->id }}">{{ $attr->name }}</option>
            @endforeach
        </select>
        <select name="conditions[${i}][operator]" class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="equals">equals</option>
            <option value="not_equals">not equals</option>
            <option value="greater_than">greater than</option>
            <option value="less_than">less than</option>
            <option value="in">in</option>
            <option value="is_empty">is empty</option>
            <option value="is_not_empty">is not empty</option>
        </select>
        <input type="text" name="conditions[${i}][value]" placeholder="Value"
               class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
    </div>`;
    document.getElementById('conditions').insertAdjacentHTML('beforeend', html);
}

function addAction() {
    const i = actionCount++;
    const html = `<div class="grid grid-cols-3 gap-2 action-row">
        <select name="actions[${i}][action_type]" class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="show">Show</option>
            <option value="hide">Hide</option>
            <option value="require">Require</option>
            <option value="optional">Optional</option>
            <option value="set_value">Set Value</option>
            <option value="warn">Warn</option>
            <option value="error">Error</option>
        </select>
        <select name="actions[${i}][target_type]" class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="attribute">Attribute</option>
            <option value="option">Option</option>
            <option value="component">Component</option>
        </select>
        <input type="text" name="actions[${i}][value]" placeholder="Target ID or message"
               class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
    </div>`;
    document.getElementById('actions').insertAdjacentHTML('beforeend', html);
}
</script>
@endsection

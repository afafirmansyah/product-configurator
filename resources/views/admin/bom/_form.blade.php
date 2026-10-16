{{--
    Shared BOM rule form.
    Expects: $action (string URL), $method ('POST'|'PUT'), $rule (?BomRule with conditions + items loaded),
             $attributes (product attributes), $components (selectable components).
--}}
@php
    $rule = $rule ?? null;

    $initialConditions = old('conditions', $rule
        ? $rule->conditions->map(fn ($condition) => [
            'attribute_id' => (string) $condition->attribute_id,
            'operator' => $condition->operator,
            'value' => $condition->value,
        ])->values()->all()
        : [['attribute_id' => '', 'operator' => 'equals', 'value' => '']]);

    $initialItems = old('items', $rule
        ? $rule->items->map(fn ($item) => [
            'component_id' => (string) $item->component_id,
            'quantity' => (string) $item->quantity,
            'is_mandatory' => (bool) $item->is_mandatory,
        ])->values()->all()
        : [['component_id' => '', 'quantity' => '1', 'is_mandatory' => true]]);

    $initialItems = collect($initialItems)->map(fn ($item) => [
        'component_id' => (string) ($item['component_id'] ?? ''),
        'quantity' => (string) ($item['quantity'] ?? '1'),
        'is_mandatory' => filter_var($item['is_mandatory'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ])->values()->all();

    $isActive = filter_var(old('is_active', $rule?->is_active ?? true), FILTER_VALIDATE_BOOLEAN);
    $conditionLogic = old('condition_logic', $rule?->condition_logic ?? 'AND');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5"
    x-data="{ conditions: @js(array_values($initialConditions)), items: @js($initialItems) }">
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif

    @if($errors->any())
        <x-ui.alert type="danger">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="bom-rule-name" class="form-label">Rule Name *</label>
            <input type="text" id="bom-rule-name" name="name" required class="form-input"
                value="{{ old('name', $rule?->name) }}" placeholder="e.g. BOM - Cellular Communication">
        </div>
        <div>
            <label for="bom-rule-logic" class="form-label">Condition Logic</label>
            <select id="bom-rule-logic" name="condition_logic" class="form-select">
                <option value="AND" @selected($conditionLogic === 'AND')>AND — all conditions must match</option>
                <option value="OR" @selected($conditionLogic === 'OR')>OR — any condition must match</option>
            </select>
        </div>
        <div class="flex items-end">
            <label class="flex items-center gap-2 text-sm text-slate-700 pb-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($isActive) class="w-4 h-4 rounded" style="accent-color:#2563eb">
                Active
            </label>
        </div>
    </div>

    {{-- Conditions --}}
    <div>
        <div class="flex items-center justify-between mb-2">
            <span class="form-label mb-0">Conditions <span class="text-slate-400 font-normal">(leave empty to always apply)</span></span>
            <button type="button" @click="conditions.push({ attribute_id: '', operator: 'equals', value: '' })" class="btn btn-ghost btn-sm text-blue-600">
                <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
                Add
            </button>
        </div>
        <div class="space-y-2">
            <template x-for="(cond, i) in conditions" :key="i">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-center">
                    <select :name="`conditions[${i}][attribute_id]`" x-model="cond.attribute_id" class="form-select" aria-label="Condition attribute">
                        <option value="">Select attribute...</option>
                        @foreach($attributes as $attr)
                            <option value="{{ $attr->id }}">{{ $attr->name }}</option>
                        @endforeach
                    </select>
                    <select :name="`conditions[${i}][operator]`" x-model="cond.operator" class="form-select" aria-label="Condition operator">
                        <option value="equals">equals</option>
                        <option value="not_equals">not equals</option>
                        <option value="greater_than">greater than</option>
                        <option value="less_than">less than</option>
                        <option value="in">is one of</option>
                    </select>
                    <div class="flex gap-1">
                        <input type="text" :name="`conditions[${i}][value]`" x-model="cond.value" class="form-input" placeholder="Value" aria-label="Condition value">
                        <button type="button" @click="conditions.splice(i,1)" class="btn btn-ghost btn-sm flex-shrink-0" aria-label="Remove condition">
                            <x-ui.icon name="x" class="w-3.5 h-3.5 text-red-400"/>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Items --}}
    <div>
        <div class="flex items-center justify-between mb-2">
            <span class="form-label mb-0">Components to Add *</span>
            <button type="button" @click="items.push({ component_id: '', quantity: '1', is_mandatory: true })" class="btn btn-ghost btn-sm text-blue-600">
                <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
                Add Component
            </button>
        </div>
        <div class="space-y-2">
            <template x-for="(item, i) in items" :key="i">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 items-center">
                    <select :name="`items[${i}][component_id]`" x-model="item.component_id" required class="form-select" aria-label="Component">
                        <option value="">Select component...</option>
                        @foreach($components as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" :name="`items[${i}][quantity]`" x-model="item.quantity" min="0.01" step="0.01" required class="form-input" placeholder="Qty" aria-label="Quantity">
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-1.5 text-sm text-slate-600">
                            <input type="checkbox" :name="`items[${i}][is_mandatory]`" value="1" x-model="item.is_mandatory" class="w-3.5 h-3.5 rounded">
                            Mandatory
                        </label>
                        <button type="button" @click="items.splice(i,1)" class="btn btn-ghost btn-sm ml-auto" aria-label="Remove component">
                            <x-ui.icon name="x" class="w-3.5 h-3.5 text-red-400"/>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary btn-md">Save BOM Rule</button>
        <a href="{{ route('admin.bom.index') }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</form>

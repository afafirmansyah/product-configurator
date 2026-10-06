@extends('layouts.admin')
@section('title', 'Create BOM Rule')
@section('heading', 'Create BOM Rule for: ' . $product->name)
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.bom.store', $product) }}" class="space-y-5" x-data="{ conditions: [{}], items: [{}] }">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">Rule Name *</label>
                        <input type="text" name="name" required class="form-input" placeholder="e.g. BOM - Cellular Communication">
                    </div>
                    <div>
                        <label class="form-label">Condition Logic</label>
                        <select name="condition_logic" class="form-select">
                            <option value="AND">AND — all conditions must match</option>
                            <option value="OR">OR — any condition must match</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-slate-700 pb-2">
                            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded" style="accent-color:#2563eb">
                            Active
                        </label>
                    </div>
                </div>

                {{-- Conditions --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="form-label mb-0">Conditions <span class="text-slate-400 font-normal">(leave empty to always apply)</span></label>
                        <button type="button" @click="conditions.push({})" class="btn btn-ghost btn-sm text-blue-600">
                            <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
                            Add
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(cond, i) in conditions" :key="i">
                            <div class="grid grid-cols-3 gap-2 items-center">
                                <select :name="`conditions[${i}][attribute_id]`" class="form-select">
                                    <option value="">Select attribute...</option>
                                    @foreach($attributes as $attr)
                                        <option value="{{ $attr->id }}">{{ $attr->name }}</option>
                                    @endforeach
                                </select>
                                <select :name="`conditions[${i}][operator]`" class="form-select">
                                    <option value="equals">equals</option>
                                    <option value="not_equals">not equals</option>
                                    <option value="greater_than">greater than</option>
                                    <option value="less_than">less than</option>
                                    <option value="in">is one of</option>
                                </select>
                                <div class="flex gap-1">
                                    <input type="text" :name="`conditions[${i}][value]`" class="form-input" placeholder="Value">
                                    <button type="button" @click="conditions.splice(i,1)" class="btn btn-ghost btn-sm flex-shrink-0">
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
                        <label class="form-label mb-0">Components to Add *</label>
                        <button type="button" @click="items.push({})" class="btn btn-ghost btn-sm text-blue-600">
                            <x-ui.icon name="plus" class="w-3.5 h-3.5"/>
                            Add Component
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(item, i) in items" :key="i">
                            <div class="grid grid-cols-3 gap-2 items-center">
                                <select :name="`items[${i}][component_id]`" required class="form-select col-span-1">
                                    <option value="">Select component...</option>
                                    @foreach($components as $comp)
                                        <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                                    @endforeach
                                </select>
                                <input type="number" :name="`items[${i}][quantity]`" value="1" min="0.01" step="0.01" required class="form-input" placeholder="Qty">
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-1.5 text-sm text-slate-600">
                                        <input type="checkbox" :name="`items[${i}][is_mandatory]`" value="1" checked class="w-3.5 h-3.5 rounded">
                                        Mandatory
                                    </label>
                                    <button type="button" @click="items.splice(i,1)" class="btn btn-ghost btn-sm ml-auto">
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
        </div>
    </div>
</div>
@endsection

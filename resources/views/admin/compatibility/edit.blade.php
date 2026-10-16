@extends('layouts.admin')
@section('title', 'Edit Compatibility Rule')
@section('heading', 'Edit Compatibility Rule')
@section('content')
    @php
        $selectedType = old('type', $rule->type);
        $isActive = filter_var(old('is_active', $rule->is_active), FILTER_VALIDATE_BOOLEAN);
        $initialState = [
            'productId' => (string) old('product_id', $rule->product_id),
            'attrId' => (string) old('attribute_id', $rule->attribute_id),
            'optionId' => (string) old('option_id', $rule->option_id),
            'targetAttrId' => (string) old('target_attribute_id', $rule->target_attribute_id),
            'targetOptionId' => (string) old('target_option_id', $rule->target_option_id),
        ];
    @endphp
    <div class="max-w-2xl">
        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-semibold text-slate-800">Rule Definition</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.compatibility.update', $rule) }}" class="space-y-5"
                    x-data="compatEditForm(@js($initialState))">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <x-ui.alert type="danger">
                            <ul class="list-disc pl-4 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert>
                    @endif

                    <div>
                        <label for="compat-product" class="form-label">Product *</label>
                        <select id="compat-product" name="product_id" required class="form-select" x-model="productId"
                            @change="loadAttributes">
                            <option value="">Select product...</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}"
                                    data-attrs="{{ $p->attributes->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'options' => $a->options->map(fn($o) => ['id' => $o->id, 'name' => $o->name])])->toJson() }}">
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-lg"
                        style="background:#f8fafc;border:1px solid #e2e8f0">
                        <div class="sm:col-span-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">WHEN this
                            option is selected</div>
                        <div>
                            <label for="compat-attribute" class="form-label">Attribute *</label>
                            <select id="compat-attribute" name="attribute_id" required class="form-select" x-model="attrId">
                                <option value="">Select attribute...</option>
                                <template x-for="attr in attributes" :key="attr.id">
                                    <option :value="attr.id" x-text="attr.name" :selected="attr.id == attrId">
                                    </option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label for="compat-option" class="form-label">Option *</label>
                            <select id="compat-option" name="option_id" required class="form-select" x-model="optionId">
                                <option value="">Select option...</option>
                                <template x-for="opt in getOptions(attrId)" :key="opt.id">
                                    <option :value="opt.id" x-text="opt.name" :selected="opt.id == optionId">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <span class="form-label">Compatibility Type *</span>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="incompatible" @checked($selectedType === 'incompatible')
                                    class="w-4 h-4" style="accent-color:#dc2626">
                                <span class="text-sm font-medium text-red-600">Incompatible</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="type" value="compatible" @checked($selectedType === 'compatible')
                                    class="w-4 h-4" style="accent-color:#16a34a">
                                <span class="text-sm font-medium text-green-600">Compatible</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-lg"
                        style="background:#f8fafc;border:1px solid #e2e8f0">
                        <div class="sm:col-span-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">WITH this
                            target option</div>
                        <div>
                            <label for="compat-target-attribute" class="form-label">Target Attribute *</label>
                            <select id="compat-target-attribute" name="target_attribute_id" required class="form-select"
                                x-model="targetAttrId">
                                <option value="">Select attribute...</option>
                                <template x-for="attr in attributes" :key="attr.id">
                                    <option :value="attr.id" x-text="attr.name" :selected="attr.id == targetAttrId">
                                    </option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label for="compat-target-option" class="form-label">Target Option *</label>
                            <select id="compat-target-option" name="target_option_id" required class="form-select"
                                x-model="targetOptionId">
                                <option value="">Select option...</option>
                                <template x-for="opt in getOptions(targetAttrId)" :key="opt.id">
                                    <option :value="opt.id" x-text="opt.name" :selected="opt.id == targetOptionId">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="compat-reason" class="form-label">Reason <span class="text-slate-400 font-normal">(shown
                                to users)</span></label>
                        <input type="text" id="compat-reason" name="reason" class="form-input"
                            value="{{ old('reason', $rule->reason) }}"
                            placeholder="e.g. Cellular modem is required for 4G communication">
                    </div>
                    <div>
                        <label for="compat-note" class="form-label">Engineering Note <span
                                class="text-slate-400 font-normal">(internal)</span></label>
                        <textarea id="compat-note" name="engineering_note" rows="2" class="form-textarea">{{ old('engineering_note', $rule->engineering_note) }}</textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                            @checked($isActive) class="w-4 h-4 rounded" style="accent-color:#2563eb">
                        <label for="is_active" class="text-sm text-slate-700">Active</label>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="btn btn-primary btn-md">Update Rule</button>
                        <a href="{{ route('admin.compatibility.index') }}" class="btn btn-secondary btn-md">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function compatEditForm(initial) {
            return {
                productId: initial.productId,
                attrId: initial.attrId,
                optionId: initial.optionId,
                targetAttrId: initial.targetAttrId,
                targetOptionId: initial.targetOptionId,
                attributes: [],
                init() {
                    this.loadAttributes();
                },
                loadAttributes() {
                    const sel = document.querySelector(`select[name=product_id] option[value="${this.productId}"]`);
                    this.attributes = sel ? JSON.parse(sel.dataset.attrs || '[]') : [];
                },
                getOptions(attrId) {
                    const attr = this.attributes.find(a => a.id == attrId);
                    return attr ? attr.options : [];
                }
            }
        }
    </script>
@endsection

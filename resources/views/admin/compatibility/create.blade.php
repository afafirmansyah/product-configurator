@extends('layouts.admin')
@section('title', 'Create Compatibility Rule')
@section('heading', 'Create Compatibility Rule')
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-header">
            <h2 class="text-sm font-semibold text-slate-800">Rule Definition</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.compatibility.store') }}" class="space-y-5" x-data="compatForm()">
                @csrf

                <div>
                    <label class="form-label">Product *</label>
                    <select name="product_id" required class="form-select" x-model="productId" @change="loadAttributes">
                        <option value="">Select product...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" data-attrs="{{ $p->attributes->map(fn($a) => ['id'=>$a->id,'name'=>$a->name,'options'=>$a->options->map(fn($o)=>['id'=>$o->id,'name'=>$o->name])])->toJson() }}">
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 p-4 rounded-lg" style="background:#f8fafc;border:1px solid #e2e8f0">
                    <div class="col-span-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">WHEN this option is selected</div>
                    <div>
                        <label class="form-label">Attribute *</label>
                        <select name="attribute_id" required class="form-select" x-model="attrId">
                            <option value="">Select attribute...</option>
                            <template x-for="attr in attributes" :key="attr.id">
                                <option :value="attr.id" x-text="attr.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Option *</label>
                        <select name="option_id" required class="form-select">
                            <option value="">Select option...</option>
                            <template x-for="opt in getOptions(attrId)" :key="opt.id">
                                <option :value="opt.id" x-text="opt.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label">Compatibility Type *</label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="type" value="incompatible" checked class="w-4 h-4" style="accent-color:#dc2626">
                            <span class="text-sm font-medium text-red-600">Incompatible</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="type" value="compatible" class="w-4 h-4" style="accent-color:#16a34a">
                            <span class="text-sm font-medium text-green-600">Compatible</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 p-4 rounded-lg" style="background:#f8fafc;border:1px solid #e2e8f0">
                    <div class="col-span-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">WITH this target option</div>
                    <div>
                        <label class="form-label">Target Attribute *</label>
                        <select name="target_attribute_id" required class="form-select" x-model="targetAttrId">
                            <option value="">Select attribute...</option>
                            <template x-for="attr in attributes" :key="attr.id">
                                <option :value="attr.id" x-text="attr.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Target Option *</label>
                        <select name="target_option_id" required class="form-select">
                            <option value="">Select option...</option>
                            <template x-for="opt in getOptions(targetAttrId)" :key="opt.id">
                                <option :value="opt.id" x-text="opt.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="form-label">Reason <span class="text-slate-400 font-normal">(shown to users)</span></label>
                    <input type="text" name="reason" class="form-input" placeholder="e.g. Cellular modem is required for 4G communication">
                </div>
                <div>
                    <label class="form-label">Engineering Note <span class="text-slate-400 font-normal">(internal)</span></label>
                    <textarea name="engineering_note" rows="2" class="form-textarea"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 rounded" style="accent-color:#2563eb">
                    <label for="is_active" class="text-sm text-slate-700">Active</label>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary btn-md">Save Rule</button>
                    <a href="{{ route('admin.compatibility.index') }}" class="btn btn-secondary btn-md">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function compatForm() {
    return {
        productId: '',
        attrId: '',
        targetAttrId: '',
        attributes: [],
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

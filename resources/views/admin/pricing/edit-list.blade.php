@extends('layouts.admin')
@section('title', 'Edit Price List')
@section('heading', 'Edit Price List: ' . $priceList->name)
@section('content')
<div class="max-w-3xl space-y-4">
    <div class="card">
        <div class="card-header"><h3 class="text-sm font-semibold text-slate-800">Price List Settings</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.pricing.lists.update', $priceList) }}" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $priceList->name) }}" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $priceList->code) }}" required class="form-input font-mono">
                    </div>
                    <div>
                        <label class="form-label">Currency *</label>
                        <select name="currency" class="form-select">
                            @foreach(['EUR','USD','IDR','SGD','INR'] as $cur)
                                <option value="{{ $cur }}" {{ $priceList->currency === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-4 pb-1">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_default" value="1" {{ $priceList->is_default ? 'checked' : '' }} class="w-4 h-4 rounded" style="accent-color:#2563eb">
                            Default
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_active" value="1" {{ $priceList->is_active ? 'checked' : '' }} class="w-4 h-4 rounded" style="accent-color:#2563eb">
                            Active
                        </label>
                    </div>
                </div>

                {{-- Product Prices --}}
                <div class="border-t pt-4" style="border-color:#e2e8f0">
                    <h4 class="text-sm font-semibold text-slate-700 mb-3">Product Base Prices</h4>
                    <div class="space-y-2">
                        @foreach($products as $product)
                            @php $pp = $priceList->productPrices->firstWhere('product_id', $product->id); @endphp
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-slate-700 flex-1">{{ $product->name }}</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-slate-400">{{ $priceList->currency }}</span>
                                    <input type="number" name="product_prices[{{ $product->id }}]"
                                           value="{{ $pp?->base_price ?? $product->base_price }}"
                                           step="0.01" min="0"
                                           class="form-input w-32 text-right">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary btn-md">Save Changes</button>
                    <a href="{{ route('admin.pricing.index') }}" class="btn btn-secondary btn-md">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

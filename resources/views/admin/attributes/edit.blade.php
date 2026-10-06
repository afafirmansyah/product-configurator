@extends('layouts.admin')
@section('title', 'Edit Attribute')
@section('heading', 'Edit Attribute: ' . $attribute->name)
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Attribute Form --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Attribute Details</h3>
        <form method="POST" action="{{ route('admin.attributes.update', $attribute) }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" value="{{ old('name', $attribute->name) }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                <input type="text" name="code" value="{{ old('code', $attribute->code) }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                <select name="type" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" {{ $attribute->type === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                <input type="text" name="unit" value="{{ old('unit', $attribute->unit) }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $attribute->description) }}</textarea>
            </div>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_global" value="1" {{ $attribute->is_global ? 'checked' : '' }} class="rounded">
                    Global
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" {{ $attribute->is_active ? 'checked' : '' }} class="rounded">
                    Active
                </label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-blue-800">Update</button>
                <a href="{{ route('admin.attributes.index') }}" class="border border-gray-300 text-gray-700 px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-50">Back</a>
            </div>
        </form>
    </div>

    {{-- Options --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Options ({{ $attribute->options->count() }})</h3>
        </div>

        @if($attribute->options->count() > 0)
            <div class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                @foreach($attribute->options as $option)
                    <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2 border border-gray-100">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ $option->name }}</span>
                            <span class="text-xs text-gray-400 ml-2 font-mono">{{ $option->code }}</span>
                            @if($option->price_adjustment != 0)
                                <span class="text-xs ml-2 {{ $option->price_adjustment > 0 ? 'text-orange-600' : 'text-green-600' }}">
                                    {{ $option->price_adjustment > 0 ? '+' : '' }}{{ number_format($option->price_adjustment, 0) }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs {{ $option->is_active ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $option->is_active ? '●' : '○' }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Add Option Form --}}
        <div class="border-t border-gray-100 pt-4">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Add Option</h4>
            <form method="POST" action="{{ route('admin.attributes.update', $attribute) }}" class="space-y-3">
                @csrf @method('PUT')
                {{-- Hidden fields to preserve attribute data --}}
                <input type="hidden" name="name" value="{{ $attribute->name }}">
                <input type="hidden" name="code" value="{{ $attribute->code }}">
                <input type="hidden" name="type" value="{{ $attribute->type }}">
                <input type="hidden" name="unit" value="{{ $attribute->unit }}">
                <input type="hidden" name="is_active" value="1">

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Option Name *</label>
                        <input type="text" name="options[0][name]" required
                               class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Code *</label>
                        <input type="text" name="options[0][code]" required
                               class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Price Adjustment</label>
                        <input type="number" name="options[0][price_adjustment]" value="0" step="0.01"
                               class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Part Number</label>
                        <input type="text" name="options[0][part_number]"
                               class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                    <input type="text" name="options[0][description]"
                           class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <input type="hidden" name="options[0][sort_order]" value="{{ $attribute->options->count() }}">
                <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded-md text-sm font-medium hover:bg-gray-900">
                    + Add Option
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

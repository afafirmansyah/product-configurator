@extends('layouts.admin')
@section('title', 'Create Attribute')
@section('heading', 'Create Attribute')
@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.attributes.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                <input type="text" name="code" value="{{ old('code') }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror">
                <p class="text-xs text-gray-400 mt-1">Lowercase, underscores only. e.g. tank_quantity</p>
                @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                <select name="type" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" {{ old('type') === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                <input type="text" name="unit" value="{{ old('unit') }}" placeholder="e.g. Tanks, kg, mm"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
            </div>
            <fieldset class="border border-gray-200 rounded-lg p-4 space-y-3">
                <legend class="px-1 text-sm font-medium text-gray-700">Validation</legend>
                <p class="text-xs text-gray-400">Checked when a value is entered in the configurator. Leave blank for no extra rules.</p>
                <div>
                    <label for="validation-type" class="block text-xs font-medium text-gray-600 mb-1">Value type</label>
                    <select id="validation-type" name="validation_rules[type]" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('validation_rules.type') border-red-500 @enderror">
                        <option value="">Any</option>
                        <option value="numeric" @selected(old('validation_rules.type') === 'numeric')>Number</option>
                        <option value="integer" @selected(old('validation_rules.type') === 'integer')>Whole number</option>
                    </select>
                    @error('validation_rules.type')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="validation-min" class="block text-xs font-medium text-gray-600 mb-1">Minimum</label>
                        <input type="number" step="any" id="validation-min" name="validation_rules[min]" value="{{ old('validation_rules.min') }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('validation_rules.min') border-red-500 @enderror">
                        @error('validation_rules.min')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="validation-max" class="block text-xs font-medium text-gray-600 mb-1">Maximum</label>
                        <input type="number" step="any" id="validation-max" name="validation_rules[max]" value="{{ old('validation_rules.max') }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('validation_rules.max') border-red-500 @enderror">
                        @error('validation_rules.max')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="hidden" name="validation_rules[required]" value="0">
                    <input type="checkbox" name="validation_rules[required]" value="1" @checked(old('validation_rules.required')) class="rounded">
                    Always required when visible
                </label>
            </fieldset>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_global" value="1" {{ old('is_global') ? 'checked' : '' }} class="rounded">
                    Global (shared across products)
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded">
                    Active
                </label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-blue-800">Save & Add Options</button>
                <a href="{{ route('admin.attributes.index') }}" class="border border-gray-300 text-gray-700 px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

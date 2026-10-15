<div class="grid grid-cols-12 gap-4 min-h-screen" wire:loading.class="opacity-95">

    {{-- Global busy indicator: visible while any Livewire request is in flight --}}
    <div wire:loading.flex
        class="fixed top-4 right-4 z-50 items-center gap-2 bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-full shadow-lg"
        role="status" aria-live="polite">
        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
            aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
            </circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <span>Updating configuration…</span>
    </div>

    {{-- Step Navigation (left) --}}
    <div class="col-span-12 md:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 p-4 sticky top-20">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Steps</div>
            <div class="space-y-1">
                @foreach ($steps as $i => $step)
                    <button wire:click="$set('currentStep', {{ $i }})"
                        class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-colors
                                   {{ $showSummary ? 'text-gray-400' : ($i === $currentStep ? 'bg-blue-50 text-blue-700 font-medium' : ($i < $currentStep ? 'text-green-600' : 'text-gray-500 hover:bg-gray-50')) }}">
                        <span
                            class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                                     {{ $showSummary ? 'bg-gray-100 text-gray-400' : ($i < $currentStep ? 'bg-green-100 text-green-600' : ($i === $currentStep ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400')) }}">
                            {{ $i < $currentStep ? '✓' : $i + 1 }}
                        </span>
                        <span class="truncate">{{ $step }}</span>
                    </button>
                @endforeach
                <button wire:click="$set('showSummary', true)"
                    class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-colors
                               {{ $showSummary ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-500 hover:bg-gray-50' }}">
                    <span
                        class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                                 {{ $showSummary ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400' }}">
                        ✓
                    </span>
                    Review
                </button>
            </div>
        </div>
    </div>

    {{-- Configuration Options (center) --}}
    <div class="col-span-12 md:col-span-7">
        @if (!$showSummary)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-5">
                    {{ $steps[$currentStep] ?? 'Configuration' }}
                </h2>

                <div class="space-y-6">
                    @foreach ($stepAttributes as $pa)
                        @php
                            $attr = $pa->attribute;
                            $attrId = $attr->id;
                            $isVisible = !$result || in_array($attrId, $result['visible_attributes']);
                            $isRequired = $result && in_array($attrId, $result['required_attributes']);
                            $disabledOpts = $result['disabled_options'][$attrId] ?? [];
                        @endphp

                        @if ($isVisible)
                            @php $helpId = 'help-'.$attrId; @endphp
                            @if (in_array($attr->type, ['select', 'radio']))
                                <fieldset wire:key="attr-{{ $attrId }}"
                                    @if ($pa->help_text) aria-describedby="{{ $helpId }}" @endif>
                                    <legend class="block text-sm font-medium text-gray-900 mb-1">
                                        {{ $attr->name }}
                                        @if ($isRequired || $pa->is_required)
                                            <span class="text-red-500" aria-hidden="true">*</span>
                                            <span class="sr-only">(required)</span>
                                        @endif
                                        @if ($attr->unit)
                                            <span class="text-gray-400 font-normal">({{ $attr->unit }})</span>
                                        @endif
                                    </legend>

                                    @if ($pa->help_text)
                                        <p id="{{ $helpId }}" class="text-xs text-gray-500 mb-2">
                                            {{ $pa->help_text }}</p>
                                    @endif

                                    <div class="grid grid-cols-1 gap-2" role="radiogroup"
                                        aria-label="{{ $attr->name }}">
                                        @foreach ($attr->options->where('is_active', true) as $option)
                                            @php
                                                $isDisabled = isset($disabledOpts[$option->id]);
                                                $isSelected =
                                                    !$isDisabled &&
                                                    (string) ($selections[$attrId] ?? '') === (string) $option->id;
                                                $reasonId = 'reason-' . $attrId . '-' . $option->id;
                                            @endphp
                                            <label
                                                @if ($isDisabled) title="{{ $disabledOpts[$option->id] }}" @endif
                                                class="flex items-center gap-3 p-3 rounded-lg border transition-colors
                                                          focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500
                                                          {{ $isDisabled ? 'border-gray-100 bg-gray-50 opacity-50 cursor-not-allowed' : 'cursor-pointer' }}
                                                          {{ $isSelected ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : (!$isDisabled ? 'border-gray-200 hover:border-blue-300' : '') }}">
                                                <input type="radio" wire:model.live="selections.{{ $attrId }}"
                                                    value="{{ $option->id }}" {{ $isDisabled ? 'disabled' : '' }}
                                                    @if ($isDisabled) aria-describedby="{{ $reasonId }}" @endif
                                                    class="text-blue-600">
                                                <div class="flex-1">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $option->name }}
                                                        @if ($isSelected)
                                                            <span class="sr-only">(selected)</span>
                                                        @endif
                                                    </div>
                                                    @if ($option->description)
                                                        <div class="text-xs text-gray-500">{{ $option->description }}
                                                        </div>
                                                    @endif
                                                    @if ($isDisabled)
                                                        <div id="{{ $reasonId }}"
                                                            class="text-xs text-red-500 mt-0.5">
                                                            {{ $disabledOpts[$option->id] }}</div>
                                                    @endif
                                                </div>
                                                @if ($option->price_adjustment != 0)
                                                    <span
                                                        class="text-xs font-medium {{ $option->price_adjustment > 0 ? 'text-orange-600' : 'text-green-600' }}">
                                                        {{ $option->price_adjustment > 0 ? '+' : '' }}{{ number_format($option->price_adjustment, 0) }}
                                                    </span>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @else
                                <div wire:key="attr-{{ $attrId }}">
                                    <label for="attr-input-{{ $attrId }}"
                                        class="block text-sm font-medium text-gray-900 mb-1">
                                        {{ $attr->name }}
                                        @if ($isRequired || $pa->is_required)
                                            <span class="text-red-500" aria-hidden="true">*</span>
                                            <span class="sr-only">(required)</span>
                                        @endif
                                        @if ($attr->unit)
                                            <span class="text-gray-400 font-normal">({{ $attr->unit }})</span>
                                        @endif
                                    </label>

                                    @if ($pa->help_text)
                                        <p id="{{ $helpId }}" class="text-xs text-gray-500 mb-2">
                                            {{ $pa->help_text }}</p>
                                    @endif

                                    @if ($attr->type === 'integer')
                                        <input type="number" id="attr-input-{{ $attrId }}"
                                            wire:model.live.debounce.400ms="selections.{{ $attrId }}"
                                            @if ($pa->help_text) aria-describedby="{{ $helpId }}" @endif
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @elseif($attr->type === 'boolean')
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" id="attr-input-{{ $attrId }}"
                                                wire:model.live="selections.{{ $attrId }}"
                                                class="w-4 h-4 text-blue-600 rounded">
                                            <span class="text-sm text-gray-700">Enable {{ $attr->name }}</span>
                                        </label>
                                    @else
                                        <input type="text" id="attr-input-{{ $attrId }}"
                                            wire:model.live.debounce.400ms="selections.{{ $attrId }}"
                                            @if ($pa->help_text) aria-describedby="{{ $helpId }}" @endif
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @endif
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>

                {{-- Navigation --}}
                <div class="flex justify-between mt-8 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="previousStep"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50 {{ $currentStep === 0 ? 'invisible' : '' }}">
                        ← Previous
                    </button>
                    <button type="button" wire:click="nextStep" wire:loading.attr="disabled" wire:target="nextStep"
                        class="px-6 py-2 bg-blue-700 text-white rounded-md text-sm font-medium hover:bg-blue-800 disabled:opacity-60 disabled:cursor-wait">
                        {{ $currentStep === count($steps) - 1 ? 'Review →' : 'Next →' }}
                    </button>
                </div>
            </div>
        @else
            {{-- Review Step --}}
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Configuration Review</h2>

                    {{-- Validation Status --}}
                    @if ($result)
                        @if ($result['validation']['valid'])
                            <div
                                class="flex items-center gap-2 text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3 mb-4">
                                <span class="text-lg">✓</span>
                                <span class="font-medium">Configuration Valid</span>
                            </div>
                        @else
                            <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4">
                                <div class="flex items-center gap-2 text-red-700 font-medium mb-2">
                                    <span>⚠</span> Configuration Incomplete
                                </div>
                                <ul class="text-sm text-red-600 space-y-1">
                                    @foreach ($result['validation']['errors'] as $error)
                                        <li>• {{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

                    {{-- Selected Options --}}
                    <div class="space-y-2">
                        @foreach ($product->productAttributes as $pa)
                            @php
                                $attr = $pa->attribute;
                                $val = $selections[$attr->id] ?? null;
                                if (!$val) {
                                    continue;
                                }
                                $displayVal = $val;
                                if ($attr->options->isNotEmpty() && is_numeric($val)) {
                                    $opt = $attr->options->firstWhere('id', $val);
                                    $displayVal = $opt?->name ?? $val;
                                }
                            @endphp
                            <div class="flex justify-between text-sm py-1 border-b border-gray-50">
                                <span class="text-gray-600">{{ $attr->name }}</span>
                                <span class="font-medium text-gray-900">{{ $displayVal }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Technical Specs --}}
                @if ($result && count($result['technical_specifications']) > 0)
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Technical Specifications</h3>
                        <div class="space-y-1">
                            @foreach ($result['technical_specifications'] as $spec)
                                <div class="flex justify-between text-sm py-1 border-b border-gray-50">
                                    <span class="text-gray-600">{{ $spec['attribute'] }}</span>
                                    <span
                                        class="font-medium text-gray-900">{{ $spec['value'] }}{{ $spec['unit'] ? ' ' . $spec['unit'] : '' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- BOM --}}
                @if ($result && count($result['bom']) > 0)
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Bill of Materials</h3>
                        <div class="space-y-2">
                            @foreach ($result['bom'] as $item)
                                <div class="flex items-center justify-between text-sm py-1 border-b border-gray-50">
                                    <div>
                                        <span class="font-medium text-gray-900">{{ $item['quantity'] }} ×
                                            {{ $item['name'] }}</span>
                                        <span class="text-gray-400 text-xs ml-2">{{ $item['code'] }}</span>
                                    </div>
                                    <span
                                        class="text-gray-600">{{ number_format($item['unit_price'] * $item['quantity'], 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @error('configuration')
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-600" role="alert">
                        {{ $message }}
                    </div>
                @enderror

                <div class="flex justify-between">
                    <button type="button" wire:click="previousStep"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50">
                        ← Back
                    </button>
                    @php $canSave = $result && $result['validation']['valid']; @endphp
                    <button type="button" wire:click="saveConfiguration" wire:loading.attr="disabled"
                        wire:target="saveConfiguration" @disabled(!$canSave)
                        @if (!$canSave) title="Resolve the validation errors before saving." @endif
                        class="px-6 py-2 rounded-md text-sm font-medium text-white transition-colors disabled:opacity-60 disabled:cursor-not-allowed
                               {{ $canSave ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed' }}">
                        <span wire:loading.remove wire:target="saveConfiguration">Save Configuration</span>
                        <span wire:loading wire:target="saveConfiguration">Saving…</span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Summary Panel (right) --}}
    <div class="col-span-12 md:col-span-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4 sticky top-20">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Summary</div>
            <div class="font-semibold text-gray-900 mb-1">{{ $product->name }}</div>

            @if ($result && $result['configuration_code'] !== $product->code)
                <div class="font-mono text-xs text-blue-700 bg-blue-50 rounded px-2 py-1 mb-3">
                    {{ $result['configuration_code'] }}
                </div>
            @endif

            {{-- Selected options summary --}}
            <div class="space-y-1 mb-4">
                @foreach ($product->productAttributes->take(6) as $pa)
                    @php
                        $val = $selections[$pa->attribute_id] ?? null;
                        if (!$val) {
                            continue;
                        }
                        $attr = $pa->attribute;
                        $displayVal = $val;
                        if ($attr->options->isNotEmpty() && is_numeric($val)) {
                            $opt = $attr->options->firstWhere('id', $val);
                            $displayVal = $opt?->name ?? $val;
                        }
                    @endphp
                    <div class="text-xs text-gray-600 flex justify-between">
                        <span class="text-gray-400">{{ $attr->name }}</span>
                        <span class="font-medium truncate ml-2">{{ $displayVal }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Price --}}
            @if ($result)
                <div class="border-t border-gray-100 pt-3 space-y-1">
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Base Price</span>
                        <span>{{ number_format($result['pricing']['base_price'], 0) }}</span>
                    </div>
                    @if ($result['pricing']['options_total'] != 0)
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Options</span>
                            <span>{{ $result['pricing']['options_total'] > 0 ? '+' : '' }}{{ number_format($result['pricing']['options_total'], 0) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm font-bold text-gray-900 pt-1 border-t border-gray-100">
                        <span>Total</span>
                        <span>{{ $result['pricing']['currency'] }}
                            {{ number_format($result['pricing']['total'], 0) }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Main Form --}}
    <div class="xl:col-span-2 space-y-6">

        @if(session('success'))
            <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- General Info --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">General Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                    <input wire:model="name" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Code *</label>
                    <input wire:model="code" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input wire:model="sku" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Family *</label>
                    <select wire:model="product_family_id" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select family...</option>
                        @foreach($families as $family)
                            <option value="{{ $family->id }}">{{ $family->category->name }} / {{ $family->name }}</option>
                        @endforeach
                    </select>
                    @error('product_family_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer</label>
                    <input wire:model="manufacturer" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
                    <textarea wire:model="short_description" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Long Description</label>
                    <textarea wire:model="long_description" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base Price *</label>
                    <div class="flex gap-2">
                        <input wire:model="base_price" type="number" step="0.01" min="0" class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <select wire:model="currency" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option>EUR</option><option>USD</option><option>IDR</option><option>SGD</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Version</label>
                    <input wire:model="version" type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        {{-- Attributes --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Configuration Attributes</h3>

            {{-- Assigned attributes --}}
            @if(count($assignedAttributes) > 0)
                <div class="space-y-2 mb-4">
                    @foreach($assignedAttributes as $index => $pa)
                        <div class="flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                            <span class="text-gray-400 text-xs w-5">{{ $index + 1 }}</span>
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-900">{{ $pa['name'] }}</span>
                                <span class="text-xs text-gray-400 ml-2">{{ $pa['code'] }} · {{ $pa['type'] }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-1 text-xs text-gray-600">
                                    <input type="checkbox" wire:model="assignedAttributes.{{ $index }}.is_required" class="rounded">
                                    Required
                                </label>
                                <input wire:model="assignedAttributes.{{ $index }}.step_name" type="text"
                                       placeholder="Step name"
                                       class="border border-gray-300 rounded px-2 py-1 text-xs w-28 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <button wire:click="removeAttribute({{ $index }})" class="text-red-400 hover:text-red-600 text-xs">✕</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Add attribute --}}
            <div class="border-t border-gray-100 pt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Add Attribute</label>
                <div class="flex gap-2">
                    <input wire:model.live="attributeSearch" type="text" placeholder="Search attributes..."
                           class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                @if($attributeSearch)
                    <div class="mt-2 border border-gray-200 rounded-md divide-y divide-gray-100 max-h-48 overflow-y-auto">
                        @foreach($availableAttributes as $attr)
                            <button wire:click="addAttribute({{ $attr->id }})"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 flex items-center justify-between">
                                <span>{{ $attr->name }} <span class="text-gray-400 text-xs">{{ $attr->code }}</span></span>
                                <span class="text-xs text-gray-400">{{ $attr->type }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-4">
        {{-- Status & Actions --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Status & Publishing</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select wire:model="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2">
                <button wire:click="save" class="w-full bg-blue-700 text-white py-2 rounded-md text-sm font-medium hover:bg-blue-800">
                    Save Draft
                </button>
                @if($product && !$isNew)
                    <button wire:click="publish" class="w-full bg-green-600 text-white py-2 rounded-md text-sm font-medium hover:bg-green-700">
                        Publish Product
                    </button>
                    <a href="{{ route('admin.rules.create', $product) }}" class="block w-full text-center border border-gray-300 text-gray-700 py-2 rounded-md text-sm font-medium hover:bg-gray-50">
                        Manage Rules
                    </a>
                    @if($product->status === 'published')
                        <a href="{{ route('configurator.configure', $product->slug) }}" target="_blank"
                           class="block w-full text-center border border-green-300 text-green-700 py-2 rounded-md text-sm font-medium hover:bg-green-50">
                            Preview Configurator ↗
                        </a>
                    @endif
                @endif
            </div>
        </div>

        {{-- Summary --}}
        @if($product && !$isNew)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Summary</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Attributes</dt>
                        <dd class="font-medium">{{ count($assignedAttributes) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Rules</dt>
                        <dd class="font-medium">{{ $product->configurationRules()->count() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">BOM Rules</dt>
                        <dd class="font-medium">{{ $product->bomRules()->count() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Version</dt>
                        <dd class="font-medium">{{ $product->version }}</dd>
                    </div>
                </dl>
            </div>
        @endif
    </div>
</div>

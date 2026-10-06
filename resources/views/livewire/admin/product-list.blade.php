<div>
    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="flex flex-wrap gap-3">
                <div class="relative flex-1 min-w-48">
                    <x-ui.icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, code, SKU..."
                           class="form-input" style="padding-left:2.25rem">
                </div>

                {{-- Status dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        class="flex items-center gap-2 pl-3 pr-2.5 py-1.5 rounded-lg border bg-white text-sm transition-colors hover:bg-slate-50"
                        style="border-color:#e2e8f0; min-width:160px">
                        <span class="flex-1 text-left px-1 text-slate-600">
                            {{ $statusFilter ? ucfirst(str_replace('_', ' ', $statusFilter)) : 'All Statuses' }}
                        </span>
                        <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mr-1"/>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                        class="absolute left-0 mt-1.5 w-48 bg-white rounded-xl border shadow-md py-1 z-50"
                        style="border-color:#e2e8f0">
                        @foreach(['' => 'All Statuses', 'draft' => 'Draft', 'in_review' => 'In Review', 'approved' => 'Approved', 'published' => 'Published', 'archived' => 'Archived'] as $val => $label)
                            <button type="button"
                                wire:click="$set('statusFilter', '{{ $val }}')"
                                @click="open = false"
                                class="w-full text-left px-3 py-2 text-sm {{ $statusFilter === $val ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Category dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        class="flex items-center gap-2 pl-3 pr-2.5 py-1.5 rounded-lg border bg-white text-sm transition-colors hover:bg-slate-50"
                        style="border-color:#e2e8f0; min-width:180px">
                        <span class="flex-1 text-left px-1 {{ $categoryFilter ? 'text-slate-800' : 'text-slate-400' }}">
                            {{ $categoryFilter ? $categories->firstWhere('id', $categoryFilter)?->name : 'All Categories' }}
                        </span>
                        <x-ui.icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 mr-1"/>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                        class="absolute left-0 mt-1.5 w-56 bg-white rounded-xl border shadow-md py-1 z-50"
                        style="border-color:#e2e8f0">
                        <button type="button"
                            wire:click="$set('categoryFilter', '')"
                            @click="open = false"
                            class="w-full text-left px-3 py-2 text-sm {{ !$categoryFilter ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                            All Categories
                        </button>
                        @foreach($categories as $cat)
                            <button type="button"
                                wire:click="$set('categoryFilter', '{{ $cat->id }}')"
                                @click="open = false"
                                class="w-full text-left px-3 py-2 text-sm {{ $categoryFilter == $cat->id ? 'font-semibold text-blue-600' : 'text-slate-600' }} hover:bg-slate-50">
                                {{ $cat->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Code</th>
                    <th>Family</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="font-medium text-slate-900">{{ $product->name }}</div>
                            <div class="text-xs text-slate-400">{{ $product->sku }}</div>
                        </td>
                        <td class="text-data text-slate-600">{{ $product->code }}</td>
                        <td class="text-slate-600">
                            {{ $product->family?->name }}
                            <span class="text-slate-400">/ {{ $product->family?->category?->name }}</span>
                        </td>
                        <td class="font-medium text-slate-900">
                            {{ $product->currency }} {{ number_format($product->base_price, 0) }}
                        </td>
                        <td>
                            @php $colors = ['draft'=>'gray','in_review'=>'yellow','approved'=>'blue','published'=>'green','archived'=>'red']; @endphp
                            <x-ui.badge color="{{ $colors[$product->status] ?? 'gray' }}">
                                {{ ucfirst(str_replace('_', ' ', $product->status)) }}
                            </x-ui.badge>
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-ghost btn-sm">
                                    <x-ui.icon name="edit" class="w-3.5 h-3.5"/>
                                    Edit
                                </a>
                                @if($product->status === 'published')
                                    <a href="{{ route('configurator.configure', $product->slug) }}" target="_blank" class="btn btn-ghost btn-sm">
                                        <x-ui.icon name="eye" class="w-3.5 h-3.5"/>
                                        Preview
                                    </a>
                                @endif
                                <button type="button"
                                    @click="$dispatch('open-modal', 'del-product-{{ $product->id }}')"
                                    class="btn btn-ghost btn-sm text-red-500 hover:text-red-600">
                                    <x-ui.icon name="trash" class="w-3.5 h-3.5"/>
                                    Delete
                                </button>
                            </div>
                            <x-ui.confirm-modal
                                id="del-product-{{ $product->id }}"
                                title="Delete Product"
                                name="{{ $product->name }}"
                                action="{{ route('admin.products.destroy', $product) }}"
                            />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-ui.empty-state icon="cube" title="No products found" description="Try adjusting your filters."/>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t" style="border-color:#e2e8f0">
            {{ $products->links() }}
        </div>
    </div>
</div>

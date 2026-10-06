@extends('layouts.app')
@section('title', 'Product Catalog')
@section('content')

{{-- Hero --}}
<div style="background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 100%)" class="py-12 px-6">
    <div class="max-w-screen-xl mx-auto">
        <div class="max-w-2xl">
            <h1 class="text-display text-white mb-3">Industrial Product Catalog</h1>
            <p class="text-slate-300 text-lg mb-6">Configure and specify industrial automation products with precision.</p>
            <div class="relative max-w-md">
                <x-ui.icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
                <input type="text" placeholder="Search products, codes, categories..."
                       class="form-input pl-9 bg-white/10 border-white/20 text-white placeholder-slate-400 focus:bg-white focus:text-slate-900 focus:placeholder-slate-400">
            </div>
        </div>
    </div>
</div>

<div class="max-w-screen-xl mx-auto px-6 py-10">

    @forelse($categories as $category)
        <div class="mb-12">
            {{-- Category Header --}}
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background:#eff6ff">
                        <x-ui.icon name="category" class="w-5 h-5 text-blue-600"/>
                    </div>
                    <div>
                        <h2 class="text-h3 text-slate-900">{{ $category->name }}</h2>
                        <p class="text-xs text-slate-400">{{ $category->families->sum(fn($f) => $f->products->count()) }} products</p>
                    </div>
                </div>
                <a href="{{ route('catalog.category', $category->slug) }}" class="btn btn-ghost btn-sm text-blue-600">
                    View all
                    <x-ui.icon name="arrow-right" class="w-3.5 h-3.5"/>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($category->families as $family)
                    @foreach($family->products->take(4) as $product)
                        <a href="{{ route('catalog.product', $product->slug) }}"
                           class="card p-5 hover:shadow-elevated hover:border-blue-200 transition-all group cursor-pointer"
                           style="text-decoration:none">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:#f8fafc">
                                <x-ui.icon name="products" class="w-5 h-5 text-slate-400 group-hover:text-blue-600 transition-colors"/>
                            </div>
                            <div class="text-xs font-medium text-blue-600 mb-1">{{ $family->name }}</div>
                            <h3 class="font-semibold text-slate-900 group-hover:text-blue-700 mb-1 transition-colors">{{ $product->name }}</h3>
                            <div class="text-xs text-slate-400 font-mono mb-3">{{ $product->code }}</div>
                            @if($product->short_description)
                                <p class="text-xs text-slate-500 line-clamp-2 mb-4">{{ $product->short_description }}</p>
                            @endif
                            <div class="flex items-center justify-between pt-3 border-t mt-auto" style="border-color:#f1f5f9">
                                <span class="text-sm font-bold text-slate-900">
                                    {{ $product->currency }} {{ number_format($product->base_price, 0) }}
                                </span>
                                <span class="text-xs font-medium text-blue-600 group-hover:underline">Configure →</span>
                            </div>
                        </a>
                    @endforeach
                @endforeach
            </div>
        </div>
    @empty
        <x-ui.empty-state icon="products" title="No products available"
            description="The product catalog is empty. Check back later or contact your administrator."
            @auth @if(auth()->user()->hasRole(['super_admin','product_manager']))
                actionLabel="Create First Product" actionHref="{{ route('admin.products.create') }}"
            @endif @endauth/>
    @endforelse
</div>
@endsection

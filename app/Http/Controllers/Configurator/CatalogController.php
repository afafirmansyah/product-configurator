<?php

namespace App\Http\Controllers\Configurator;

use App\Http\Controllers\Controller;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductFamily;

class CatalogController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::with(['families.products' => fn ($q) => $q->published()])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('configurator.catalog.index', compact('categories'));
    }

    public function category(ProductCategory $category)
    {
        $category->load(['families.products' => fn ($q) => $q->published()]);

        return view('configurator.catalog.category', compact('category'));
    }

    public function family(ProductFamily $family)
    {
        $family->load(['category', 'products' => fn ($q) => $q->published()]);

        return view('configurator.catalog.family', compact('family'));
    }

    public function product(Product $product)
    {
        abort_unless($product->isPublished(), 404);
        $product->load(['family.category', 'productAttributes.attribute.options']);

        return view('configurator.catalog.product', compact('product'));
    }

    public function configure(Product $product)
    {
        abort_unless($product->isPublished(), 404);

        return view('configurator.configure', compact('product'));
    }

    public function show(string $ulid)
    {
        $session = ConfigurationSession::where('ulid', $ulid)
            ->with(['product', 'items.attribute', 'bomItems.component'])
            ->firstOrFail();

        return view('configurator.configuration-summary', compact('session'));
    }

    public function myConfigurations()
    {
        $configurations = ConfigurationSession::with(['product'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('configurator.my-configurations', compact('configurations'));
    }
}

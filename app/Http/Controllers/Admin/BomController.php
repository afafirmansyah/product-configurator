<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BomRuleRequest;
use App\Models\BomRule;
use App\Models\Component;
use App\Models\Product;
use App\Services\Bom\BomRuleWriter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BomController extends Controller
{
    public function __construct(
        private readonly BomRuleWriter $writer,
    ) {}

    public function index(Request $request): View
    {
        $rules = BomRule::with(['product', 'conditions', 'items.component', 'items.childProduct', 'items.valueSets'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->latest()
            ->paginate(20);

        $products = Product::orderBy('name')->get();

        return view('admin.bom.index', compact('rules', 'products'));
    }

    public function create(Product $product): View
    {
        $components = Component::where('status', 'active')->orderBy('name')->get();
        $attributes = $product->attributes()->with('options')->get();
        $childProducts = $this->childProductsFor($product);

        return view('admin.bom.create', compact('product', 'components', 'attributes', 'childProducts'));
    }

    public function store(BomRuleRequest $request, Product $product): RedirectResponse
    {
        $this->writer->create($product, $request->validated());

        return redirect()->route('admin.bom.index')->with('success', 'BOM rule created.');
    }

    public function edit(BomRule $rule): View
    {
        $rule->load(['product', 'conditions.attribute', 'items.component', 'items.childProduct', 'items.valueSets.options']);

        // Keep components already used by the rule selectable even if they were deactivated.
        $components = Component::where('status', 'active')
            ->orWhereIn('id', $rule->items->pluck('component_id'))
            ->orderBy('name')
            ->get();
        $attributes = $rule->product->attributes()->with('options')->get();
        $childProducts = $this->childProductsFor($rule->product);

        return view('admin.bom.edit', compact('rule', 'components', 'attributes', 'childProducts'));
    }

    public function update(BomRuleRequest $request, BomRule $rule): RedirectResponse
    {
        $this->writer->update($rule, $request->validated());

        return redirect()->route('admin.bom.index')->with('success', 'BOM rule updated.');
    }

    public function destroy(BomRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('success', 'BOM rule deleted.');
    }

    /**
     * Configurable products selectable as sub-assemblies of the given product.
     *
     * @return Collection<int, Product>
     */
    private function childProductsFor(Product $product): Collection
    {
        return Product::where('is_configurable', true)
            ->whereKeyNot($product->id)
            ->orderBy('name')
            ->get();
    }
}

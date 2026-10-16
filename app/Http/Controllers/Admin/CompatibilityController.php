<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompatibilityRuleRequest;
use App\Models\CompatibilityRule;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompatibilityController extends Controller
{
    public function index(Request $request): View
    {
        $rules = CompatibilityRule::with(['product', 'attribute', 'option', 'targetAttribute', 'targetOption'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->latest()
            ->paginate(30);

        $products = Product::orderBy('name')->get();

        return view('admin.compatibility.index', compact('rules', 'products'));
    }

    public function create(): View
    {
        $products = Product::with('attributes.options')->orderBy('name')->get();

        return view('admin.compatibility.create', compact('products'));
    }

    public function store(CompatibilityRuleRequest $request): RedirectResponse
    {
        CompatibilityRule::create($request->validated());

        return redirect()->route('admin.compatibility.index')->with('success', 'Compatibility rule created.');
    }

    public function edit(CompatibilityRule $rule): View
    {
        $products = Product::with('attributes.options')->orderBy('name')->get();

        return view('admin.compatibility.edit', compact('rule', 'products'));
    }

    public function update(CompatibilityRuleRequest $request, CompatibilityRule $rule): RedirectResponse
    {
        $rule->update($request->validated());

        return redirect()->route('admin.compatibility.index')->with('success', 'Compatibility rule updated.');
    }

    public function destroy(CompatibilityRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with('success', 'Compatibility rule deleted.');
    }
}

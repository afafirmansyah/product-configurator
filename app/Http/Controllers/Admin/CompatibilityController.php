<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompatibilityRule;
use App\Models\Product;
use Illuminate\Http\Request;

class CompatibilityController extends Controller
{
    public function index(Request $request)
    {
        $rules = CompatibilityRule::with(['product', 'attribute', 'option', 'targetAttribute', 'targetOption'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->latest()
            ->paginate(30);

        $products = Product::orderBy('name')->get();

        return view('admin.compatibility.index', compact('rules', 'products'));
    }

    public function create()
    {
        $products = Product::with('attributes.options')->orderBy('name')->get();

        return view('admin.compatibility.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'attribute_id' => 'required|exists:attributes,id',
            'option_id' => 'required|exists:attribute_options,id',
            'target_attribute_id' => 'required|exists:attributes,id',
            'target_option_id' => 'required|exists:attribute_options,id',
            'type' => 'required|in:compatible,incompatible',
            'reason' => 'nullable|string|max:500',
            'engineering_note' => 'nullable|string',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        CompatibilityRule::create($data);

        return redirect()->route('admin.compatibility.index')->with('success', 'Compatibility rule created.');
    }

    public function destroy(CompatibilityRule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Compatibility rule deleted.');
    }
}

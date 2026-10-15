<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BomRule;
use App\Models\BomRuleCondition;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $rules = BomRule::with(['product', 'conditions', 'items.component'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->latest()
            ->paginate(20);

        $products = Product::orderBy('name')->get();

        return view('admin.bom.index', compact('rules', 'products'));
    }

    public function create(Product $product)
    {
        $product->load('attributes');
        $components = Component::where('status', 'active')->orderBy('name')->get();
        $attributes = $product->attributes()->get();

        return view('admin.bom.create', compact('product', 'components', 'attributes'));
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'condition_logic' => 'required|in:AND,OR',
            'is_active' => 'boolean',
            'conditions' => 'array',
            'conditions.*.attribute_id' => 'required|exists:attributes,id',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.component_id' => 'required|exists:components,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.is_mandatory' => 'boolean',
        ]);

        $rule = BomRule::create([
            'product_id' => $product->id,
            'name' => $data['name'],
            'code' => 'BOM-'.strtoupper(Str::random(8)),
            'condition_logic' => $data['condition_logic'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => BomRule::where('product_id', $product->id)->count(),
        ]);

        foreach ($request->input('conditions', []) as $cond) {
            if (! empty($cond['attribute_id'])) {
                BomRuleCondition::create([
                    'bom_rule_id' => $rule->id,
                    'attribute_id' => $cond['attribute_id'],
                    'operator' => $cond['operator'],
                    'value' => $cond['value'] ?? null,
                    'logic' => 'AND',
                    'group' => 0,
                ]);
            }
        }

        foreach ($request->input('items', []) as $item) {
            BomRuleItem::create([
                'bom_rule_id' => $rule->id,
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'is_mandatory' => isset($item['is_mandatory']),
            ]);
        }

        return redirect()->route('admin.bom.index')->with('success', 'BOM rule created.');
    }

    public function edit(BomRule $rule)
    {
        $rule->load(['product.attributes', 'conditions.attribute', 'items.component']);
        $components = Component::where('status', 'active')->orderBy('name')->get();
        $attributes = $rule->product->attributes()->get();

        return view('admin.bom.edit', compact('rule', 'components', 'attributes'));
    }

    public function destroy(BomRule $rule)
    {
        $rule->delete();

        return back()->with('success', 'BOM rule deleted.');
    }
}

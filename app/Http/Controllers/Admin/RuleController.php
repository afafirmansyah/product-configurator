<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigurationRule;
use App\Models\Product;
use App\Models\RuleAction;
use App\Models\RuleCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = ConfigurationRule::with(['product', 'conditions', 'actions'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->orderBy('product_id')->orderBy('priority')
            ->paginate(20);

        $products = Product::orderBy('name')->get();

        return view('admin.rules.index', compact('rules', 'products'));
    }

    public function create(Product $product)
    {
        $attributes = $product->attributes()->get();

        return view('admin.rules.create', compact('product', 'attributes'));
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'condition_logic' => 'required|in:AND,OR',
            'priority' => 'integer|min:0',
        ]);

        $rule = ConfigurationRule::create([
            'product_id' => $product->id,
            'name' => $request->name,
            'code' => 'RULE-'.strtoupper(Str::random(8)),
            'type' => $request->type,
            'condition_logic' => $request->condition_logic,
            'priority' => $request->input('priority', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        foreach ($request->input('conditions', []) as $i => $cond) {
            if (! empty($cond['attribute_id'])) {
                RuleCondition::create([
                    'configuration_rule_id' => $rule->id,
                    'attribute_id' => $cond['attribute_id'],
                    'operator' => $cond['operator'] ?? 'equals',
                    'value' => $cond['value'] ?? null,
                    'logic' => 'AND',
                    'group' => 0,
                    'sort_order' => $i,
                ]);
            }
        }

        foreach ($request->input('actions', []) as $i => $action) {
            if (! empty($action['action_type'])) {
                RuleAction::create([
                    'configuration_rule_id' => $rule->id,
                    'action_type' => $action['action_type'],
                    'target_type' => $action['target_type'] ?? 'attribute',
                    'target_id' => $action['target_id'] ?? null,
                    'value' => $action['value'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        return redirect()->route('admin.rules.index')->with('success', 'Rule created successfully.');
    }

    public function edit(ConfigurationRule $rule)
    {
        $rule->load(['product', 'conditions.attribute', 'actions']);
        $attributes = $rule->product->attributes()->get();

        return view('admin.rules.edit', compact('rule', 'attributes'));
    }

    public function update(Request $request, ConfigurationRule $rule)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'condition_logic' => 'required|in:AND,OR',
        ]);

        $rule->update([
            'name' => $request->name,
            'type' => $request->type,
            'condition_logic' => $request->condition_logic,
            'priority' => $request->input('priority', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Replace conditions and actions
        $rule->conditions()->delete();
        $rule->actions()->delete();

        foreach ($request->input('conditions', []) as $i => $cond) {
            if (! empty($cond['attribute_id'])) {
                RuleCondition::create([
                    'configuration_rule_id' => $rule->id,
                    'attribute_id' => $cond['attribute_id'],
                    'operator' => $cond['operator'] ?? 'equals',
                    'value' => $cond['value'] ?? null,
                    'logic' => 'AND',
                    'group' => 0,
                    'sort_order' => $i,
                ]);
            }
        }

        foreach ($request->input('actions', []) as $i => $action) {
            if (! empty($action['action_type'])) {
                RuleAction::create([
                    'configuration_rule_id' => $rule->id,
                    'action_type' => $action['action_type'],
                    'target_type' => $action['target_type'] ?? 'attribute',
                    'target_id' => $action['target_id'] ?? null,
                    'value' => $action['value'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        return redirect()->route('admin.rules.index')->with('success', 'Rule updated.');
    }

    public function destroy(ConfigurationRule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Rule deleted.');
    }
}

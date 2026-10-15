<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ComponentCategory;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    public function index()
    {
        $components = Component::with('category')->orderBy('name')->paginate(20);

        return view('admin.components.index', compact('components'));
    }

    public function create()
    {
        $categories = ComponentCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.components.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:100|unique:components',
            'name' => 'required|string|max:255',
            'component_category_id' => 'nullable|exists:component_categories,id',
            'description' => 'nullable|string',
            'manufacturer' => 'nullable|string|max:255',
            'manufacturer_part_number' => 'nullable|string|max:100',
            'internal_part_number' => 'nullable|string|max:100',
            'sales_part_number' => 'nullable|string|max:100',
            'cost' => 'required|numeric|min:0',
            'sales_price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'status' => 'required|in:active,inactive,discontinued',
        ]);

        Component::create($data);

        return redirect()->route('admin.components.index')->with('success', 'Component created.');
    }

    public function edit(Component $component)
    {
        $categories = ComponentCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.components.edit', compact('component', 'categories'));
    }

    public function update(Request $request, Component $component)
    {
        $data = $request->validate([
            'code' => 'required|string|max:100|unique:components,code,'.$component->id,
            'name' => 'required|string|max:255',
            'component_category_id' => 'nullable|exists:component_categories,id',
            'description' => 'nullable|string',
            'manufacturer' => 'nullable|string|max:255',
            'manufacturer_part_number' => 'nullable|string|max:100',
            'internal_part_number' => 'nullable|string|max:100',
            'sales_part_number' => 'nullable|string|max:100',
            'cost' => 'required|numeric|min:0',
            'sales_price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'status' => 'required|in:active,inactive,discontinued',
        ]);

        $component->update($data);

        return redirect()->route('admin.components.index')->with('success', 'Component updated.');
    }

    public function destroy(Component $component)
    {
        $component->delete();

        return redirect()->route('admin.components.index')->with('success', 'Component deleted.');
    }
}

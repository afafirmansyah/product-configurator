<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\ProductFamily;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FamilyController extends Controller
{
    public function index()
    {
        $families = ProductFamily::with('category')->withCount('products')->orderBy('name')->paginate(20);

        return view('admin.families.index', compact('families'));
    }

    public function create()
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.families.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:product_families',
            'description' => 'nullable|string',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        ProductFamily::create($data);

        return redirect()->route('admin.families.index')->with('success', 'Family created.');
    }

    public function edit(ProductFamily $family)
    {
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.families.edit', compact('family', 'categories'));
    }

    public function update(Request $request, ProductFamily $family)
    {
        $data = $request->validate([
            'product_category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:product_families,code,'.$family->id,
            'description' => 'nullable|string',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $family->update($data);

        return redirect()->route('admin.families.index')->with('success', 'Family updated.');
    }

    public function destroy(ProductFamily $family)
    {
        $family->delete();

        return redirect()->route('admin.families.index')->with('success', 'Family deleted.');
    }
}

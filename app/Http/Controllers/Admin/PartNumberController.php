<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartNumberPattern;
use App\Models\Product;
use Illuminate\Http\Request;

class PartNumberController extends Controller
{
    public function index()
    {
        $patterns = PartNumberPattern::with('product')->latest()->paginate(20);

        return view('admin.part-numbers.index', compact('patterns'));
    }

    public function create(Product $product)
    {
        $product->load('attributes');

        return view('admin.part-numbers.create', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'segments' => 'required|array|min:1',
        ]);

        PartNumberPattern::where('product_id', $product->id)->update(['is_active' => false]);

        PartNumberPattern::create([
            'product_id' => $product->id,
            'name' => $request->name,
            'segments' => $request->segments,
            'is_active' => true,
        ]);

        return redirect()->route('admin.part-numbers.index')->with('success', 'Part number pattern saved.');
    }

    public function edit(PartNumberPattern $pattern)
    {
        $pattern->load('product.attributes');

        return view('admin.part-numbers.edit', compact('pattern'));
    }

    public function update(Request $request, PartNumberPattern $pattern)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'segments' => 'required|array|min:1',
        ]);

        $pattern->update([
            'name' => $request->name,
            'segments' => $request->segments,
        ]);

        return redirect()->route('admin.part-numbers.index')->with('success', 'Pattern updated.');
    }

    public function destroy(PartNumberPattern $pattern)
    {
        $pattern->delete();

        return back()->with('success', 'Pattern deleted.');
    }
}

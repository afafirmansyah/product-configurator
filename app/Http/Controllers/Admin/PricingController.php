<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index()
    {
        $priceLists = PriceList::withCount(['productPrices', 'optionPrices'])->orderBy('name')->get();

        return view('admin.pricing.index', compact('priceLists'));
    }

    public function createList()
    {
        return view('admin.pricing.create-list');
    }

    public function storeList(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:price_lists',
            'currency' => 'required|string|size:3',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after:valid_from',
        ]);

        if ($request->boolean('is_default')) {
            PriceList::where('is_default', true)->update(['is_default' => false]);
        }

        PriceList::create($data);

        return redirect()->route('admin.pricing.index')->with('success', 'Price list created.');
    }

    public function editList(PriceList $priceList)
    {
        $priceList->load(['productPrices.product', 'optionPrices.option.attribute']);
        $products = Product::orderBy('name')->get();

        return view('admin.pricing.edit-list', compact('priceList', 'products'));
    }

    public function updateList(Request $request, PriceList $priceList)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:price_lists,code,'.$priceList->id,
            'currency' => 'required|string|size:3',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date',
        ]);

        if ($request->boolean('is_default')) {
            PriceList::where('id', '!=', $priceList->id)->update(['is_default' => false]);
        }

        $priceList->update($data);

        // Update product prices if submitted
        foreach ($request->input('product_prices', []) as $productId => $price) {
            ProductPrice::updateOrCreate(
                ['product_id' => $productId, 'price_list_id' => $priceList->id],
                ['base_price' => $price]
            );
        }

        return redirect()->route('admin.pricing.index')->with('success', 'Price list updated.');
    }
}

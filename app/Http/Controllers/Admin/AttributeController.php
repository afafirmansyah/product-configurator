<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttributeRequest;
use App\Models\Attribute;
use App\Models\AttributeOption;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = Attribute::withCount('options')->orderBy('name')->paginate(20);

        return view('admin.attributes.index', compact('attributes'));
    }

    public function create()
    {
        return view('admin.attributes.create', ['types' => AttributeType::cases()]);
    }

    public function store(AttributeRequest $request)
    {
        $attribute = Attribute::create($request->attributeData());

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Attribute created.');
    }

    public function edit(Attribute $attribute)
    {
        $attribute->load('options');

        return view('admin.attributes.edit', [
            'attribute' => $attribute,
            'types' => AttributeType::cases(),
        ]);
    }

    public function update(AttributeRequest $request, Attribute $attribute)
    {
        $attribute->update($request->attributeData());

        // Handle options if submitted
        if ($request->has('options')) {
            foreach ($request->input('options', []) as $optionData) {
                if (! empty($optionData['name'])) {
                    AttributeOption::updateOrCreate(
                        ['attribute_id' => $attribute->id, 'code' => $optionData['code']],
                        array_merge($optionData, ['attribute_id' => $attribute->id])
                    );
                }
            }
        }

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Attribute updated.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted.');
    }
}

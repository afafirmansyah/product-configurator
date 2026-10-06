<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:attributes',
            'type' => 'required|in:' . implode(',', array_column(AttributeType::cases(), 'value')),
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_global' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $attribute = Attribute::create($data);

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

    public function update(Request $request, Attribute $attribute)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:attributes,code,' . $attribute->id,
            'type' => 'required|in:' . implode(',', array_column(AttributeType::cases(), 'value')),
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_global' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $attribute->update($data);

        // Handle options if submitted
        if ($request->has('options')) {
            foreach ($request->input('options', []) as $optionData) {
                if (!empty($optionData['name'])) {
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

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BomRuleRequest extends FormRequest
{
    /**
     * Access is enforced by the admin route group's role middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
        ];
    }
}

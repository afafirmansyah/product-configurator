<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompatibilityRuleRequest extends FormRequest
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
        ];
    }
}

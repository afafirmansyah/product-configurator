<?php

namespace App\Http\Requests\Admin;

use App\Enums\AttributeType;
use App\Models\Attribute;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttributeRequest extends FormRequest
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
        /** @var Attribute|null $attribute */
        $attribute = $this->route('attribute');

        return [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:100', Rule::unique('attributes', 'code')->ignore($attribute?->id)],
            'type' => 'required|in:'.implode(',', array_column(AttributeType::cases(), 'value')),
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_global' => 'boolean',
            'is_active' => 'boolean',
            'validation_rules' => 'nullable|array',
            'validation_rules.type' => 'nullable|in:numeric,integer',
            'validation_rules.min' => 'nullable|numeric',
            'validation_rules.max' => [
                'nullable',
                'numeric',
                Rule::when($this->filled('validation_rules.min'), 'gte:validation_rules.min'),
            ],
            'validation_rules.required' => 'boolean',
        ];
    }

    /**
     * Validated attribute data with validation_rules normalised to the stored shape
     * documented on {@see Attribute}: blank inputs are dropped, bounds become numbers,
     * and an empty rule set is stored as null. When the request carries no
     * validation_rules at all (e.g. the "Add option" form) the stored rules are kept.
     *
     * @return array<string, mixed>
     */
    public function attributeData(): array
    {
        $data = $this->validated();

        if (! array_key_exists('validation_rules', $data)) {
            return $data;
        }

        $input = $data['validation_rules'] ?? [];

        $rules = array_filter([
            'type' => $input['type'] ?? null,
            'min' => isset($input['min']) ? $input['min'] + 0 : null,
            'max' => isset($input['max']) ? $input['max'] + 0 : null,
            'required' => filter_var($input['required'] ?? false, FILTER_VALIDATE_BOOLEAN) ?: null,
        ], fn (mixed $value): bool => $value !== null);

        $data['validation_rules'] = $rules === [] ? null : $rules;

        return $data;
    }
}

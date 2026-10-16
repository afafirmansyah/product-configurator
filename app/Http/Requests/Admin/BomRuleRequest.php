<?php

namespace App\Http\Requests\Admin;

use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'items.*.value_sets' => 'nullable|array',
            'items.*.value_sets.*.name' => 'nullable|string|max:255',
            'items.*.value_sets.*.option_ids' => 'required|array|min:1',
            'items.*.value_sets.*.option_ids.*' => 'integer|exists:attribute_options,id',
        ];
    }

    /**
     * Reject value-set options whose attribute is not attached to the rule's product.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $product = $this->ruleProduct();
                $items = $this->input('items');

                if ($product === null || ! is_array($items)) {
                    return;
                }

                $optionIds = collect($items)
                    ->flatMap(fn (mixed $item): array => is_array($item) && is_array($item['value_sets'] ?? null) ? $item['value_sets'] : [])
                    ->flatMap(fn (mixed $valueSet): array => is_array($valueSet) && is_array($valueSet['option_ids'] ?? null) ? $valueSet['option_ids'] : [])
                    ->filter(fn (mixed $optionId): bool => is_numeric($optionId))
                    ->map(fn (mixed $optionId): int => (int) $optionId)
                    ->unique()
                    ->values();

                if ($optionIds->isEmpty()) {
                    return;
                }

                $productAttributeIds = $product->attributes()->pluck('attributes.id')->all();
                $foreignOptionIds = AttributeOption::whereIn('id', $optionIds)
                    ->whereNotIn('attribute_id', $productAttributeIds)
                    ->pluck('id')
                    ->map(fn (mixed $optionId): int => (int) $optionId)
                    ->all();

                if ($foreignOptionIds === []) {
                    return;
                }

                foreach ($items as $itemIndex => $item) {
                    foreach ((is_array($item) ? $item['value_sets'] ?? [] : []) as $setIndex => $valueSet) {
                        $setOptionIds = is_array($valueSet) && is_array($valueSet['option_ids'] ?? null) ? $valueSet['option_ids'] : [];
                        $hasForeignOption = collect($setOptionIds)
                            ->contains(fn (mixed $optionId): bool => is_numeric($optionId) && in_array((int) $optionId, $foreignOptionIds, true));

                        if ($hasForeignOption) {
                            $validator->errors()->add(
                                "items.{$itemIndex}.value_sets.{$setIndex}.option_ids",
                                'Value sets may only use options of attributes assigned to this product.',
                            );
                        }
                    }
                }
            },
        ];
    }

    /**
     * Resolve the product the rule belongs to from the store or update route.
     */
    private function ruleProduct(): ?Product
    {
        $product = $this->route('product');

        if ($product instanceof Product) {
            return $product;
        }

        $rule = $this->route('rule');

        return $rule instanceof BomRule ? $rule->product : null;
    }
}

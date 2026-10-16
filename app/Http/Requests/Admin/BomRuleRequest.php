<?php

namespace App\Http\Requests\Admin;

use App\Enums\QuantityMode;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\Product;
use App\Services\Bom\QuantityFormula;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'items.*.quantity_mode' => ['nullable', Rule::enum(QuantityMode::class)],
            'items.*.quantity_attribute_id' => 'nullable|required_if:items.*.quantity_mode,attribute|exists:attributes,id',
            'items.*.quantity_formula' => 'nullable|required_if:items.*.quantity_mode,formula|string|max:255',
            'items.*.quantity_round_up' => 'boolean',
            'items.*.value_sets' => 'nullable|array',
            'items.*.value_sets.*.name' => 'nullable|string|max:255',
            'items.*.value_sets.*.option_ids' => 'required|array|min:1',
            'items.*.value_sets.*.option_ids.*' => 'integer|exists:attribute_options,id',
        ];
    }

    /**
     * Reject value-set options whose attribute is not attached to the rule's product,
     * quantity attributes not attached to it, and quantity formulas with invalid
     * syntax or codes of attributes that are not on the product.
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
            $this->validateComputedQuantities(...),
        ];
    }

    /**
     * Check quantity attributes and formulas of computed-quantity items against the product.
     */
    private function validateComputedQuantities(Validator $validator): void
    {
        $product = $this->ruleProduct();
        $items = $this->input('items');

        if ($product === null || ! is_array($items)) {
            return;
        }

        $computedItems = collect($items)->filter(fn (mixed $item): bool => is_array($item)
            && in_array($item['quantity_mode'] ?? null, [QuantityMode::Attribute->value, QuantityMode::Formula->value], true));

        if ($computedItems->isEmpty()) {
            return;
        }

        /** @var array<int, string> $productAttributeCodes */
        $productAttributeCodes = $product->attributes()->pluck('attributes.code', 'attributes.id')->all();

        foreach ($computedItems as $itemIndex => $item) {
            if ($item['quantity_mode'] === QuantityMode::Attribute->value) {
                $attributeId = $item['quantity_attribute_id'] ?? null;

                if (is_numeric($attributeId) && ! array_key_exists((int) $attributeId, $productAttributeCodes)) {
                    $validator->errors()->add(
                        "items.{$itemIndex}.quantity_attribute_id",
                        'The quantity attribute must be assigned to this product.',
                    );
                }

                continue;
            }

            $formula = $item['quantity_formula'] ?? null;

            if (! is_string($formula) || trim($formula) === '') {
                continue;
            }

            foreach (app(QuantityFormula::class)->validate($formula, array_values($productAttributeCodes)) as $message) {
                $validator->errors()->add("items.{$itemIndex}.quantity_formula", $message);
            }
        }
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

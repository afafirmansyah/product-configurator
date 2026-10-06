<?php

namespace App\Services\Pricing;

use App\Models\AttributeOption;
use App\Models\PriceList;
use App\Models\Product;

class PricingService
{
    public function calculate(Product $product, array $selections, ?PriceList $priceList = null): array
    {
        $priceList ??= PriceList::where('is_default', true)->where('is_active', true)->first();

        $basePrice = $priceList
            ? ($product->productPrices()->where('price_list_id', $priceList->id)->first()?->base_price ?? $product->base_price)
            : $product->base_price;

        $optionAdjustments = [];
        $optionsTotal = 0;

        foreach ($selections as $attributeId => $value) {
            if (empty($value)) {
                continue;
            }

            $optionIds = is_array($value) ? $value : [$value];

            foreach ($optionIds as $optionId) {
                if (!is_numeric($optionId)) {
                    continue;
                }

                $option = AttributeOption::find($optionId);
                if (!$option) {
                    continue;
                }

                $adjustment = $priceList
                    ? ($option->optionPrices()->where('price_list_id', $priceList->id)->first()?->price_adjustment ?? $option->price_adjustment)
                    : $option->price_adjustment;

                if ($adjustment != 0) {
                    $optionAdjustments[] = [
                        'option_name' => $option->name,
                        'adjustment' => $adjustment,
                    ];
                    $optionsTotal += $adjustment;
                }
            }
        }

        $subtotal = $basePrice + $optionsTotal;
        $currency = $priceList?->currency ?? $product->currency;

        return [
            'base_price' => $basePrice,
            'option_adjustments' => $optionAdjustments,
            'options_total' => $optionsTotal,
            'subtotal' => $subtotal,
            'discount' => 0,
            'total' => $subtotal,
            'currency' => $currency,
        ];
    }
}

<?php

namespace App\Services\Pricing;

use App\Models\AttributeOption;
use App\Models\PriceList;
use App\Models\Product;
use App\Support\Money;

class PricingService
{
    public function calculate(Product $product, array $selections, ?PriceList $priceList = null): array
    {
        $priceList ??= PriceList::where('is_default', true)->where('is_active', true)->first();

        $basePrice = $priceList
            ? ($product->productPrices()->where('price_list_id', $priceList->id)->first()?->base_price ?? $product->base_price)
            : $product->base_price;

        $baseCents = Money::toCents($basePrice);

        $optionAdjustments = [];
        $optionsCents = 0;

        foreach ($selections as $attributeId => $value) {
            if (empty($value)) {
                continue;
            }

            $optionIds = is_array($value) ? $value : [$value];

            foreach ($optionIds as $optionId) {
                if (! is_numeric($optionId)) {
                    continue;
                }

                $option = AttributeOption::find($optionId);
                if (! $option) {
                    continue;
                }

                $adjustment = $priceList
                    ? ($option->optionPrices()->where('price_list_id', $priceList->id)->first()?->price_adjustment ?? $option->price_adjustment)
                    : $option->price_adjustment;

                $adjustmentCents = Money::toCents($adjustment);

                if ($adjustmentCents !== 0) {
                    $optionAdjustments[] = [
                        'option_name' => $option->name,
                        'adjustment' => Money::fromCents($adjustmentCents),
                    ];
                    $optionsCents = Money::addCents($optionsCents, $adjustmentCents);
                }
            }
        }

        $subtotalCents = Money::addCents($baseCents, $optionsCents);
        $currency = $priceList?->currency ?? $product->currency;

        return [
            'base_price' => Money::fromCents($baseCents),
            'option_adjustments' => $optionAdjustments,
            'options_total' => Money::fromCents($optionsCents),
            'subtotal' => Money::fromCents($subtotalCents),
            'discount' => 0,
            'total' => Money::fromCents($subtotalCents),
            'currency' => $currency,
        ];
    }
}

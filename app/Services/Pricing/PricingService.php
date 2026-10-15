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

        // Collect every numeric option id across all selections first, then load
        // them (with their option prices) in a SINGLE query to avoid a per-option
        // find()/first() N+1. The iteration order below drives the result, so keep
        // the flat, de-duplicated id list in selection order.
        $orderedOptionIds = $this->collectOptionIds($selections);
        $options = AttributeOption::query()
            ->with('optionPrices')
            ->whereIn('id', $orderedOptionIds)
            ->get()
            ->keyBy('id');

        $optionAdjustments = [];
        $optionsCents = 0;

        foreach ($orderedOptionIds as $optionId) {
            $option = $options->get($optionId);
            if (! $option) {
                continue;
            }

            $adjustment = $priceList
                ? ($option->optionPrices->firstWhere('price_list_id', $priceList->id)?->price_adjustment ?? $option->price_adjustment)
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

    /**
     * Flatten the selections into a de-duplicated list of numeric option ids,
     * preserving selection order so the price breakdown stays deterministic.
     *
     * @param  array<int|string, mixed>  $selections
     * @return list<int>
     */
    private function collectOptionIds(array $selections): array
    {
        $ids = [];

        foreach ($selections as $value) {
            if (empty($value)) {
                continue;
            }

            $optionIds = is_array($value) ? $value : [$value];

            foreach ($optionIds as $optionId) {
                if (! is_numeric($optionId)) {
                    continue;
                }

                $id = (int) $optionId;

                if (! in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }
}

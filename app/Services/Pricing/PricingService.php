<?php

namespace App\Services\Pricing;

use App\Models\AttributeOption;
use App\Models\PriceList;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;

class PricingService
{
    /**
     * Cache key for the resolved default price-list id. Invalidated whenever any
     * PriceList row is written or deleted (see PriceList::booted()).
     */
    public const DEFAULT_PRICE_LIST_CACHE_KEY = 'pricing.default_price_list_id';

    public function calculate(Product $product, array $selections, ?PriceList $priceList = null): array
    {
        $priceList ??= $this->resolveDefaultPriceList();

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
     * Resolve the active default price list, caching its id so the hot
     * recalculation path (one lookup per Livewire keystroke) does not re-query
     * this near-static row every time. The cache stores only the id and the full
     * model is still loaded fresh, so no stale relation data is served. The entry
     * is flushed on any PriceList write/delete via PriceList::booted().
     */
    private function resolveDefaultPriceList(): ?PriceList
    {
        $id = Cache::rememberForever(self::DEFAULT_PRICE_LIST_CACHE_KEY, function (): ?int {
            return PriceList::where('is_default', true)
                ->where('is_active', true)
                ->value('id');
        });

        if ($id === null) {
            return null;
        }

        $priceList = PriceList::find($id);

        // The cached id can go stale if the row was deactivated without a cache
        // flush; fall back to a fresh lookup and refresh the cache in that case.
        if ($priceList === null || ! $priceList->is_default || ! $priceList->is_active) {
            Cache::forget(self::DEFAULT_PRICE_LIST_CACHE_KEY);

            return PriceList::where('is_default', true)->where('is_active', true)->first();
        }

        return $priceList;
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

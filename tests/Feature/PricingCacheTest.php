<?php

use App\Models\PriceList;
use App\Models\Product;
use App\Services\Pricing\PricingService;
use Illuminate\Support\Facades\Cache;

test('the default price list id is cached after the first resolution', function () {
    $default = PriceList::factory()->default()->create();

    expect(Cache::has(PricingService::DEFAULT_PRICE_LIST_CACHE_KEY))->toBeFalse();

    $service = app(PricingService::class);
    $service->calculate(Product::factory()->create(), []);

    expect(Cache::get(PricingService::DEFAULT_PRICE_LIST_CACHE_KEY))->toBe($default->id);
});

test('saving a price list flushes the cached default id', function () {
    $default = PriceList::factory()->default()->create();
    Cache::forever(PricingService::DEFAULT_PRICE_LIST_CACHE_KEY, $default->id);

    PriceList::factory()->create();

    expect(Cache::has(PricingService::DEFAULT_PRICE_LIST_CACHE_KEY))->toBeFalse();
});

test('a stale cached id falls back to the current default when deactivated', function () {
    $old = PriceList::factory()->default()->create();
    Cache::forever(PricingService::DEFAULT_PRICE_LIST_CACHE_KEY, $old->id);

    // Deactivate directly in the database to simulate a stale cache entry
    // (the booted() hook would normally flush, so bypass it via a raw update).
    PriceList::withoutEvents(function () use ($old) {
        $old->forceFill(['is_active' => false])->save();
    });
    $current = PriceList::factory()->default()->create();
    Cache::forever(PricingService::DEFAULT_PRICE_LIST_CACHE_KEY, $old->id);

    $result = app(PricingService::class)->calculate(Product::factory()->create(), []);

    expect($result['currency'])->toBe($current->currency);
});

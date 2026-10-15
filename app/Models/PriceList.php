<?php

namespace App\Models;

use App\Services\Pricing\PricingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class PriceList extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'code', 'currency', 'is_default', 'is_active', 'valid_from', 'valid_to'];

    /**
     * Flush the cached default-price-list id whenever any price list is written
     * or removed, so PricingService re-resolves the current default.
     */
    protected static function booted(): void
    {
        $forget = static function (): void {
            Cache::forget(PricingService::DEFAULT_PRICE_LIST_CACHE_KEY);
        };

        static::saved($forget);
        static::deleted($forget);
    }

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function optionPrices()
    {
        return $this->hasMany(OptionPrice::class);
    }
}

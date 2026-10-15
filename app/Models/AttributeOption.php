<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeOption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attribute_id',
        'code',
        'name',
        'description',
        'price_adjustment',
        'cost',
        'sku',
        'part_number',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function optionPrices(): HasMany
    {
        return $this->hasMany(OptionPrice::class, 'attribute_option_id');
    }
}

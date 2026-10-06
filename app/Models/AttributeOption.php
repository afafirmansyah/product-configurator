<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attribute_id', 'code', 'name', 'description', 'price_adjustment',
        'cost', 'sku', 'part_number', 'image', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function optionPrices()
    {
        return $this->hasMany(OptionPrice::class, 'attribute_option_id');
    }
}

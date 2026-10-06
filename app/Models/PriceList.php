<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceList extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'currency', 'is_default', 'is_active', 'valid_from', 'valid_to'];

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

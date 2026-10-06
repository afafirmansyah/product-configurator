<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionPrice extends Model
{
    protected $fillable = ['attribute_option_id', 'price_list_id', 'price_adjustment', 'valid_from', 'valid_to'];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function option()
    {
        return $this->belongsTo(AttributeOption::class, 'attribute_option_id');
    }

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }
}

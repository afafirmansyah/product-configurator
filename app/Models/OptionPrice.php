<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionPrice extends Model
{
    use HasFactory;

    protected $fillable = ['attribute_option_id', 'price_list_id', 'price_adjustment', 'valid_from', 'valid_to'];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'attribute_option_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }
}

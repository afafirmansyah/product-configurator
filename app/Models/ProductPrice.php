<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'price_list_id', 'base_price', 'valid_from', 'valid_to'];

    protected $casts = [
        'base_price' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }
}

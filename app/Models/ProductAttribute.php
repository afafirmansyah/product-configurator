<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductAttribute extends Pivot
{
    protected $table = 'product_attributes';

    public $incrementing = true;

    protected $fillable = [
        'product_id',
        'attribute_id',
        'is_required',
        'default_value',
        'is_visible',
        'help_text',
        'sort_order',
        'step_name',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}

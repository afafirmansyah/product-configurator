<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'component_category_id', 'code', 'name', 'description', 'manufacturer',
        'manufacturer_part_number', 'internal_part_number', 'sales_part_number',
        'cost', 'sales_price', 'currency', 'weight', 'dimensions', 'technical_specs', 'status',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'technical_specs' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ComponentCategory::class, 'component_category_id');
    }
}

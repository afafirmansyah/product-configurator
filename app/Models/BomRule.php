<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BomRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'name', 'code', 'condition_logic', 'is_active',
        'effective_from', 'effective_to', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function conditions()
    {
        return $this->hasMany(BomRuleCondition::class);
    }

    public function items()
    {
        return $this->hasMany(BomRuleItem::class);
    }
}

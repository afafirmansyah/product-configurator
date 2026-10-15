<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompatibilityRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'attribute_id',
        'option_id',
        'target_attribute_id',
        'target_option_id',
        'type',
        'reason',
        'engineering_note',
        'priority',
        'is_active',
        'effective_from',
        'effective_to',
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

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function option()
    {
        return $this->belongsTo(AttributeOption::class, 'option_id');
    }

    public function targetAttribute()
    {
        return $this->belongsTo(Attribute::class, 'target_attribute_id');
    }

    public function targetOption()
    {
        return $this->belongsTo(AttributeOption::class, 'target_option_id');
    }
}

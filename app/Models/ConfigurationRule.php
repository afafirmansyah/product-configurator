<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigurationRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'code',
        'type',
        'condition_logic',
        'priority',
        'is_active',
        'effective_from',
        'effective_to',
        'version',
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
        return $this->hasMany(RuleCondition::class)->orderBy('sort_order');
    }

    public function actions()
    {
        return $this->hasMany(RuleAction::class)->orderBy('sort_order');
    }
}

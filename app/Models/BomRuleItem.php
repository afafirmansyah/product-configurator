<?php

namespace App\Models;

use App\Enums\QuantityMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BomRuleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_rule_id',
        'component_id',
        'quantity',
        'quantity_mode',
        'quantity_attribute_id',
        'quantity_formula',
        'quantity_round_up',
        'is_mandatory',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_mode' => QuantityMode::class,
        'quantity_round_up' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    public function bomRule(): BelongsTo
    {
        return $this->belongsTo(BomRule::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Attribute whose numeric selection multiplies the base quantity (attribute mode).
     */
    public function quantityAttribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'quantity_attribute_id');
    }

    /**
     * Alternative attribute-value sets gating this line; none means always included.
     */
    public function valueSets(): HasMany
    {
        return $this->hasMany(BomRuleItemValueSet::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BomRuleItem extends Model
{
    use HasFactory;

    protected $fillable = ['bom_rule_id', 'component_id', 'quantity', 'is_mandatory'];

    protected $casts = [
        'quantity' => 'decimal:2',
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
     * Alternative attribute-value sets gating this line; none means always included.
     */
    public function valueSets(): HasMany
    {
        return $this->hasMany(BomRuleItemValueSet::class);
    }
}

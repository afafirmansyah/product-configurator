<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}

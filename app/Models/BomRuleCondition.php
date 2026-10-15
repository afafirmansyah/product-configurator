<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomRuleCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_rule_id',
        'attribute_id',
        'operator',
        'value',
        'logic',
        'group',
    ];

    public function bomRule(): BelongsTo
    {
        return $this->belongsTo(BomRule::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}

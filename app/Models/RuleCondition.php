<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'configuration_rule_id',
        'attribute_id',
        'operator',
        'value',
        'logic',
        'group',
        'sort_order',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ConfigurationRule::class, 'configuration_rule_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}

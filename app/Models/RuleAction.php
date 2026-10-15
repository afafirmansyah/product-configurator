<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'configuration_rule_id',
        'action_type',
        'target_type',
        'target_id',
        'value',
        'sort_order',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ConfigurationRule::class, 'configuration_rule_id');
    }
}

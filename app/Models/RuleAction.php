<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function rule()
    {
        return $this->belongsTo(ConfigurationRule::class, 'configuration_rule_id');
    }
}

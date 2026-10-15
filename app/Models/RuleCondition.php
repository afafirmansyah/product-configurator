<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function rule()
    {
        return $this->belongsTo(ConfigurationRule::class, 'configuration_rule_id');
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}

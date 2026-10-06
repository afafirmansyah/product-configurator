<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BomRuleCondition extends Model
{
    protected $fillable = [
        'bom_rule_id', 'attribute_id', 'operator', 'value', 'logic', 'group',
    ];

    public function bomRule()
    {
        return $this->belongsTo(BomRule::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}

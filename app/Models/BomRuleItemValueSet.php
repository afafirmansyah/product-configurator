<?php

namespace App\Models;

use Database\Factories\BomRuleItemValueSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One alternative set of attribute options for a BOM rule item. The item is
 * included when every option of at least one of its sets is selected.
 */
class BomRuleItemValueSet extends Model
{
    /** @use HasFactory<BomRuleItemValueSetFactory> */
    use HasFactory;

    protected $fillable = ['bom_rule_item_id', 'name'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(BomRuleItem::class, 'bom_rule_item_id');
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(AttributeOption::class, 'bom_rule_item_value_set_options')
            ->withTimestamps();
    }
}

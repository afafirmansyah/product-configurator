<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'code', 'type', 'unit', 'description', 'validation_rules', 'is_global', 'is_active'];

    protected $casts = [
        'validation_rules' => 'array',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attributes')
            ->withPivot(['is_required', 'default_value', 'is_visible', 'help_text', 'sort_order', 'step_name']);
    }
}

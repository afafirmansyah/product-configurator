<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Value validation applied by the configurator (App\Services\Configurator\AttributeValueValidator).
 * Every key is optional; null means "no extra rules":
 * - type: 'numeric' | 'integer' (integer also rejects non-whole numbers)
 * - min / max: inclusive numeric bounds (either implies a numeric value)
 * - required: true makes the attribute required wherever it is visible
 *
 * @property array{type?: 'numeric'|'integer', min?: int|float, max?: int|float, required?: bool}|null $validation_rules
 */
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_family_id',
        'name',
        'slug',
        'code',
        'sku',
        'internal_part_number',
        'manufacturer',
        'short_description',
        'long_description',
        'applications',
        'features',
        'image',
        'base_price',
        'currency',
        'status',
        'version',
        'effective_date',
        'expiry_date',
        'is_configurable',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_configurable' => 'boolean',
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(ProductFamily::class, 'product_family_id');
    }

    public function category(): HasOneThrough
    {
        return $this->hasOneThrough(
            ProductCategory::class,
            ProductFamily::class,
            'id',
            'id',
            'product_family_id',
            'product_category_id'
        );
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
            ->withPivot(['is_required', 'default_value', 'is_visible', 'help_text', 'sort_order', 'step_name'])
            ->orderByPivot('sort_order');
    }

    public function configurationRules(): HasMany
    {
        return $this->hasMany(ConfigurationRule::class)->where('is_active', true)->orderBy('priority');
    }

    public function bomRules(): HasMany
    {
        return $this->hasMany(BomRule::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function partNumberPattern(): HasOne
    {
        return $this->hasOne(PartNumberPattern::class)->where('is_active', true);
    }

    public function compatibilityRules(): HasMany
    {
        return $this->hasMany(CompatibilityRule::class)->where('is_active', true);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}

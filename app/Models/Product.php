<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function family()
    {
        return $this->belongsTo(ProductFamily::class, 'product_family_id');
    }

    public function category()
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

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
            ->withPivot(['is_required', 'default_value', 'is_visible', 'help_text', 'sort_order', 'step_name'])
            ->orderByPivot('sort_order');
    }

    public function configurationRules()
    {
        return $this->hasMany(ConfigurationRule::class)->where('is_active', true)->orderBy('priority');
    }

    public function bomRules()
    {
        return $this->hasMany(BomRule::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function partNumberPattern()
    {
        return $this->hasOne(PartNumberPattern::class)->where('is_active', true);
    }

    public function compatibilityRules()
    {
        return $this->hasMany(CompatibilityRule::class)->where('is_active', true);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}

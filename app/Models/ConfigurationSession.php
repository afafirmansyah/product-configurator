<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigurationSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ulid',
        'configuration_number',
        'name',
        'customer',
        'project',
        'product_id',
        'user_id',
        'status',
        'bom_status',
        'bom_revision',
        'bom_generated_at',
        'bom_snapshot',
        'validation_errors',
        'version',
        'total_price',
        'currency',
        'configuration_code',
        'snapshot',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'snapshot' => 'array',
        'bom_snapshot' => 'array',
        'validation_errors' => 'array',
        'bom_generated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConfigurationItem::class);
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(ConfigurationBomItem::class);
    }

    public function quotationRequest(): HasOne
    {
        return $this->hasOne(QuotationRequest::class);
    }

    public function getSelectionsArray(): array
    {
        return $this->items->pluck('value', 'attribute_id')->toArray();
    }
}

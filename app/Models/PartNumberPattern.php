<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartNumberPattern extends Model
{
    protected $fillable = ['product_id', 'name', 'segments', 'is_active'];

    protected $casts = [
        'segments' => 'array',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

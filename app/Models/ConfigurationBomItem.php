<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigurationBomItem extends Model
{
    protected $fillable = [
        'configuration_session_id', 'component_id', 'quantity', 'unit_price', 'is_mandatory',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'is_mandatory' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(ConfigurationSession::class, 'configuration_session_id');
    }

    public function component()
    {
        return $this->belongsTo(Component::class);
    }
}

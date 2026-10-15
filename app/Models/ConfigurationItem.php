<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigurationItem extends Model
{
    protected $fillable = ['configuration_session_id', 'attribute_id', 'value'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConfigurationSession::class, 'configuration_session_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}

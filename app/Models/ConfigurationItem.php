<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigurationItem extends Model
{
    protected $fillable = ['configuration_session_id', 'attribute_id', 'value'];

    public function session()
    {
        return $this->belongsTo(ConfigurationSession::class, 'configuration_session_id');
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}

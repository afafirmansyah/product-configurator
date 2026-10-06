<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ulid', 'reference_number', 'configuration_session_id', 'user_id',
        'company', 'contact_person', 'email', 'phone', 'country',
        'project_name', 'project_location', 'quantity', 'required_delivery_date',
        'remarks', 'status',
    ];

    protected $casts = [
        'required_delivery_date' => 'date',
    ];

    public function configuration()
    {
        return $this->belongsTo(ConfigurationSession::class, 'configuration_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

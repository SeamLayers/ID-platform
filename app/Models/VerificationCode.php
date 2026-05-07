<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    protected $fillable = [
        'phone_number',
        'country_code',
        'code',
        'expiration_date',
        'is_used',
    ];

    protected $casts = [
        'expiration_date' => 'datetime',
        'is_used' => 'boolean',
    ];
}

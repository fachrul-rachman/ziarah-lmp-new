<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalkIn extends Model
{
    protected $fillable = [
        'public_token',
        'customer_name',
        'customer_phone',
        'lot_number',
        'ethics_consented_at',
    ];

    protected $casts = [
        'ethics_consented_at' => 'datetime',
    ];
}

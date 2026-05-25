<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingFacility extends Model
{
    protected $fillable = [
        'booking_id',
        'chairs_count',
        'burn_barrels_count',
        'has_tent',
        'has_prayer_table',
        'has_lamp',
    ];

    protected $casts = [
        'chairs_count' => 'integer',
        'burn_barrels_count' => 'integer',
        'has_tent' => 'boolean',
        'has_prayer_table' => 'boolean',
        'has_lamp' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}


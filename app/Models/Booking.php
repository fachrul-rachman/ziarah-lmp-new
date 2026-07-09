<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'public_token',
        'activity_type',
        'booking_code',
        'customer_name',
        'customer_email',
        'customer_phone',
        'additional_note',
        'location_id',
        'zone_id',
        'lot_id',
        'grave_type',
        'visit_date',
        'time_slot_id',
        'status',
        'cancel_reason',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function facilities(): HasOne
    {
        return $this->hasOne(BookingFacility::class);
    }
}

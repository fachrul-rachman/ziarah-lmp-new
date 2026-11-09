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
        'ethics_consented_at',
        'location_id',
        'zone_id',
        'lot_id',
        'grave_type',
        'visit_date',
        'time_slot_id',
        'status',
        'cancel_reason',
        'visitation_plan_id',
        'planned_moment',
        'whatsapp_consented_at',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'ethics_consented_at' => 'datetime',
        'whatsapp_consented_at' => 'datetime',
    ];

    public function visitationPlan(): BelongsTo
    {
        return $this->belongsTo(VisitationPlan::class);
    }

    public function activityLabel(): string
    {
        if ($this->visitation_plan_id) {
            $moment = match ($this->planned_moment) {
                '7', '40', '100' => $this->planned_moment.' Hari',
                'year' => '1 Tahun',
                default => 'Tanggal Bebas',
            };

            return 'Ziarah Terencana '.$moment;
        }

        return match ($this->activity_type) {
            'ziarah' => 'Ziarah',
            'naik_batu' => 'Naik Batu',
            'start_work' => 'Start Work',
            'wang_san' => 'Wang San',
            default => (string) $this->activity_type,
        };
    }

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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHiddenFacility extends Model
{
    protected $fillable = [
        'event_id',
        'facility_key',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'event_locations');
    }

    public function hiddenFacilities(): HasMany
    {
        return $this->hasMany(EventHiddenFacility::class);
    }
}


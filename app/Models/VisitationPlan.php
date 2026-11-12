<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitationPlan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date_of_death' => 'immutable_date',
        'burial_at' => 'immutable_datetime',
        'expires_at' => 'immutable_datetime',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class)->withTrashed();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function maximumDate(?string $moment = null): CarbonImmutable
    {
        $anniversary = $this->date_of_death->addYearNoOverflow();

        return $moment === 'year' ? $anniversary->addMonthNoOverflow() : $anniversary;
    }

    public function moments(): array
    {
        return [
            '7' => $this->date_of_death->addDays(7)->toDateString(),
            '40' => $this->date_of_death->addDays(40)->toDateString(),
            '100' => $this->date_of_death->addDays(100)->toDateString(),
            'year' => $this->maximumDate()->toDateString(),
        ];
    }
}

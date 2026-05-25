<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class BookingCodeService
{
    public function make(string $activityType, CarbonInterface $visitDate, string $normalizedLotNumber): string
    {
        $prefix = match ($activityType) {
            'ziarah' => 'Z',
            'naik_batu' => 'NB',
            'wang_san' => 'WS',
            'start_work' => 'SW',
            default => 'Z',
        };

        $datePart = $visitDate->format('Ymd');

        $lotPart = Str::of($normalizedLotNumber)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();

        return "{$prefix}-{$datePart}-{$lotPart}";
    }
}


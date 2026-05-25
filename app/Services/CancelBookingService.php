<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\CarbonImmutable;

class CancelBookingService
{
    public function cancelByCustomer(Booking $booking, string $reason): void
    {
        if ($booking->status === 'cancelled') {
            return;
        }

        if ($this->isExpired($booking)) {
            throw new \RuntimeException('Masa berlaku aksi sudah habis.');
        }

        $booking->update([
            'status' => 'cancelled',
            'cancel_reason' => $reason,
        ]);
    }

    private function isExpired(Booking $booking): bool
    {
        $today = now()->timezone('Asia/Jakarta')->startOfDay();
        $visit = CarbonImmutable::parse($booking->visit_date, 'Asia/Jakarta')->startOfDay();
        return $visit->lessThan($today);
    }
}


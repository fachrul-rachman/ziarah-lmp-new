<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CancelBookingService
{
    public function cancel(Booking $booking, ?string $reason, bool $byAdmin = false): void
    {
        DB::transaction(function () use ($booking, $reason, $byAdmin) {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            if ($current->status === 'cancelled') {
                return;
            }
            if (! $byAdmin && ($current->status === 'completed' || $this->isExpired($current))) {
                throw new \RuntimeException('Masa berlaku aksi sudah habis.');
            }
            $current->update(['status' => 'cancelled', 'cancel_reason' => $reason]);
        });
    }

    private function isExpired(Booking $booking): bool
    {
        $today = now()->timezone('Asia/Jakarta')->startOfDay();
        $visit = CarbonImmutable::parse($booking->visit_date, 'Asia/Jakarta')->startOfDay();

        return $visit->lessThan($today);
    }
}

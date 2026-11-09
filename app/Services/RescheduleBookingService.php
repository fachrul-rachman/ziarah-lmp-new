<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Lot;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RescheduleBookingService
{
    public function __construct(
        private readonly BookingCodeService $codeService,
        private readonly BookingRulesService $rules,
        private readonly BookingWhatsAppService $whatsapp,
    ) {}

    /**
     * @param array{
     *  location_id:int,
     *  zone_id:int,
     *  lot_id:int,
     *  grave_type:string,
     *  visit_date:string,
     *  time_slot_id:int,
     *  facilities:array{
     *    chairs_count:int,
     *    burn_barrels_count:int,
     *    has_tent:bool,
     *    has_prayer_table:bool,
     *    has_lamp:bool,
     *  },
     *  customer_name:string,
     *  customer_email:string,
     *  customer_phone?:string,
     *  additional_note?:?string
     * } $data
     */
    public function reschedule(Booking $booking, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $data) {
            $additionalNote = trim((string) ($data['additional_note'] ?? ''));

            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            if (! in_array($booking->status, ['confirmed', 'rescheduled'], true) || $this->isExpired($booking)) {
                throw new \RuntimeException('Booking sudah tidak dapat diubah.');
            }
            $booking->loadMissing(['facilities']);

            $data['facilities'] = $this->rules->validate($data, $booking);

            $lot = Lot::query()
                ->whereKey($data['lot_id'])
                ->firstOrFail(['id', 'normalized_lot_number', 'normalized_size', 'size']);

            $oldFacilities = [
                'chairs_count' => (int) ($booking->facilities->chairs_count ?? 0),
                'burn_barrels_count' => (int) ($booking->facilities->burn_barrels_count ?? 0),
                'has_tent' => (bool) ($booking->facilities->has_tent ?? false),
                'has_prayer_table' => (bool) ($booking->facilities->has_prayer_table ?? false),
                'has_lamp' => (bool) ($booking->facilities->has_lamp ?? false),
            ];

            DB::table('booking_reschedule_histories')->insert([
                'booking_id' => $booking->id,
                'old_visit_date' => $booking->visit_date?->format('Y-m-d'),
                'old_time_slot_id' => $booking->time_slot_id,
                'old_location_id' => $booking->location_id,
                'old_zone_id' => $booking->zone_id,
                'old_lot_id' => $booking->lot_id,
                'old_grave_type' => $booking->grave_type,
                'old_facilities_json' => json_encode($oldFacilities),
                'new_visit_date' => $data['visit_date'],
                'new_time_slot_id' => $data['time_slot_id'],
                'new_location_id' => $data['location_id'],
                'new_zone_id' => $data['zone_id'],
                'new_lot_id' => $data['lot_id'],
                'new_grave_type' => $data['grave_type'],
                'new_facilities_json' => json_encode($data['facilities']),
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $booking->fill([
                'booking_code' => $this->codeService->make(
                    $booking->activity_type,
                    CarbonImmutable::parse($data['visit_date'], 'Asia/Jakarta'),
                    (string) $lot->normalized_lot_number,
                ),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'whatsapp_consented_at' => $data['whatsapp_consented_at'] ?? $booking->whatsapp_consented_at,
                'customer_phone' => $data['customer_phone'] ?? $booking->customer_phone,
                'additional_note' => $additionalNote !== '' ? $additionalNote : null,
                'location_id' => $data['location_id'],
                'zone_id' => $data['zone_id'],
                'lot_id' => $data['lot_id'],
                'grave_type' => $data['grave_type'],
                'visit_date' => $data['visit_date'],
                'time_slot_id' => $data['time_slot_id'],
                'status' => 'rescheduled',
                'cancel_reason' => null,
            ]);

            try {
                $booking->save();
            } catch (QueryException $e) {
                if (($e->errorInfo[0] ?? null) === '23505') {
                    throw new \RuntimeException('Lot sudah tidak tersedia untuk tanggal dan jam tersebut.');
                }
                throw $e;
            }

            $booking->facilities()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'chairs_count' => $data['facilities']['chairs_count'],
                    'burn_barrels_count' => $data['facilities']['burn_barrels_count'],
                    'has_tent' => $data['facilities']['has_tent'],
                    'has_prayer_table' => $data['facilities']['has_prayer_table'],
                    'has_lamp' => $data['facilities']['has_lamp'],
                ],
            );

            $this->whatsapp->queue($booking, 'rescheduled');

            return $booking;
        });
    }

    private function isExpired(Booking $booking): bool
    {
        $today = now()->timezone('Asia/Jakarta')->startOfDay();
        $visit = CarbonImmutable::parse($booking->visit_date, 'Asia/Jakarta')->startOfDay();

        return $visit->lessThan($today);
    }
}

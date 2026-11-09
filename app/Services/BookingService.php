<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Lot;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly BookingCodeService $codeService,
        private readonly BookingRulesService $rules,
        private readonly BookingWhatsAppService $whatsapp,
    ) {}

    /**
     * @param array{
     *  activity_type:string,
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
     *  customer_phone:string,
     *  additional_note?:?string
     *  ethics_consented_at:\DateTimeInterface
     * } $data
     */
    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $additionalNote = trim((string) ($data['additional_note'] ?? ''));

            $data['facilities'] = $this->rules->validate($data);

            $lot = Lot::query()->whereKey($data['lot_id'])->firstOrFail(['id', 'normalized_lot_number', 'normalized_size', 'size']);

            $booking = new Booking([
                'public_token' => (string) Str::ulid(),
                'activity_type' => $data['activity_type'],
                'booking_code' => $this->codeService->make(
                    $data['activity_type'],
                    CarbonImmutable::parse($data['visit_date']),
                    (string) $lot->normalized_lot_number,
                ),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'visitation_plan_id' => $data['visitation_plan_id'] ?? null,
                'planned_moment' => $data['planned_moment'] ?? null,
                'whatsapp_consented_at' => $data['whatsapp_consented_at'] ?? null,
                'customer_phone' => $data['customer_phone'],
                'additional_note' => $additionalNote !== '' ? $additionalNote : null,
                'ethics_consented_at' => $data['ethics_consented_at'],
                'location_id' => $data['location_id'],
                'zone_id' => $data['zone_id'],
                'lot_id' => $data['lot_id'],
                'grave_type' => $data['grave_type'],
                'visit_date' => $data['visit_date'],
                'time_slot_id' => $data['time_slot_id'],
                'status' => 'confirmed',
            ]);

            try {
                $booking->save();
            } catch (QueryException $e) {
                // Handle unique index for active bookings (race condition).
                if (($e->errorInfo[0] ?? null) === '23505') {
                    throw new \RuntimeException('Lot sudah tidak tersedia untuk tanggal dan jam tersebut.');
                }
                throw $e;
            }

            $booking->facilities()->create([
                'chairs_count' => $data['facilities']['chairs_count'],
                'burn_barrels_count' => $data['facilities']['burn_barrels_count'],
                'has_tent' => $data['facilities']['has_tent'],
                'has_prayer_table' => $data['facilities']['has_prayer_table'],
                'has_lamp' => $data['facilities']['has_lamp'],
            ]);

            $this->whatsapp->queue($booking, 'confirmation');

            return $booking;
        });
    }
}

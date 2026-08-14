<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BookingLeadTimeService
{
    /** @return array{value:int,unit:string} */
    public function rule(): array
    {
        $settings = DB::table('settings')->whereIn('key', [
            'booking_minimum_value',
            'booking_minimum_unit',
        ])->pluck('value', 'key');

        $unit = $settings->get('booking_minimum_unit') === 'hours' ? 'hours' : 'days';
        $max = $unit === 'hours' ? 2400 : 100;
        $value = max(0, min($max, (int) $settings->get('booking_minimum_value', 2)));

        return ['value' => $value, 'unit' => $unit];
    }

    public function earliestVisitAt(?CarbonImmutable $now = null): CarbonImmutable
    {
        $now = ($now ?? CarbonImmutable::now('Asia/Jakarta'))->timezone('Asia/Jakarta');
        $rule = $this->rule();

        return $rule['unit'] === 'hours'
            ? $now->addHours($rule['value'])
            : $now->startOfDay()->addDays($rule['value']);
    }

    public function allows(string $visitDate, string $startTime, ?CarbonImmutable $now = null): bool
    {
        $visitAt = CarbonImmutable::parse("{$visitDate} {$startTime}", 'Asia/Jakarta');

        return $visitAt->greaterThanOrEqualTo($this->earliestVisitAt($now));
    }

    public function message(): string
    {
        $rule = $this->rule();

        return $rule['unit'] === 'hours'
            ? "Pemesanan minimal {$rule['value']} jam sebelum jadwal kunjungan."
            : "Tanggal kunjungan minimal H+{$rule['value']}.";
    }

    /** @return array{minimum_value:int,minimum_unit:string,earliest_visit_at:string,message:string} */
    public function payload(): array
    {
        $rule = $this->rule();

        return [
            'minimum_value' => $rule['value'],
            'minimum_unit' => $rule['unit'],
            'earliest_visit_at' => $this->earliestVisitAt()->toIso8601String(),
            'message' => $this->message(),
        ];
    }
}

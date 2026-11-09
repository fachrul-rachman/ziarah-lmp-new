<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BookingWhatsAppService
{
    public function configured(): bool
    {
        return config('whatsapp.token') && config('whatsapp.phone_number_id') && config('whatsapp.api_version');
    }

    public function queue(Booking $booking, string $kind): void
    {
        if (! $booking->whatsapp_consented_at || ! $booking->customer_phone) {
            return;
        }

        $version = match ($kind) {
            'reminder' => $booking->visit_date->toDateString(),
            'rescheduled' => (string) DB::table('booking_reschedule_histories')->where('booking_id', $booking->id)->max('id'),
            default => $booking->public_token,
        };
        DB::table('booking_whatsapp_messages')->insertOrIgnore([
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'kind' => $kind,
            'deduplication_key' => "{$booking->id}:{$kind}:{$version}",
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // The scheduler drains the outbox, so a queue outage cannot undo a successful booking.
    }

    public function send(int $messageId): void
    {
        if (! $this->configured()) {
            DB::table('booking_whatsapp_messages')->where('id', $messageId)->where('status', 'pending')
                ->update(['error_code' => 'configuration_missing', 'updated_at' => now()]);

            return;
        }

        // Persist the claim before contacting Meta. Interrupted/ambiguous sends require review.
        $claimed = DB::table('booking_whatsapp_messages')->where('id', $messageId)->where('status', 'pending')
            ->update(['status' => 'sending', 'error_code' => null, 'updated_at' => now()]);
        if (! $claimed) {
            return;
        }

        DB::transaction(function () use ($messageId) {
            $message = DB::table('booking_whatsapp_messages')->find($messageId);
            $booking = Booking::query()->with(['location', 'zone', 'lot', 'timeSlot', 'visitationPlan'])
                ->lockForUpdate()->find($message->booking_id);
            $today = CarbonImmutable::now('Asia/Jakarta')->toDateString();
            $stale = ! $booking || ! $booking->whatsapp_consented_at
                || ! in_array($booking->status, ['confirmed', 'rescheduled'], true)
                || ($message->kind !== 'reminder' && $booking->booking_code !== $message->booking_code)
                || $booking->visit_date->toDateString() < $today;
            if ($message->kind !== 'reminder' && $booking) {
                $stale = $stale || DB::table('booking_whatsapp_messages')
                    ->where('booking_id', $booking->id)->whereIn('kind', ['confirmation', 'rescheduled'])
                    ->where('id', '>', $message->id)->exists();
            }
            if ($message->kind === 'reminder' && $booking) {
                $stale = $stale || ! $booking->visitation_plan_id
                    || $message->deduplication_key !== $booking->id.':reminder:'.$booking->visit_date->toDateString()
                    || $booking->visit_date->toDateString() !== CarbonImmutable::now('Asia/Jakarta')->addDay()->toDateString();
            }
            if ($stale) {
                DB::table('booking_whatsapp_messages')->where('id', $messageId)->update(['status' => 'skipped', 'updated_at' => now()]);

                return;
            }

            $start = CarbonImmutable::parse($booking->timeSlot->start_time)->format('H:i');
            $end = CarbonImmutable::parse($booking->timeSlot->start_time)->addMinutes(59)->format('H:i');
            $schedule = $booking->visit_date->locale('id')->translatedFormat('d F Y').", pukul {$start} sampai {$end} WIB";
            $lotLabel = 'Zona '.$booking->zone->name.', nomor '.$booking->lot->lot_number;
            $values = [$booking->customer_name, $booking->booking_code, $booking->activityLabel(), $schedule, $booking->location->name, $lotLabel];
            if ($message->kind === 'reminder') {
                $values = [$booking->customer_name, $booking->visitationPlan->deceased_name,
                    str_replace('Ziarah Terencana ', '', $booking->activityLabel()), $booking->booking_code,
                    $schedule, $booking->location->name, $lotLabel];
            }
            $target = $booking->visitation_plan_id
                ? 'plan/'.$booking->visitationPlan->public_token
                : 'booking/'.$booking->public_token;
            $template = match ($message->kind) {
                'reminder' => 'ziarah_planned_reminder',
                'rescheduled' => 'ziarah_booking_rescheduled',
                default => 'ziarah_booking_confirmation',
            };

            try {
                $response = Http::withToken(config('whatsapp.token'))->connectTimeout(5)->timeout(15)
                    ->post('https://graph.facebook.com/'.config('whatsapp.api_version').'/'.config('whatsapp.phone_number_id').'/messages', [
                        'messaging_product' => 'whatsapp',
                        'to' => $booking->customer_phone,
                        'type' => 'template',
                        'template' => [
                            'name' => $template,
                            'language' => ['code' => config('whatsapp.language')],
                            'components' => [
                                ['type' => 'body', 'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => (string) $value], $values)],
                                ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => $target]]],
                            ],
                        ],
                    ]);
                $providerId = $response->json('messages.0.id');
                $status = $response->successful() && $providerId ? 'accepted' : 'failed';
                $error = $status === 'accepted' ? null : 'meta_'.($response->json('error.code') ?? $response->status());
            } catch (ConnectionException) {
                $status = 'unknown';
                $providerId = null;
                $error = 'connection_ambiguous';
            }

            DB::table('booking_whatsapp_messages')->where('id', $messageId)->update([
                'status' => $status,
                'provider_message_id' => $providerId,
                'error_code' => $error,
                'sent_at' => $status === 'accepted' ? now() : null,
                'updated_at' => now(),
            ]);
        });
    }
}

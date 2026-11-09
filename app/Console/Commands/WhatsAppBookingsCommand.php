<?php

namespace App\Console\Commands;

use App\Jobs\SendBookingWhatsAppJob;
use App\Models\Booking;
use App\Services\BookingWhatsAppService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WhatsAppBookingsCommand extends Command
{
    protected $signature = 'whatsapp:bookings';

    protected $description = 'Queue H-1 reminders and dispatch pending booking WhatsApp messages.';

    public function handle(BookingWhatsAppService $whatsapp): int
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        if ($now->format('H:i') >= config('whatsapp.reminder_time', '09:00')) {
            Booking::query()->whereNotNull('visitation_plan_id')->whereNotNull('whatsapp_consented_at')
                ->whereDate('visit_date', $now->addDay()->toDateString())
                ->whereIn('status', ['confirmed', 'rescheduled'])
                ->chunkById(100, function ($bookings) use ($whatsapp) {
                    foreach ($bookings as $booking) {
                        $whatsapp->queue($booking, 'reminder');
                    }
                });
        }

        if (! $whatsapp->configured()) {
            $this->warn('WhatsApp configuration is incomplete. Pending messages are retained.');

            return self::SUCCESS;
        }

        DB::table('booking_whatsapp_messages')->where('status', 'pending')->orderBy('id')
            ->chunkById(100, function ($messages) {
                foreach ($messages as $message) {
                    SendBookingWhatsAppJob::dispatch($message->id);
                }
            });

        return self::SUCCESS;
    }
}

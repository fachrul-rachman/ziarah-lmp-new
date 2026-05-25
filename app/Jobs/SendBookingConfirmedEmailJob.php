<?php

namespace App\Jobs;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $bookingId)
    {
    }

    public function handle(): void
    {
        $booking = Booking::query()
            ->with(['location:id,name', 'zone:id,name', 'lot:id,lot_number,size', 'timeSlot:id,start_time', 'facilities'])
            ->find($this->bookingId);

        if (! $booking) {
            return;
        }

        try {
            Mail::to($booking->customer_email)->send(new BookingConfirmedMail($booking));
        } catch (\Throwable $e) {
            // Common in dev: Mailgun domain/account not activated or SMTP blocked.
            // We log and stop retries so queue workers keep running.
            report($e);
            return;
        }
    }
}

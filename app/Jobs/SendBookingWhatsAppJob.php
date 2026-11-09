<?php

namespace App\Jobs;

use App\Services\BookingWhatsAppService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingWhatsAppJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $messageId) {}

    // shortcut: locks live until completion, reconcile them if queued jobs are manually purged.
    public function uniqueId(): string
    {
        return (string) $this->messageId;
    }

    public function handle(BookingWhatsAppService $whatsapp): void
    {
        $whatsapp->send($this->messageId);
    }
}

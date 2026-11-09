<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RetryBookingWhatsAppCommand extends Command
{
    protected $signature = 'whatsapp:retry {message : ID in booking_whatsapp_messages} {--verified-not-sent : Confirm an ambiguous message was not sent by Meta}';

    protected $description = 'Requeue a failed booking message after checking its provider status.';

    public function handle(): int
    {
        $message = DB::table('booking_whatsapp_messages')->find($this->argument('message'));
        if (! $message || ! in_array($message->status, ['failed', 'unknown', 'sending'], true)) {
            $this->error('Only failed or ambiguous messages can be retried.');

            return self::FAILURE;
        }
        if (in_array($message->status, ['unknown', 'sending'], true) && ! $this->option('verified-not-sent')) {
            $this->error('Verify with Meta that the message was not sent before using --verified-not-sent.');

            return self::FAILURE;
        }
        DB::table('booking_whatsapp_messages')->where('id', $message->id)->where('status', $message->status)
            ->update(['status' => 'pending', 'error_code' => null, 'updated_at' => now()]);
        $this->info('Message requeued. The scheduler will dispatch it.');

        return self::SUCCESS;
    }
}

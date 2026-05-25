<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BookingsCompleteExpiredCommand extends Command
{
    protected $signature = 'bookings:complete-expired';
    protected $description = 'Mark confirmed/rescheduled bookings as completed when visit_date has passed (Asia/Jakarta).';

    public function handle(): int
    {
        $today = now()->timezone('Asia/Jakarta')->startOfDay()->toDateString();

        $updated = DB::table('bookings')
            ->whereIn('status', ['confirmed', 'rescheduled'])
            ->whereDate('visit_date', '<', $today)
            ->update([
                'status' => 'completed',
                'updated_at' => now(),
            ]);

        $this->info("completed={$updated}");

        return self::SUCCESS;
    }
}


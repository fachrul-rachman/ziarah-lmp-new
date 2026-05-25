<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDiscordNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiscordNotifyH1Command extends Command
{
    protected $signature = 'discord:notify-h1 {--target-date= : Override target date (YYYY-MM-DD) for testing} {--force : Ignore time check and always queue}';
    protected $description = 'Queue Discord H-1 notification when current time matches setting discord_notification_time (minute precision).';

    public function handle(): int
    {
        $webhook = trim((string) DB::table('settings')->where('key', 'discord_webhook_url')->value('value'));
        $time = trim((string) DB::table('settings')->where('key', 'discord_notification_time')->value('value'));

        if ($webhook === '' || $time === '') {
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            // Minute precision only (ignore seconds) to avoid missing due to cron seconds drift.
            $nowHm = now()->timezone('Asia/Jakarta')->format('H:i');
            if ($nowHm !== $time) {
                return self::SUCCESS;
            }
        }

        $targetDate = (string) ($this->option('target-date') ?: now()->timezone('Asia/Jakarta')->addDay()->toDateString());

        $existing = DB::table('notification_logs')
            ->where('target_date', $targetDate)
            ->whereIn('status', ['queued', 'processing', 'sent', 'skipped'])
            ->first();

        if ($existing) {
            return self::SUCCESS;
        }

        $id = DB::table('notification_logs')->insertGetId([
            'target_date' => $targetDate,
            'status' => 'queued',
            'message' => null,
            'attachments_json' => null,
            'sent_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ProcessDiscordNotificationJob::dispatch($id);

        $this->info("queued notification_log_id={$id} target_date={$targetDate}");

        return self::SUCCESS;
    }
}

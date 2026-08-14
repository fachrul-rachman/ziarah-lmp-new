<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDiscordNotificationJob;
use App\Services\ReportScheduleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiscordNotifyH1Command extends Command
{
    protected $signature = 'discord:notify-h1 {--target-date= : Override target date (YYYY-MM-DD) for testing} {--schedule-time= : Label this batch with HH:MM} {--force : Ignore time check and always queue}';

    protected $description = 'Queue Discord H-1 notification when current time matches an admin report schedule.';

    public function handle(ReportScheduleService $schedule): int
    {
        $webhook = trim((string) DB::table('settings')->where('key', 'discord_webhook_url')->value('value'));

        if ($webhook === '') {
            return self::SUCCESS;
        }

        $nowHm = now()->timezone('Asia/Jakarta')->format('H:i');
        $scheduledTime = trim((string) ($this->option('schedule-time') ?: $nowHm));

        if (! $this->option('force')) {
            if (! in_array($nowHm, $schedule->times(), true)) {
                return self::SUCCESS;
            }
        }

        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $scheduledTime)) {
            $this->error('schedule-time harus berformat HH:MM.');

            return self::FAILURE;
        }

        $targetDate = (string) ($this->option('target-date') ?: now()->timezone('Asia/Jakarta')->addDay()->toDateString());

        $existing = DB::table('notification_logs')
            ->where('target_date', $targetDate)
            ->where('scheduled_time', $scheduledTime)
            ->first();

        if ($existing) {
            if ($existing->status === 'failed') {
                DB::table('notification_logs')->where('id', $existing->id)->update([
                    'status' => 'queued',
                    'message' => null,
                    'updated_at' => now(),
                ]);
                ProcessDiscordNotificationJob::dispatch((int) $existing->id);
            }

            return self::SUCCESS;
        }

        $id = DB::table('notification_logs')->insertGetId([
            'target_date' => $targetDate,
            'scheduled_time' => $scheduledTime,
            'status' => 'queued',
            'message' => null,
            'attachments_json' => null,
            'sent_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ProcessDiscordNotificationJob::dispatch($id);

        $this->info("queued notification_log_id={$id} target_date={$targetDate} batch={$scheduledTime}");

        return self::SUCCESS;
    }
}

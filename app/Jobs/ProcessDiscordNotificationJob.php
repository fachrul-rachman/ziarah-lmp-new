<?php

namespace App\Jobs;

use App\Services\DiscordNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDiscordNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $notificationLogId)
    {
    }

    public function handle(DiscordNotificationService $service): void
    {
        $log = DB::table('notification_logs')->where('id', $this->notificationLogId)->first();
        if (! $log) {
            return;
        }

        DB::table('notification_logs')->where('id', $this->notificationLogId)->update([
            'status' => 'processing',
            'updated_at' => now(),
        ]);

        try {
            $result = $service->sendForTargetDate((string) $log->target_date, $this->notificationLogId);

            DB::table('notification_logs')->where('id', $this->notificationLogId)->update([
                'status' => (string) ($result['status'] ?? 'sent'),
                'message' => $result['message'] ?? null,
                'attachments_json' => isset($result['attachments']) ? json_encode($result['attachments']) : null,
                'sent_at' => ($result['status'] ?? null) === 'sent' ? now() : null,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('notification_logs')->where('id', $this->notificationLogId)->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'updated_at' => now(),
            ]);
        }
    }
}


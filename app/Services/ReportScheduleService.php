<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportScheduleService
{
    /** @return array<int,string> */
    public function times(): array
    {
        $settings = DB::table('settings')->whereIn('key', [
            'discord_notification_time',
            'discord_notification_times',
        ])->pluck('value', 'key');

        $times = json_decode((string) $settings->get('discord_notification_times', ''), true);
        if (! is_array($times)) {
            $times = [(string) $settings->get('discord_notification_time', '08:00')];
        }

        $times = array_values(array_unique(array_filter($times, fn ($time) => is_string($time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)
        )));
        sort($times);

        return $times ?: ['08:00'];
    }
}

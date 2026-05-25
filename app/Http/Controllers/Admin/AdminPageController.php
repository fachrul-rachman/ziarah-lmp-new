<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminPageController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('admin/dashboard');
    }

    public function locations(): Response
    {
        return Inertia::render('admin/locations');
    }

    public function timeSlots(): Response
    {
        return Inertia::render('admin/time-slots');
    }

    public function events(): Response
    {
        return Inertia::render('admin/events');
    }

    public function settings(): Response
    {
        $keys = ['discord_webhook_url', 'discord_notification_time'];
        $rows = DB::table('settings')->whereIn('key', $keys)->get(['key', 'value']);
        $map = $rows->mapWithKeys(fn ($r) => [(string) $r->key => (string) $r->value])->all();

        return Inertia::render('admin/settings', [
            'values' => [
                'discord_webhook_url' => $map['discord_webhook_url'] ?? '',
                'discord_notification_time' => $map['discord_notification_time'] ?? '08:00',
            ],
            'csrf_token' => csrf_token(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'discord_webhook_url' => ['nullable', 'string', 'max:2000', 'url'],
            'discord_notification_time' => ['required', 'string', 'regex:/^([01]\\d|2[0-3]):[0-5]\\d$/'],
        ], [
            'discord_notification_time.required' => 'Jam kirim wajib diisi.',
            'discord_notification_time.regex' => 'Format jam harus HH:MM (00-23).',
            'discord_webhook_url.url' => 'Format URL tidak valid.',
        ]);

        // Allow webhook empty string (treated as disabled)
        $webhook = trim((string) ($validated['discord_webhook_url'] ?? ''));
        $time = trim((string) $validated['discord_notification_time']);

        DB::table('settings')->upsert([
            [
                'key' => 'discord_webhook_url',
                'value' => $webhook,
            ],
            [
                'key' => 'discord_notification_time',
                'value' => $time,
            ],
        ], ['key'], ['value']);

        return redirect()->back()->with('success', 'Setting berhasil disimpan.');
    }
}

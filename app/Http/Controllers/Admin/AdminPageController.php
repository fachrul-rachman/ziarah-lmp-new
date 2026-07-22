<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $keys = ['discord_webhook_url', 'discord_notification_time', 'ethics_image_path'];
        $rows = DB::table('settings')->whereIn('key', $keys)->get(['key', 'value']);
        $map = $rows->mapWithKeys(fn ($r) => [(string) $r->key => (string) $r->value])->all();

        return Inertia::render('admin/settings', [
            'values' => [
                'discord_webhook_url' => $map['discord_webhook_url'] ?? '',
                'discord_notification_time' => $map['discord_notification_time'] ?? '08:00',
                'ethics_image_url' => ! empty($map['ethics_image_path']) && Storage::disk('public')->exists($map['ethics_image_path'])
                    ? Storage::disk('public')->url($map['ethics_image_path'])
                    : null,
            ],
            'csrf_token' => csrf_token(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'discord_webhook_url' => ['nullable', 'string', 'max:2000', 'url'],
            'discord_notification_time' => ['required', 'string', 'regex:/^([01]\\d|2[0-3]):[0-5]\\d$/'],
            'ethics_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'discord_notification_time.required' => 'Jam kirim wajib diisi.',
            'discord_notification_time.regex' => 'Format jam harus HH:MM (00-23).',
            'discord_webhook_url.url' => 'Format URL tidak valid.',
            'ethics_image.image' => 'File etika berziarah harus berupa gambar.',
            'ethics_image.mimes' => 'Gambar harus berformat JPG, PNG, atau WebP.',
            'ethics_image.max' => 'Ukuran gambar maksimal 5 MB.',
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

        if ($request->hasFile('ethics_image')) {
            $oldPath = trim((string) DB::table('settings')->where('key', 'ethics_image_path')->value('value'));
            $newPath = $request->file('ethics_image')->store('ethics', 'public');

            if (! $newPath) {
                return redirect()->back()->withErrors([
                    'ethics_image' => 'Gambar gagal disimpan. Silakan coba lagi.',
                ]);
            }

            DB::table('settings')->upsert([[
                'key' => 'ethics_image_path',
                'value' => $newPath,
            ]], ['key'], ['value']);

            if ($oldPath !== '' && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return redirect()->back()->with('success', 'Setting berhasil disimpan.');
    }
}

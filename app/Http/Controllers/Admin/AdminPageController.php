<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminPageController extends Controller
{
    public function __construct(private readonly ReportScheduleService $reportSchedule) {}

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
        $keys = [
            'discord_webhook_url',
            'discord_notification_time',
            'discord_notification_times',
            'booking_minimum_value',
            'booking_minimum_unit',
            'ethics_image_path',
            'ethics_pdf_path',
            'booking_notice_enabled',
            'booking_notice_title',
            'booking_notice_body',
            'booking_notice_start_date',
            'booking_notice_end_date',
            'booking_notice_image_path',
        ];
        $rows = DB::table('settings')->whereIn('key', $keys)->get(['key', 'value']);
        $map = $rows->mapWithKeys(fn ($r) => [(string) $r->key => (string) $r->value])->all();

        return Inertia::render('admin/settings', [
            'values' => [
                'discord_webhook_url' => $map['discord_webhook_url'] ?? '',
                'discord_notification_times' => $this->reportSchedule->times(),
                'booking_minimum_value' => (int) ($map['booking_minimum_value'] ?? 2),
                'booking_minimum_unit' => ($map['booking_minimum_unit'] ?? 'days') === 'hours' ? 'hours' : 'days',
                'ethics_image_url' => ! empty($map['ethics_image_path']) && Storage::disk('public')->exists($map['ethics_image_path'])
                    ? Storage::disk('public')->url($map['ethics_image_path'])
                    : null,
                'ethics_pdf_url' => ! empty($map['ethics_pdf_path']) && Storage::disk('public')->exists($map['ethics_pdf_path'])
                    ? Storage::disk('public')->url($map['ethics_pdf_path'])
                    : null,
                'booking_notice_enabled' => ($map['booking_notice_enabled'] ?? '0') === '1',
                'booking_notice_title' => $map['booking_notice_title'] ?? '',
                'booking_notice_body' => $map['booking_notice_body'] ?? '',
                'booking_notice_start_date' => $map['booking_notice_start_date'] ?? '',
                'booking_notice_end_date' => $map['booking_notice_end_date'] ?? '',
                'booking_notice_image_url' => ! empty($map['booking_notice_image_path']) && Storage::disk('public')->exists($map['booking_notice_image_path'])
                    ? Storage::disk('public')->url($map['booking_notice_image_path'])
                    : null,
            ],
            'csrf_token' => csrf_token(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'discord_webhook_url' => ['nullable', 'string', 'max:2000', 'url'],
            'discord_notification_time' => ['nullable', 'string', 'regex:/^([01]\\d|2[0-3]):[0-5]\\d$/'],
            'discord_notification_times' => ['nullable', 'array', 'min:1', 'max:6'],
            'discord_notification_times.*' => ['required', 'string', 'distinct', 'regex:/^([01]\\d|2[0-3]):[0-5]\\d$/'],
            'booking_minimum_value' => ['nullable', 'integer', 'min:0', 'max:2400'],
            'booking_minimum_unit' => ['nullable', 'string', 'in:hours,days'],
            'ethics_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'ethics_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:4096'],
            'booking_notice_enabled' => ['sometimes', 'boolean'],
            'booking_notice_title' => ['nullable', 'required_if:booking_notice_enabled,1', 'string', 'max:255'],
            'booking_notice_body' => ['nullable', 'required_if:booking_notice_enabled,1', 'string', 'max:2000'],
            'booking_notice_start_date' => ['nullable', 'required_if:booking_notice_enabled,1', 'date_format:Y-m-d'],
            'booking_notice_end_date' => ['nullable', 'required_if:booking_notice_enabled,1', 'date_format:Y-m-d', 'after_or_equal:booking_notice_start_date'],
            'booking_notice_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'discord_notification_time.regex' => 'Format jam harus HH:MM (00-23).',
            'discord_notification_times.min' => 'Minimal satu jam laporan harus diisi.',
            'discord_notification_times.max' => 'Maksimal enam jadwal laporan.',
            'discord_notification_times.*.distinct' => 'Jam laporan tidak boleh sama.',
            'discord_notification_times.*.regex' => 'Format jam laporan harus HH:MM (00-23).',
            'discord_webhook_url.url' => 'Format URL tidak valid.',
            'ethics_image.image' => 'File etika berziarah harus berupa gambar.',
            'ethics_image.mimes' => 'Gambar harus berformat JPG, PNG, atau WebP.',
            'ethics_image.max' => 'Ukuran gambar setelah diperkecil maksimal 2 MB.',
            'ethics_pdf.mimes' => 'File Etika Berziarah harus berformat PDF.',
            'ethics_pdf.max' => 'Ukuran PDF Etika Berziarah maksimal 4 MB.',
            'booking_notice_title.required_if' => 'Judul informasi wajib diisi saat informasi aktif.',
            'booking_notice_body.required_if' => 'Isi informasi wajib diisi saat informasi aktif.',
            'booking_notice_start_date.required_if' => 'Tanggal mulai wajib diisi saat informasi aktif.',
            'booking_notice_end_date.required_if' => 'Tanggal berakhir wajib diisi saat informasi aktif.',
            'booking_notice_end_date.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'booking_notice_image.image' => 'File informasi harus berupa gambar.',
            'booking_notice_image.mimes' => 'Gambar informasi harus berformat JPG, PNG, atau WebP.',
            'booking_notice_image.max' => 'Ukuran gambar informasi setelah diperkecil maksimal 3 MB.',
        ]);

        // Allow webhook empty string (treated as disabled)
        $webhook = trim((string) ($validated['discord_webhook_url'] ?? ''));
        $times = $validated['discord_notification_times'] ?? null;
        if (! is_array($times)) {
            $legacyTime = trim((string) ($validated['discord_notification_time'] ?? ''));
            $times = $legacyTime !== '' ? [$legacyTime] : $this->reportSchedule->times();
        }
        $times = array_values(array_unique($times));
        sort($times);

        $minimumUnit = (string) ($validated['booking_minimum_unit']
            ?? DB::table('settings')->where('key', 'booking_minimum_unit')->value('value')
            ?? 'days');
        $minimumValue = (int) ($validated['booking_minimum_value']
            ?? DB::table('settings')->where('key', 'booking_minimum_value')->value('value')
            ?? 2);

        if ($minimumUnit === 'days' && $minimumValue > 100) {
            return redirect()->back()->withErrors([
                'booking_minimum_value' => 'Batas minimum dalam hari maksimal 100.',
            ]);
        }

        DB::table('settings')->upsert([
            [
                'key' => 'discord_webhook_url',
                'value' => $webhook,
            ],
            [
                'key' => 'discord_notification_time',
                'value' => $times[0],
            ],
            [
                'key' => 'discord_notification_times',
                'value' => json_encode($times),
            ],
            [
                'key' => 'booking_minimum_value',
                'value' => (string) $minimumValue,
            ],
            [
                'key' => 'booking_minimum_unit',
                'value' => $minimumUnit,
            ],
            [
                'key' => 'booking_notice_enabled',
                'value' => $request->boolean('booking_notice_enabled') ? '1' : '0',
            ],
            [
                'key' => 'booking_notice_title',
                'value' => trim((string) ($validated['booking_notice_title'] ?? '')),
            ],
            [
                'key' => 'booking_notice_body',
                'value' => trim((string) ($validated['booking_notice_body'] ?? '')),
            ],
            [
                'key' => 'booking_notice_start_date',
                'value' => (string) ($validated['booking_notice_start_date'] ?? ''),
            ],
            [
                'key' => 'booking_notice_end_date',
                'value' => (string) ($validated['booking_notice_end_date'] ?? ''),
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

        if ($request->hasFile('ethics_pdf')) {
            $oldPath = trim((string) DB::table('settings')->where('key', 'ethics_pdf_path')->value('value'));
            $newPath = $request->file('ethics_pdf')->store('ethics', 'public');

            if (! $newPath) {
                return redirect()->back()->withErrors([
                    'ethics_pdf' => 'PDF Etika Berziarah gagal disimpan. Silakan coba lagi.',
                ]);
            }

            DB::table('settings')->upsert([[
                'key' => 'ethics_pdf_path',
                'value' => $newPath,
            ]], ['key'], ['value']);

            if ($oldPath !== '' && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        if ($request->hasFile('booking_notice_image')) {
            $oldPath = trim((string) DB::table('settings')->where('key', 'booking_notice_image_path')->value('value'));
            $newPath = $request->file('booking_notice_image')->store('booking-notices', 'public');

            if (! $newPath) {
                return redirect()->back()->withErrors([
                    'booking_notice_image' => 'Gambar informasi gagal disimpan. Silakan coba lagi.',
                ]);
            }

            DB::table('settings')->upsert([[
                'key' => 'booking_notice_image_path',
                'value' => $newPath,
            ]], ['key'], ['value']);

            if ($oldPath !== '' && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return redirect()->back()->with('success', 'Setting berhasil disimpan.');
    }
}

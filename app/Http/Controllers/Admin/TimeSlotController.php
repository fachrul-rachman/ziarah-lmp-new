<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TimeSlotController extends Controller
{
    public function index(): Response
    {
        $timeSlots = TimeSlot::query()
            ->orderBy('start_time')
            ->get()
            ->map(function (TimeSlot $timeSlot) {
                $start = CarbonImmutable::parse($timeSlot->start_time)->format('H:i');
                $end = CarbonImmutable::parse($timeSlot->start_time)->addMinutes(59)->format('H:i');

                return [
                    'id' => $timeSlot->id,
                    'start_time' => $start,
                    'end_time' => $end,
                ];
            })
            ->values();

        return Inertia::render('admin/time-slots', [
            'timeSlots' => $timeSlots,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i', 'unique:time_slots,start_time'],
        ], [
            'start_time.required' => 'Jam mulai wajib diisi.',
            'start_time.date_format' => 'Format jam harus HH:MM.',
            'start_time.unique' => 'Time slot dengan jam tersebut sudah ada.',
        ]);

        TimeSlot::query()->create($validated);

        return redirect()->back()->with('success', 'Time slot berhasil ditambahkan.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ], [
            'start_time.required' => 'Jam mulai wajib diisi.',
            'start_time.date_format' => 'Format jam mulai harus HH:MM.',
            'end_time.required' => 'Jam akhir wajib diisi.',
            'end_time.date_format' => 'Format jam akhir harus HH:MM.',
        ]);

        $start = CarbonImmutable::createFromFormat('H:i', $validated['start_time']);
        $end = CarbonImmutable::createFromFormat('H:i', $validated['end_time']);

        if ($start->greaterThan($end)) {
            return redirect()->back()->withErrors([
                'end_time' => 'Jam akhir harus lebih besar atau sama dengan jam mulai.',
            ]);
        }

        $now = now();
        $rows = [];
        for ($cursor = $start; $cursor->lessThanOrEqualTo($end); $cursor = $cursor->addHour()) {
            $rows[] = [
                'start_time' => $cursor->format('H:i'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (count($rows) === 0) {
            return redirect()->back()->withErrors([
                'start_time' => 'Range jam tidak valid.',
            ]);
        }

        // Idempotent: ignore duplicates (unique start_time).
        $createdCount = 0;
        foreach ($rows as $row) {
            $created = DB::table('time_slots')->insertOrIgnore($row);
            if ($created === 1) {
                $createdCount++;
            }
        }

        return redirect()->back()->with('success', "Bulk generate selesai. Slot baru dibuat: {$createdCount}.");
    }

    public function destroy(TimeSlot $timeSlot): RedirectResponse
    {
        $isUsed = DB::table('bookings')
            ->where('time_slot_id', $timeSlot->id)
            ->whereIn('status', ['confirmed', 'rescheduled'])
            ->exists();

        if ($isUsed) {
            return redirect()->back()->withErrors([
                'time_slot' => 'Time slot tidak bisa dihapus karena sedang dipakai booking aktif.',
            ]);
        }

        $timeSlot->delete();

        return redirect()->back()->with('success', 'Time slot berhasil dihapus.');
    }
}

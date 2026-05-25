<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /** @var array<string,string> */
    private const FACILITY_LABELS = [
        'chairs' => 'Kursi',
        'burn_barrels' => 'Tong Bakar',
        'tent' => 'Tenda',
        'prayer_table' => 'Meja Sembahyang',
        'lamp' => 'Lampu',
    ];

    public function index(): Response
    {
        $events = Event::query()
            ->with([
                'locations:id,name',
                'hiddenFacilities:id,event_id,facility_key',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Event $event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'start_date' => $event->start_date?->format('Y-m-d'),
                    'end_date' => $event->end_date?->format('Y-m-d'),
                    'locations' => $event->locations->map(fn ($l) => [
                        'id' => $l->id,
                        'name' => $l->name,
                    ])->values(),
                    'hidden_facilities' => $event->hiddenFacilities
                        ->pluck('facility_key')
                        ->values(),
                ];
            })
            ->values();

        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Location $l) => ['id' => $l->id, 'name' => $l->name])
            ->values();

        return Inertia::render('admin/events', [
            'events' => $events,
            'locations' => $locations,
            'facilityLabels' => self::FACILITY_LABELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'location_ids' => ['required', 'array', 'min:1'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
            'hidden_facilities' => ['nullable', 'array'],
            'hidden_facilities.*' => ['string', 'in:chairs,burn_barrels,tent,prayer_table,lamp'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus lebih besar atau sama dengan tanggal mulai.',
            'location_ids.required' => 'Minimal 1 lokasi wajib dipilih.',
            'location_ids.min' => 'Minimal 1 lokasi wajib dipilih.',
        ]);

        return DB::transaction(function () use ($validated) {
            $event = Event::query()->create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);

            $event->locations()->sync($validated['location_ids']);

            $hidden = array_values(array_unique($validated['hidden_facilities'] ?? []));
            if (count($hidden) > 0) {
                $now = now();
                DB::table('event_hidden_facilities')->insert(
                    array_map(fn (string $key) => [
                        'event_id' => $event->id,
                        'facility_key' => $key,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $hidden)
                );
            }

            return redirect()->back()->with('success', 'Event berhasil dibuat.');
        });
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'location_ids' => ['required', 'array', 'min:1'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
            'hidden_facilities' => ['nullable', 'array'],
            'hidden_facilities.*' => ['string', 'in:chairs,burn_barrels,tent,prayer_table,lamp'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus lebih besar atau sama dengan tanggal mulai.',
            'location_ids.required' => 'Minimal 1 lokasi wajib dipilih.',
            'location_ids.min' => 'Minimal 1 lokasi wajib dipilih.',
        ]);

        return DB::transaction(function () use ($validated, $event) {
            $event->update([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);

            $event->locations()->sync($validated['location_ids']);

            DB::table('event_hidden_facilities')->where('event_id', $event->id)->delete();

            $hidden = array_values(array_unique($validated['hidden_facilities'] ?? []));
            if (count($hidden) > 0) {
                $now = now();
                DB::table('event_hidden_facilities')->insert(
                    array_map(fn (string $key) => [
                        'event_id' => $event->id,
                        'facility_key' => $key,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $hidden)
                );
            }

            return redirect()->back()->with('success', 'Event berhasil diperbarui.');
        });
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->back()->with('success', 'Event berhasil dihapus.');
    }
}


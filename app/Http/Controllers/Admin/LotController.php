<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\Zone;
use App\Support\Normalization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotController extends Controller
{
    public function lotsForZone(Zone $zone): JsonResponse
    {
        $lots = Lot::query()
            ->where('zone_id', $zone->id)
            ->whereNull('deleted_at')
            ->orderBy('grave_type')
            ->orderBy('normalized_lot_number')
            ->get(['id', 'zone_id', 'grave_type', 'lot_number', 'size'])
            ->map(fn (Lot $lot) => [
                'id' => $lot->id,
                'zone_id' => $lot->zone_id,
                'grave_type' => $lot->grave_type,
                'lot_number' => $lot->lot_number,
                'size' => $lot->size,
            ])
            ->values();

        return response()->json([
            'zone_id' => $zone->id,
            'lots' => $lots,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'grave_type' => ['required', 'in:makam,kotak_abu'],
            'lot_number' => ['required', 'string', 'max:255'],
            'size' => ['required', 'string', 'max:255'],
        ], [
            'location_id.required' => 'Lokasi wajib dipilih.',
            'zone_id.required' => 'Zona wajib dipilih.',
            'grave_type.required' => 'Jenis makam wajib dipilih.',
            'lot_number.required' => 'Nomor lot wajib diisi.',
            'size.required' => 'Ukuran wajib diisi.',
        ]);

        $normalizedLot = Normalization::normalizeLotNumber($validated['lot_number']);

        $duplicate = Lot::query()
            ->where('grave_type', $validated['grave_type'])
            ->where('location_id', $validated['location_id'])
            ->where('zone_id', $validated['zone_id'])
            ->where('normalized_lot_number', $normalizedLot)
            ->whereNull('deleted_at')
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withErrors([
                'lot_number' => 'Lot dengan kombinasi tersebut sudah ada.',
            ]);
        }

        Lot::query()->create([
            'location_id' => $validated['location_id'],
            'zone_id' => $validated['zone_id'],
            'grave_type' => $validated['grave_type'],
            'lot_number' => Normalization::normalizeText($validated['lot_number']),
            'normalized_lot_number' => $normalizedLot,
            'size' => Normalization::normalizeSizeDisplay($validated['size']),
            'normalized_size' => Normalization::normalizeSizeKey($validated['size']),
        ]);

        return redirect()->back()->with('success', 'Lot berhasil ditambahkan.');
    }

    public function update(Request $request, Lot $lot): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'grave_type' => ['required', 'in:makam,kotak_abu'],
            'lot_number' => ['required', 'string', 'max:255'],
            'size' => ['required', 'string', 'max:255'],
        ]);

        $normalizedLot = Normalization::normalizeLotNumber($validated['lot_number']);

        $duplicate = Lot::query()
            ->where('grave_type', $validated['grave_type'])
            ->where('location_id', $validated['location_id'])
            ->where('zone_id', $validated['zone_id'])
            ->where('normalized_lot_number', $normalizedLot)
            ->whereNull('deleted_at')
            ->where('id', '!=', $lot->id)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withErrors([
                'lot_number' => 'Lot dengan kombinasi tersebut sudah ada.',
            ]);
        }

        $lot->update([
            'location_id' => $validated['location_id'],
            'zone_id' => $validated['zone_id'],
            'grave_type' => $validated['grave_type'],
            'lot_number' => Normalization::normalizeText($validated['lot_number']),
            'normalized_lot_number' => $normalizedLot,
            'size' => Normalization::normalizeSizeDisplay($validated['size']),
            'normalized_size' => Normalization::normalizeSizeKey($validated['size']),
        ]);

        return redirect()->back()->with('success', 'Lot berhasil diupdate.');
    }

    public function destroy(Lot $lot): RedirectResponse
    {
        $hasBooking = DB::table('bookings')->where('lot_id', $lot->id)->exists();

        if ($hasBooking) {
            $lot->delete();
        } else {
            $lot->forceDelete();
        }

        return redirect()->back()->with('success', 'Lot berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lot_ids' => ['required', 'array', 'min:1'],
            'lot_ids.*' => ['integer'],
        ]);

        $lots = Lot::query()
            ->whereIn('id', $validated['lot_ids'])
            ->get();

        foreach ($lots as $lot) {
            $hasBooking = DB::table('bookings')->where('lot_id', $lot->id)->exists();
            if ($hasBooking) {
                $lot->delete();
            } else {
                $lot->forceDelete();
            }
        }

        return redirect()->back()->with('success', 'Bulk delete lot berhasil diproses.');
    }
}

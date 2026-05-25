<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ZoneController extends Controller
{
    public function store(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama zona wajib diisi.',
        ]);

        $name = trim($validated['name']);

        $exists = DB::table('zones')
            ->where('location_id', $location->id)
            ->whereRaw('lower(name) = lower(?)', [$name])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors([
                'name' => 'Nama zona sudah ada untuk lokasi ini.',
            ]);
        }

        Zone::query()->create([
            'location_id' => $location->id,
            'name' => $name,
        ]);

        return redirect()->back()->with('success', 'Zona berhasil ditambahkan.');
    }

    public function update(Request $request, Location $location, Zone $zone): RedirectResponse
    {
        if ((int) $zone->location_id !== (int) $location->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama zona wajib diisi.',
        ]);

        $name = trim($validated['name']);

        $exists = DB::table('zones')
            ->where('location_id', $location->id)
            ->whereRaw('lower(name) = lower(?)', [$name])
            ->where('id', '!=', $zone->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors([
                'name' => 'Nama zona sudah ada untuk lokasi ini.',
            ]);
        }

        $zone->update(['name' => $name]);

        return redirect()->back()->with('success', 'Zona berhasil diupdate.');
    }

    public function destroy(Location $location, Zone $zone): RedirectResponse
    {
        if ((int) $zone->location_id !== (int) $location->id) {
            abort(404);
        }

        $hasActiveLots = DB::table('lots')
            ->where('zone_id', $zone->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasActiveLots) {
            return redirect()->back()->withErrors([
                'zone' => 'Zona tidak bisa dihapus karena masih punya lot aktif.',
            ]);
        }

        $zone->delete();

        return redirect()->back()->with('success', 'Zona berhasil dihapus.');
    }
}

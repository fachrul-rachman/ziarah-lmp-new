<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(Request $request): Response
    {
        $locations = Location::query()
            ->withCount(['zones', 'lots'])
            ->with([
                'zones' => fn ($q) => $q->withCount('lots')->orderBy('name'),
            ])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->values();

        return Inertia::render('admin/locations/index', [
            'locations' => $locations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
        ]);

        $name = trim($validated['name']);

        $exists = DB::table('locations')
            ->whereRaw('lower(name) = lower(?)', [$name])
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors([
                'name' => 'Nama lokasi sudah ada.',
            ]);
        }

        Location::query()->create([
            'name' => $name,
        ]);

        return redirect()->back()->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
        ]);

        $name = trim($validated['name']);

        $exists = DB::table('locations')
            ->whereRaw('lower(name) = lower(?)', [$name])
            ->where('id', '!=', $location->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors([
                'name' => 'Nama lokasi sudah ada.',
            ]);
        }

        $location->update(['name' => $name]);

        return redirect()->back()->with('success', 'Lokasi berhasil diupdate.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $hasActiveZones = DB::table('zones')
            ->where('location_id', $location->id)
            ->exists();

        $hasActiveLots = DB::table('lots')
            ->where('location_id', $location->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasActiveZones || $hasActiveLots) {
            return redirect()->back()->withErrors([
                'location' => 'Lokasi tidak bisa dihapus karena masih punya zona/lot aktif.',
            ]);
        }

        $location->delete();

        return redirect()->back()->with('success', 'Lokasi berhasil dihapus.');
    }
}

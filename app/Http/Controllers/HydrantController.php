<?php

namespace App\Http\Controllers;

use App\Models\Hydrant;
use App\Models\HydrantType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HydrantController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('view-master-data') && !Auth::user()->can('manage-assets')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $query = Hydrant::query()->with(['type', 'room.floor.building']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhereHas('type', function ($qType) use ($request) {
                      $qType->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        if ($request->building && $request->building !== 'all') {
            $query->whereHas('room.floor', function ($q) use ($request) {
                $q->where('building_id', $request->building);
            });
        }

        if ($request->floor && $request->floor !== 'all') {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('floor_id', $request->floor);
            });
        }

        if ($request->room && $request->room !== 'all') {
            $query->where('room_id', $request->room);
        }

        return Inertia::render('MasterData/Assets/Hydrant/Index', [
            'hydrants'      => $query->latest()->paginate(10)->withQueryString(),
            'hydrant_types' => HydrantType::all(),
            'buildings'     => \App\Models\Building::all(),
            'floors'        => \App\Models\Floor::with('building')->get(),
            'rooms'         => Room::with('floor.building')->get(),
            'filters'       => $request->only(['search', 'building', 'floor', 'room']),
            'can'           => [
                'manage' => Auth::user()->can('manage-assets'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage-assets'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'code'            => 'required|string|unique:hydrants',
            'hydrant_type_id' => 'required|exists:hydrant_types,id',
            'room_id'         => 'required|exists:rooms,id',
            'status'          => 'required|in:safe,warning,critical',
            'location_data'   => 'nullable|array',
        ]);

        Hydrant::create($validated);
        return redirect()->back()->with('success', 'Hydrant berhasil ditambahkan!');
    }

    public function update(Request $request, Hydrant $hydrant)
    {
        abort_unless(Auth::user()->can('manage-assets'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'code'            => 'required|string|unique:hydrants,code,' . $hydrant->id,
            'hydrant_type_id' => 'required|exists:hydrant_types,id',
            'room_id'         => 'required|exists:rooms,id',
            'status'          => 'required|in:safe,warning,critical',
            'location_data'   => 'nullable|array',
        ]);

        $hydrant->update($validated);
        return redirect()->back()->with('success', 'Data hydrant berhasil diperbarui!');
    }

    public function destroy(Hydrant $hydrant)
    {
        abort_unless(Auth::user()->can('manage-assets'), 403, 'Anda tidak memiliki izin.');

        $hydrant->delete();
        return redirect()->back()->with('success', 'Hydrant berhasil dihapus!');
    }
}
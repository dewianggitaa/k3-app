<?php

namespace App\Http\Controllers;

use App\Models\Apar;
use App\Models\AparType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AparController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('view-master-data') && !Auth::user()->can('manage-assets')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $query = Apar::query()->with(['type', 'room.floor.building']);

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

        return Inertia::render('MasterData/Assets/Apar/Index', [
            'apars'     => $query->latest()->paginate(10)->withQueryString(),
            'aparTypes' => AparType::all(),
            'buildings' => \App\Models\Building::all(),
            'floors'    => \App\Models\Floor::with('building')->get(),
            'rooms'     => Room::with('floor.building')->get(),
            'filters'   => $request->only(['search', 'building', 'floor', 'room']),
            'can'       => [
                'manage' => Auth::user()->can('manage-assets'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage-assets'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'code'             => 'required|string|unique:apars,code',
            'apar_type_id'     => 'required|exists:apar_types,id',
            'room_id'          => 'required|exists:rooms,id',
            'weight'           => 'required|numeric',
            'expired_at'       => 'required|date',
            'last_refilled_at' => 'nullable|date',
        ]);

        Apar::create($validated);
        return back();
    }

    public function update(Request $request, Apar $apar)
    {
        abort_unless(Auth::user()->can('manage-assets'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'code'             => 'required|string|unique:apars,code,' . $apar->id,
            'apar_type_id'     => 'required|exists:apar_types,id',
            'room_id'          => 'required|exists:rooms,id',
            'weight'           => 'required|numeric',
            'expired_at'       => 'required|date',
            'last_refilled_at' => 'nullable|date',
        ]);

        $apar->update($validated);
        return back();
    }

    public function destroy(Apar $apar)
    {
        abort_unless(Auth::user()->can('manage-assets'), 403, 'Anda tidak memiliki izin.');

        $apar->delete();
        return back();
    }
}
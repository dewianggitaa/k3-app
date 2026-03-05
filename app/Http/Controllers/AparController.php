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
            $query->where('code', 'like', "%{$request->search}%")
                  ->orWhereHas('type', function ($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
        }

        return Inertia::render('MasterData/Assets/Apar/Index', [
            'apars'     => $query->latest()->paginate(10)->withQueryString(),
            'aparTypes' => AparType::all(),
            'rooms'     => Room::with('floor.building')->get(),
            'filters'   => $request->only(['search']),
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
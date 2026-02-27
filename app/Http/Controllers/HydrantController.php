<?php

namespace App\Http\Controllers;

use App\Models\Hydrant;
use App\Models\HydrantType;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HydrantController extends Controller
{
    public function index(Request $request)
    {
        $query = Hydrant::query()->with(['type', 'room.floor.building']);

        if ($request->search) {
            $query->where('code', 'like', "%{$request->search}%")
                  ->orWhereHas('type', function($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
        }

        return Inertia::render('MasterData/Assets/Hydrant/Index', [
            'hydrants' => $query->latest()->paginate(10)->withQueryString(),
            'hydrant_types' => HydrantType::all(),
            'rooms' => Room::with('floor.building')->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:hydrants',
            'hydrant_type_id' => 'required|exists:hydrant_types,id',
            'room_id' => 'required|exists:rooms,id',
            'status' => 'required|in:safe,warning,critical',
            'location_data' => 'nullable|array',
        ]);

        Hydrant::create($validated);

        return redirect()->back()->with('success', 'Hydrant berhasil ditambahkan!');
    }
    
    public function update(Request $request, Hydrant $hydrant)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:hydrants,code,' . $hydrant->id,
            'hydrant_type_id' => 'required|exists:hydrant_types,id',
            'room_id' => 'required|exists:rooms,id',
            'status' => 'required|in:safe,warning,critical',
            'location_data' => 'nullable|array',
        ]);

        $hydrant->update($validated);

        return redirect()->back()->with('success', 'Data hydrant berhasil diperbarui!');
    }

    public function destroy(Hydrant $hydrant)
    {
        $hydrant->delete();

        return redirect()->back()->with('success', 'Hydrant berhasil dihapus!');
    }
}
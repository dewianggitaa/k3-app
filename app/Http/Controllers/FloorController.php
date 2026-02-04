<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Building;

class FloorController extends Controller
{
    public function index(Request $request)
    {
        $query = Floor::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('MasterData/Floor/Index', [
            'floors' => Floor::with('building')
                        ->when($request->search, function ($query, $search) {
                            $query->where('name', 'like', "%{$search}%")
                                  ->orWhereHas('building', function ($q) use ($search) {
                                      $q->where('name', 'like', "%{$search}%");
                                  });
                        })
                        ->paginate(10)
                        ->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'name' => 'required|string|max:255',
        ]);

        $floor = Floor::create($validated);
        return response()->json(['message' => 'Floor created', 'data' => $floor], 201);
    }

    public function show($id)
    {
        $floor = Floor::with('building')->find($id);
        if (!$floor) return response()->json(['message' => 'Not found'], 404);
        return response()->json($floor);
    }

    public function update(Request $request, $id)
    {
        $floor = Floor::find($id);
        if (!$floor) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'building_id' => 'sometimes|exists:buildings,id',
            'name' => 'sometimes|string|max:255',
        ]);

        $floor->update($validated);
        return response()->json(['message' => 'Floor updated', 'data' => $floor]);
    }

    public function destroy($id)
    {
        $floor = Floor::find($id);
        if (!$floor) return response()->json(['message' => 'Not found'], 404);
        $floor->delete();
        return response()->json(['message' => 'Floor deleted']);
    }

    public function getByBuilding($buildingId)
    {
        $floors = Floor::where('building_id', $buildingId)->get();
        return response()->json($floors);
    }
}
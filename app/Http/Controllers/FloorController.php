<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Building;
use Illuminate\Support\Facades\Storage;

class FloorController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('view-master-data') && !Auth::user()->can('manage-floors')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return Inertia::render('MasterData/Floor/Index', [
            'floors' => Floor::with('building')
                        ->when($request->search, function ($query, $search) {
                            $query->where('name', 'like', "%{$search}%")
                                  ->orWhereHas('building', function ($q) use ($search) {
                                      $q->where('name', 'like', "%{$search}%");
                                  });
                        })
                        ->when($request->building, function ($query, $buildingId) {
                            if ($buildingId !== 'all') {
                                $query->where('building_id', $buildingId);
                            }
                        })
                        ->paginate(10)
                        ->withQueryString(),
            'buildings' => Building::all(),
            'filters'   => $request->only(['search', 'building']),
            'can'       => [
                'manage' => Auth::user()->can('manage-floors'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage-floors'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'name'        => 'required|string|max:255',
            'map_image'   => 'nullable|image|mimes:jpeg,png,jpg',
        ]);

        if ($request->hasFile('map_image')) {
            $file      = $request->file('map_image');
            $imageSize = getimagesize($file->getRealPath());
            $filename  = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('floors', $filename, 'public');
            $validated['map_image_path'] = $filename;
            $validated['map_width']      = $imageSize[0];
            $validated['map_height']     = $imageSize[1];
        }

        unset($validated['map_image']);
        Floor::create($validated);

        return redirect()->back()->with('message', 'Mantap! Data berhasil dibuat.');
    }

    public function show($id)
    {
        $floor = Floor::with('building')->find($id);
        if (!$floor) return response()->json(['message' => 'Not found'], 404);
        return response()->json($floor);
    }

    public function update(Request $request, $id)
    {
        abort_unless(Auth::user()->can('manage-floors'), 403, 'Anda tidak memiliki izin.');

        $floor     = Floor::findOrFail($id);
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'name'        => 'required|string|max:255',
            'map_image'   => 'nullable|image|mimes:jpeg,png,jpg',
        ]);

        if ($request->hasFile('map_image')) {
            $file      = $request->file('map_image');
            $imageSize = getimagesize($file->getRealPath());

            if ($floor->map_image_path) {
                Storage::disk('public')->delete('floors/' . $floor->map_image_path);
            }

            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('floors', $filename, 'public');
            $validated['map_image_path'] = $filename;
            $validated['map_width']      = $imageSize[0];
            $validated['map_height']     = $imageSize[1];
        }

        $floor->update($validated);

        return redirect()->back()->with('message', 'Mantap! Data berhasil diupdate.');
    }

    public function destroy($id)
    {
        abort_unless(Auth::user()->can('manage-floors'), 403, 'Anda tidak memiliki izin.');

        $floor = Floor::find($id);
        if (!$floor) return response()->json(['message' => 'Not found'], 404);
        $floor->delete();

        return redirect()->back()->with('message', 'Mantap! Data berhasil dihapus.');
    }

    public function getByBuilding($buildingId)
    {
        $floors = Floor::where('building_id', $buildingId)->get();
        return response()->json($floors);
    }

    public function mapping($id)
    {
        if (!Auth::user()->can('view-master-data') && !Auth::user()->can('manage-floors')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $floor = Floor::with('rooms')->findOrFail($id);

        return Inertia::render('MasterData/Floor/Mapping', [
            'floor' => $floor,
            'can'   => [
                'manage' => Auth::user()->can('manage-floors'),
            ],
        ]);
    }
}
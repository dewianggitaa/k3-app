<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Building;
use Illuminate\Support\Facades\Storage;

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
            'map_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // max 2MB
        ]);

        if ($request->hasFile('map_image')) {
            $file = $request->file('map_image');
            
            $filename = time() . '_' . $file->getClientOriginalName();
            
            $file->storeAs('public/floors', $filename);
            
            $validated['map_image'] = $filename;
        }

        $floor = Floor::create($validated);
        
        return response()->json([
            'message' => 'Floor created with map', 
            'data' => $floor
        ], 201);
    }

    public function show($id)
    {
        $floor = Floor::with('building')->find($id);
        if (!$floor) return response()->json(['message' => 'Not found'], 404);
        return response()->json($floor);
    }

    public function update(Request $request, $id)
    {
        $floor = Floor::findOrFail($id);

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'name' => 'required|string|max:255',
            'map_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
        ]);

        if ($request->hasFile('map_image')) {
            // 1. Definisikan file dulu (biar getRealPath() gak error)
            $file = $request->file('map_image');
            
            // 2. Ambil dimensi gambar
            $imageSize = getimagesize($file->getRealPath());
            
            // 3. Hapus foto lama kalau ada
            if ($floor->map_image_path) {
                Storage::disk('public')->delete('floors/' . $floor->map_image_path);
            }

            // 4. Proses simpan
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('floors', $filename, 'public');
            
            // 5. Masukkan ke array validated sesuai nama kolom di migration lu
            $validated['map_image_path'] = $filename;
            $validated['map_width'] = $imageSize[0];  // index 0 itu lebar
            $validated['map_height'] = $imageSize[1]; // index 1 itu tinggi
        }

        // Update ke DB
        $floor->update($validated);
        
        return redirect()->back()->with('message', 'Mantap! Data berhasil diupdate.');
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

    public function mapping($id)
    {
        // Ambil data lantai beserta ruangan-ruangannya
        $floor = Floor::with('rooms')->findOrFail($id);
        
        return Inertia::render('MasterData/Floor/Mapping', [
            'floor' => $floor,
        ]);
    }
}
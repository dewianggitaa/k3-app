<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\P3k;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetMappingController extends Controller
{

    public function index($floor_id)
    {
        $floor = Floor::findOrFail($floor_id);
        
        $rooms = Room::where('floor_id', $floor_id)
            ->whereNotNull('coordinates')
            ->get();

        // Ambil P3K yang berada di ruangan-ruangan milik lantai ini
        $p3ks = P3k::whereHas('room', function ($query) use ($floor_id) {
                $query->where('floor_id', $floor_id);
            })
            ->with('room:id,name')
            ->orderBy('code', 'asc')
            ->get();

        return Inertia::render('MasterData/Assets/AssetMapping', [
            'floor' => $floor,
            'rooms' => $rooms,
            'p3ks'  => $p3ks,
        ]);
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'id'            => 'required|exists:p3ks,id',
            'location_data' => 'nullable|array',
            'location_data.x' => 'required_with:location_data|numeric',
            'location_data.y' => 'required_with:location_data|numeric',
        ]);

        try {
            $p3k = P3K::findOrFail($request->id);
            
            $p3k->update([
                'location_data' => $request->location_data
            ]);

            $message = $request->location_data 
                ? "Posisi P3K {$p3k->code} berhasil diperbarui." 
                : "Posisi P3K {$p3k->code} berhasil di-reset.";

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Gagal memperbarui posisi: ' . $e->getMessage()
            ]);
        }
    }
}
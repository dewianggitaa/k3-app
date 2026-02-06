<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\P3k;
use App\Models\Apar;
use App\Models\Hydrant;
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

        $p3ks = P3k::whereHas('room', function ($query) use ($floor_id) {
                $query->where('floor_id', $floor_id);
            })
            ->with('room:id,name')
            ->orderBy('code', 'asc')
            ->get();

        $apars = Apar::whereHas('room', function ($query) use ($floor_id) {
                $query->where('floor_id', $floor_id);
            })
            ->with('room:id,name')
            ->orderBy('code', 'asc')
            ->get();
        
         $hydrants = Hydrant::whereHas('room', function ($query) use ($floor_id) {
                $query->where('floor_id', $floor_id);
            })
            ->with('room:id,name')
            ->orderBy('code', 'asc')
            ->get(); 

        return Inertia::render('MasterData/Assets/AssetMapping', [
            'floor' => $floor,
            'rooms' => $rooms,
            'p3ks'  => $p3ks,
            'apars' => $apars,
            'hydrants' => $hydrants,
        ]);
    }

    public function updateLocation(Request $request, $type)
    {
        // Mapping tipe ke Model
        $modelClass = match($type) {
            'p3k'   => \App\Models\P3k::class,
            'apar'  => \App\Models\Apar::class,
            'hydrant' => \App\Models\Hydrant::class, // Tambahkan yang lain di sini
            default => abort(404, 'Tipe aset tidak valid')
        };

        $request->validate([
            'id'            => "required|exists:{$type}s,id", // Dinamis cek ke tabel yang sesuai
            'location_data' => 'nullable|array',
            'location_data.x' => 'required_with:location_data|numeric',
            'location_data.y' => 'required_with:location_data|numeric',
        ]);

        try {
            $asset = $modelClass::findOrFail($request->id);
            
            $asset->update([
                'location_data' => $request->location_data
            ]);

            $typeName = strtoupper($type);
            $message = $request->location_data 
                ? "Posisi {$typeName} {$asset->code} berhasil diperbarui." 
                : "Posisi {$typeName} {$asset->code} berhasil di-reset.";

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Gagal memperbarui posisi: ' . $e->getMessage()
            ]);
        }
    }
    
}
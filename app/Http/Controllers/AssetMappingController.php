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

    public function index($floor_id, Request $request)
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
            'target_id' => $request->target_id,
            'target_type' => $request->target_type,
        ]);
    }

    public function updateLocation(Request $request, $type)
    {
        $modelClass = match($type) {
            'p3k'   => P3k::class,
            'apar'  => Apar::class,
            'hydrant' => Hydrant::class, 
            default => abort(404, 'Tipe aset tidak valid')
        };

        $request->validate([
            'id'            => "required|exists:{$type}s,id",
            'location_data' => 'nullable|array',
            'location_data.x' => 'required_with:location_data|numeric',
            'location_data.y' => 'required_with:location_data|numeric',
        ]);

        try {
            $asset = $modelClass::findOrFail($request->id);
            
            // --- LOGIKA MAPPING DOUBLE APAR ---
            // Cek apakah ini APAR dan kodenya berakhiran -A atau -B (Case Insensitive)
            if ($type === 'apar' && preg_match('/-[A-B]$/i', $asset->code)) {
                
                // Hapus akhiran -A atau -B untuk mendapatkan Base Code
                // Contoh: "APAR-03M1001-A" menjadi "APAR-03M1001"
                $baseCode = preg_replace('/-[A-B]$/i', '', $asset->code);

                // Update massal semua APAR yang berawalan Base Code tersebut
                // Pakai LIKE agar -A dan -B kena update semua ke koordinat yang sama
                $modelClass::where('code', 'LIKE', $baseCode . '-%')
                    ->update([
                        'location_data' => $request->location_data ? json_encode($request->location_data) : null
                    ]);
                    
            } else {
                // Update normal untuk P3K, Hydrant, atau Single APAR
                $asset->update([
                    'location_data' => $request->location_data
                ]);
            }

            $typeName = strtoupper($type);
            $message = $request->location_data 
                ? "Posisi {$typeName} {$asset->code} berhasil diperbarui." 
                : "Posisi {$typeName} {$asset->code} berhasil di-reset.";

            // Modifikasi pesan khusus untuk Double APAR agar admin tahu
            if ($type === 'apar' && preg_match('/-[A-B]$/i', $asset->code)) {
                $message = $request->location_data 
                    ? "Posisi Kotak {$typeName} {$baseCode} (A & B) berhasil diperbarui." 
                    : "Posisi Kotak {$typeName} {$baseCode} (A & B) berhasil di-reset.";
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Gagal memperbarui posisi: ' . $e->getMessage()
            ]);
        }
    }
    
}
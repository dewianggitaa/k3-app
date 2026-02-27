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
            ->with('room:id,name', 'type:id,name')
            ->orderBy('code', 'asc')
            ->get();

        $apars = Apar::whereHas('room', function ($query) use ($floor_id) {
                $query->where('floor_id', $floor_id);
            })
            ->with('room:id,name', 'type:id,name')
            ->orderBy('code', 'asc')
            ->get();
        
         $hydrants = Hydrant::whereHas('room', function ($query) use ($floor_id) {
                $query->where('floor_id', $floor_id);
            })
            ->with('room:id,name', 'type:id,name')
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
            if ($type === 'apar' && preg_match('/-[A-B]$/i', $asset->code)) {
                $baseCode = preg_replace('/-[A-B]$/i', '', $asset->code);
                $modelClass::where('code', 'LIKE', $baseCode . '-%')
                    ->update([
                        'location_data' => $request->location_data ? json_encode($request->location_data) : null
                    ]);
                    
            } else {
                $asset->update([
                    'location_data' => $request->location_data
                ]);
            }

            $typeName = strtoupper($type);
            $message = $request->location_data 
                ? "Posisi {$typeName} {$asset->code} berhasil diperbarui." 
                : "Posisi {$typeName} {$asset->code} berhasil di-reset.";

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
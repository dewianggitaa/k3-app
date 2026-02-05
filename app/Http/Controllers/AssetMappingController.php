<?php

namespace App\Http\Controllers;

use App\Models\P3k;
use App\Models\Floor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetMappingController extends Controller
{
    public function index($floorId)
    {
        $floor = Floor::with(['p3ks.type'])->findOrFail($floorId);

        return Inertia::render('Assets/AssetMapping', [
            'floor' => $floor,
            'initialAssets' => $floor->p3ks,
        ]);
    }

    public function updatePosition(Request $request, $id)
    {
        $p3k = P3k::findOrFail($id);
        
        $p3k->update([
            'location_data' => $request->location_data
        ]);

        return back()->with('success', 'Posisi aset berhasil diperbarui!');
    }
}

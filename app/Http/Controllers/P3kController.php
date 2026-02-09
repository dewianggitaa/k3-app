<?php

namespace App\Http\Controllers;

use App\Models\P3k;
use App\Models\P3kType;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class P3kController extends Controller
{
    public function index(Request $request)
    {
        $query = P3k::query()->with(['p3k_type', 'room.floor.building']);

        if ($request->search) {
            $query->where('code', 'like', "%{$request->search}%")
                  ->orWhereHas('p3k_type', function($q) use ($request) {
                      $q->where('name', 'like', "%{$request->search}%");
                  });
        }

        return Inertia::render('MasterData/Assets/P3k/Index', [
            'p3ks' => $query->latest()->paginate(10)->withQueryString(),
            'p3k_types' => P3kType::all(),
            'rooms' => Room::with('floor.building')->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:p3ks',
            'p3k_type_id' => 'required|exists:p3k_types,id',
            'room_id' => 'required|exists:rooms,id', // Pastikan ID Room valid
            'status' => 'required|in:safe,warning,critical',
            'location_data' => 'nullable|array', // Menerima koordinat Pinpoint (JSON)
        ]);

        P3k::create($validated);

        return redirect()->back()->with('success', 'Kotak P3K berhasil ditambahkan!');
    }

    public function update(Request $request, P3k $p3k)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:p3ks,code,' . $p3k->id,
            'p3k_type_id' => 'required|exists:p3k_types,id',
            'room_id' => 'required|exists:rooms,id',
            'status' => 'required|in:safe,warning,critical',
            'location_data' => 'nullable|array',
        ]);

        $p3k->update($validated);

        return redirect()->back()->with('success', 'Data P3K berhasil diperbarui!');
    }

    public function destroy(P3k $p3k)
    {
        $p3k->delete();

        return redirect()->back()->with('success', 'Kotak P3K berhasil dihapus!');
    }
}
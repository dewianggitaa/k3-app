<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Floor;
use App\Models\Building;

class RoomController extends Controller
{
   public function index(Request $request)
    {
        $query = Room::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return inertia('MasterData/Room/Index', [
            'rooms' => Room::with('floor.building') // Load relasi berantai
                        ->when($request->search, function($query, $search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhereHas('floor', function($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                        ->orWhereHas('building', function($qb) use ($search) {
                                            $qb->where('name', 'like', "%{$search}%");
                                        });
                                });
                        })
                        ->paginate(10)
                        ->withQueryString(),
            'floors' => Floor::with('building')->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:50',
        ]);

        $room = Room::create($validated);
        return response()->json(['message' => 'Room created', 'data' => $room], 201);
    }

    public function show($id)
    {
        $room = Room::with('floor')->find($id);
        if (!$room) return response()->json(['message' => 'Not found'], 404);
        return response()->json($room);
    }

    public function update(Request $request, $id)
    {
        // 1. Cari datanya
        $room = Room::findOrFail($id);

        // 2. Validasi (Ganti required biar pasti terisi)
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name'     => 'required|string|max:255',
            'color'    => 'nullable|string|max:50',
        ]);

        // 3. Update data
        $room->update($validated);

        // 4. Redirect Back (WAJIB buat Inertia)
        // Inertia bakal otomatis baca ini sebagai sukses dan trigger onSuccess di Vue
        return redirect()->back();
    }

    public function destroy($id)
    {
        $room = Room::find($id);
        if (!$room) return response()->json(['message' => 'Not found'], 404);
        $room->delete();
        return response()->json(['message' => 'Room deleted']);
    }


    public function getByFloor($floorId)
    {
        $rooms = Room::where('floor_id', $floorId)->get();
        return response()->json($rooms);
    }

    public function updateCoordinates(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'coordinates' => 'required|array|min:3',
        ]);

        $room = Room::findOrFail($request->room_id);
    
        $room->coordinates = $request->coordinates;
        $room->save();

        return redirect()->back()->with('message', 'Area ruangan berhasil diperbarui!');
    }
}
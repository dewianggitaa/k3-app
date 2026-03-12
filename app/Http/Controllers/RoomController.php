<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Floor;
use App\Models\Building;
use App\Models\User;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('view-master-data') && !Auth::user()->can('manage-rooms')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return inertia('MasterData/Room/Index', [
            'rooms' => Room::with(['floor.building', 'pic'])
                        ->when($request->search, function ($query, $search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhereHas('floor', function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                        ->orWhereHas('building', function ($qb) use ($search) {
                                            $qb->where('name', 'like', "%{$search}%");
                                        });
                                });
                        })
                        ->when($request->building, function ($query, $buildingId) {
                            if ($buildingId !== 'all') {
                                $query->whereHas('floor', function ($q) use ($buildingId) {
                                    $q->where('building_id', $buildingId);
                                });
                            }
                        })
                        ->when($request->floor, function ($query, $floorId) {
                            if ($floorId !== 'all') {
                                $query->where('floor_id', $floorId);
                            }
                        })
                        ->paginate(10)
                        ->withQueryString(),

            'floors'    => Floor::with('building')->get(),
            'buildings' => Building::all(),
            'users'     => User::with('position')->select('id', 'name', 'position_id')->get(),
            'filters'   => $request->only(['search', 'building', 'floor']),
            'can'     => [
                'manage' => Auth::user()->can('manage-rooms'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage-rooms'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'floor_id'    => 'required|exists:floors,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:rooms,code',
            'color'       => 'nullable|string|max:50',
            'pic_user_id' => 'nullable|exists:users,id',
        ]);

        Room::create($validated);
        return redirect()->back();
    }

    public function show($id)
    {
        $room = Room::with('floor')->find($id);
        if (!$room) return response()->json(['message' => 'Not found'], 404);
        return response()->json($room);
    }

    public function update(Request $request, $id)
    {
        abort_unless(Auth::user()->can('manage-rooms'), 403, 'Anda tidak memiliki izin.');

        $room      = Room::findOrFail($id);
        $validated = $request->validate([
            'floor_id'    => 'required|exists:floors,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:rooms,code,' . $room->id,
            'color'       => 'nullable|string|max:50',
            'pic_user_id' => 'nullable|exists:users,id',
        ]);

        $room->update($validated);
        return redirect()->back();
    }

    public function destroy($id)
    {
        abort_unless(Auth::user()->can('manage-rooms'), 403, 'Anda tidak memiliki izin.');

        $room = Room::find($id);
        if (!$room) return response()->json(['message' => 'Not found'], 404);
        $room->delete();
        return redirect()->back()->with('message', 'Area ruangan berhasil dihapus!');
    }

    public function getByFloor($floorId)
    {
        $rooms = Room::where('floor_id', $floorId)->get();
        return response()->json($rooms);
    }

    public function updateCoordinates(Request $request)
    {
        $request->validate([
            'room_id'     => 'required|exists:rooms,id',
            'coordinates' => 'required|array|min:3',
        ]);

        $room              = Room::findOrFail($request->room_id);
        $room->coordinates = $request->coordinates;
        $room->save();

        return redirect()->back()->with('message', 'Area ruangan berhasil diperbarui!');
    }
}
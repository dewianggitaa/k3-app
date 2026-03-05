<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BuildingController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('view-master-data') && !Auth::user()->can('manage-buildings')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $query = Building::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('MasterData/Building/Index', [
            'buildings' => $query->latest()->paginate(10)->withQueryString(),
            'filters'   => $request->only(['search']),
            'can'       => [
                'manage' => Auth::user()->can('manage-buildings'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage-buildings'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:buildings,code',
        ]);

        Building::create($validated);

        return redirect()->route('buildings.index')->with('success', 'Building created successfully.');
    }

    public function update(Request $request, Building $building)
    {
        abort_unless(Auth::user()->can('manage-buildings'), 403, 'Anda tidak memiliki izin.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:buildings,code,' . $building->id,
        ]);

        $building->update($validated);

        return redirect()->route('buildings.index')->with('success', 'Building updated successfully.');
    }

    public function destroy(Building $building)
    {
        abort_unless(Auth::user()->can('manage-buildings'), 403, 'Anda tidak memiliki izin.');

        $building->delete();

        return redirect()->route('buildings.index')->with('success', 'Building deleted successfully.');
    }
}
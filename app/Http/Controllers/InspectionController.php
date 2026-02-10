<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class InspectionController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage-inspections')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        $query = Inspection::with(['assetable.room.floor.building', 'schedule']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->building_id) {
            $query->whereHas('schedule', function ($q) use ($request) {
                $q->where('building_id', $request->building_id);
            });
        }

        return Inertia::render('Inspections/Index', [
            'inspections' => $query->latest('schedule_date')->paginate(10)->withQueryString(),
            'buildings'   => Building::all(),
            'filters'     => $request->only(['status', 'building_id']),
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('manage-inspections')) {
            abort(403, 'Anda tidak memiliki izin untuk membuat tugas.');
        }

        $request->validate([
            'schedule_id'    => 'required|exists:schedules,id',
            'assetable_type' => 'required',
            'assetable_id'   => 'required',
            'schedule_date'  => 'required|date',
            'due_date'       => 'required|date|after_or_equal:schedule_date',
        ]);

        Inspection::create([
            'schedule_id'    => $request->schedule_id,
            'assetable_type' => $request->assetable_type,
            'assetable_id'   => $request->assetable_id,
            'status'         => 'pending',
            'schedule_date'  => $request->schedule_date,
            'due_date'       => $request->due_date,
        ]);

        return redirect()->back()->with('success', 'Tugas berhasil dibuat.');
    }

    public function update(Request $request, Inspection $inspection)
    {
        if (!Auth::user()->can('manage-inspections')) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit tugas.');
        }

        $request->validate([
            'status' => 'required|in:pending,completed,issue,overdue',
            'notes'  => 'nullable|string',
        ]);

        $inspection->update([
            'status' => $request->status,
            'notes'  => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('manage-inspections')) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus tugas.');
        }

        Inspection::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Tugas berhasil dihapus.');
    }
}
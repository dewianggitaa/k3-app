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
        // 1. Cek Izin Admin
        if (!Auth::user()->can('manage-inspections')) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        // 2. Query Data dengan Relasi Lengkap
        // Kita butuh data user (PIC), schedule (asal), dan lokasi aset (room->floor->building)
        $query = Inspection::with([
            'user', 
            'schedule', 
            'assetable.room.floor.building'
        ]);

        // 3. Filter Status (Pending/Completed/Issue/Overdue)
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // 4. Filter Gedung (Logic "Memanjat" Relasi)
        if ($request->building_id) {
            // Karena asetnya polymorphic (bisa Apar, Hydrant, P3k), kita pakai whereHasMorph
            // '*' artinya cari di semua tipe model aset
            $query->whereHasMorph('assetable', '*', function ($q) use ($request) {
                // Masuk ke relasi Room -> Floor -> Building
                $q->whereHas('room.floor', function ($f) use ($request) {
                    $f->where('building_id', $request->building_id);
                });
            });
        }

        // 5. Filter Pencarian (Opsional: Cari nama user atau ID aset)
        if ($request->search) {
             $query->where(function($q) use ($request) {
                 $q->where('assetable_id', 'like', '%'.$request->search.'%')
                   ->orWhereHas('user', function($u) use ($request) {
                       $u->where('name', 'like', '%'.$request->search.'%');
                   });
             });
        }

        return Inertia::render('Inspections/Index', [
            'inspections' => $query->latest('schedule_date') // Urutkan dari jadwal terbaru
                                   ->paginate(10)
                                   ->withQueryString(),

            'buildings'   => Building::select('id', 'name')->orderBy('name')->get(),
            
            'filters'     => $request->only(['status', 'building_id', 'search']),
        ]);
    }

    // --- LOGIC TAMBAHAN BUAT ADMIN (Edit Status / Hapus) ---

    public function update(Request $request, Inspection $inspection)
    {
        if (!Auth::user()->can('manage-inspections')) {
            abort(403, 'Anda tidak berhak.');
        }

        $request->validate([
            'status' => 'required|in:pending,completed,issue,overdue',
            'notes'  => 'nullable|string',
        ]);

        $inspection->update([
            'status' => $request->status,
            'report_data' => $request->notes ? ['admin_notes' => $request->notes] : $inspection->report_data,
        ]);

        return redirect()->back()->with('success', 'Status tugas diperbarui.');
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('manage-inspections')) {
            abort(403, 'Anda tidak berhak.');
        }

        $inspection = Inspection::findOrFail($id);
        $inspection->delete();

        return redirect()->back()->with('success', 'Tugas berhasil dihapus.');
    }
}
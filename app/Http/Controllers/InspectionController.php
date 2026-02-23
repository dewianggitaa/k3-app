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

        $query = Inspection::with([
            'user', 
            'schedule', 
            'assetable.room.floor.building'
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->building_id) {
            $query->whereHasMorph('assetable', '*', function ($q) use ($request) {
                $q->whereHas('room.floor', function ($f) use ($request) {
                    $f->where('building_id', $request->building_id);
                });
            });
        }

        if ($request->search) {
             $query->where(function($q) use ($request) {
                 $q->where('assetable_id', 'like', '%'.$request->search.'%')
                   ->orWhereHas('user', function($u) use ($request) {
                       $u->where('name', 'like', '%'.$request->search.'%');
                   });
             });
        }

        return Inertia::render('Inspections/Index', [
            'inspections' => $query
                ->orderByRaw("FIELD(status, 'overdue', 'pending', 'completed') ASC")
                ->orderBy('schedule_date', 'desc') 
                ->paginate(10)
                ->withQueryString(),
            'buildings'   => Building::select('id', 'name')->orderBy('name')->get(),
            
            'filters'     => $request->only(['status', 'building_id', 'search']),
        ]);
    }

    public function openTasks(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->can('execute-inspections') || optional($user->department)->name !== 'K3') {
            abort(403, 'Akses ditolak. Halaman ini khusus untuk Tim K3.');
        }

        $query = Inspection::with([
            'schedule', 
            'assetable.room.floor.building',
            'user' // Load user untuk ditampilkan siapa yang mengerjakan
        ]);
        
        $query->where(function($q) {
            $q->whereNull('user_id')
              
              ->orWhereHas('user.department', function($d) {
                  $d->where('name', 'K3');
              });
        });

        if ($request->search) {
            $query->whereHasMorph('assetable', '*', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->building_id) {
            $query->whereHasMorph('assetable', '*', function ($q) use ($request) {
                $q->whereHas('room.floor', function ($f) use ($request) {
                    $f->where('building_id', $request->building_id);
                });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Inspections/MyTasks', [
            'inspections' => $query
                ->orderByRaw("FIELD(status, 'overdue', 'pending', 'issue', 'completed') ASC")
                ->orderBy('schedule_date', 'asc') // Deadline terdekat duluan
                ->paginate(10)
                ->withQueryString(),
            'pageType'  => 'open', // Di Vue nanti bisa diubah judulnya jadi "K3 Team Tasks"
            'pageTitle' => 'Monitoring Tim K3', // Judul Halaman
            'buildings' => Building::select('id', 'name')->orderBy('name')->get(),
            'filters'   => $request->only(['search', 'building_id', 'status']),
        ]);
    }


    public function myTasks(Request $request)
    {
        if (!Auth::user()->can('execute-inspections')) {
            abort(403, 'Anda tidak memiliki izin untuk melakukan tugas inspeksi.');
        }

        // Logic PIC: Hanya tugas yang dibebankan ke SAYA
        $query = Inspection::with([
            'schedule', 
            'assetable.room.floor.building'
        ])
        ->where('user_id', Auth::id());

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->whereHasMorph('assetable', '*', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->building_id) {
            $query->whereHasMorph('assetable', '*', function ($q) use ($request) {
                $q->whereHas('room.floor', function ($f) use ($request) {
                    $f->where('building_id', $request->building_id);
                });
            });
        }

        return Inertia::render('Inspections/MyTasks', [
            'inspections' => $query
                ->orderByRaw("FIELD(status, 'overdue', 'pending', 'issue', 'completed') ASC")
                ->orderBy('schedule_date', 'asc')
                ->paginate(10)
                ->withQueryString(),
            'pageType'  => 'assigned',
            'pageTitle' => 'Tugas Personal Saya',
            'buildings' => Building::select('id', 'name')->orderBy('name')->get(),
            'filters'   => $request->only(['status', 'search', 'building_id']),
        ]);
    }


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
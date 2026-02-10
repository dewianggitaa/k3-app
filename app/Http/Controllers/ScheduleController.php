<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::with('buildings');

        if ($request->search) {
            $query->where('asset_type', 'like', '%' . $request->search . '%');
        }

        return Inertia::render('Schedules/Index', [
            'schedules' => $query->latest()
                ->paginate(10)
                ->withQueryString(),

            'buildings' => Building::select('id', 'name')->orderBy('name')->get(),
            'filters'   => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_type'      => 'required|string',
            'months_interval' => 'required|integer|min:1',
            'week_rank'       => 'nullable|integer|min:1|max:4',
            'start_date'      => 'required|date',
            'scope'           => 'required|in:global,building',
            'building_ids'    => 'required_if:scope,building|array',
            'building_ids.*'  => 'exists:buildings,id',
            'assign_type'     => 'required|in:k3,pic'
        ]);

        $schedule = Schedule::create([
            'asset_type'      => $request->asset_type,
            'scope'           => $request->scope,
            'months_interval' => $request->months_interval,
            'week_rank'       => $request->week_rank,
            'assign_type'     => $request->assign_type,
            'next_run_date'   => $request->start_date,
        ]);

        if ($request->scope === 'building' && $request->building_ids) {
            $schedule->buildings()->attach($request->building_ids);
        }

        Artisan::call('inspections:generate');

        return redirect()->route('schedules.index')
            ->with('success', "Aturan Jadwal berhasil dibuat! Robot sedang memproses tugas inspeksi...");
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'months_interval' => 'required|integer|min:1',
            'week_rank'       => 'nullable|integer|min:1|max:4',
            'start_date'      => 'required|date',
            'assign_type'     => 'required|in:k3,pic',
            'scope'           => 'required|in:global,building',
            'building_ids'    => 'required_if:scope,building|array',
        ]);

        $schedule->update([
            'months_interval' => $request->months_interval,
            'week_rank'       => $request->week_rank,
            'assign_type'     => $request->assign_type,
            'scope'           => $request->scope,
            'next_run_date'   => $request->start_date, 
        ]);

        if ($request->scope === 'building') {
            $schedule->buildings()->sync($request->building_ids);
        } else {
            $schedule->buildings()->detach();
        }

        Artisan::call('inspections:generate');

        return redirect()->route('schedules.index')
            ->with('success', 'Aturan jadwal diperbarui. Robot telah memproses ulang.');
    }

    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        
        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Aturan jadwal dihapus. Robot berhenti menjadwalkan aset ini.');
    }
}
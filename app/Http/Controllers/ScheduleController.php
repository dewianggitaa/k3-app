<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan;

class ScheduleController extends Controller
{

    public function index()
    {
        return Inertia::render('Schedules/Index', [
            // List of existing schedules (paginated)
            'schedules' => Schedule::with('assetable.room.floor.building')
                ->latest()
                ->paginate(10),

            // List of buildings for the "Checkbox" selection in Frontend
            'buildings' => Building::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_type'      => 'required|string', // e.g., 'App\Models\Apar'
            'months_interval' => 'required|integer|min:1',
            'week_rank'       => 'nullable|integer|min:1|max:4', // 1, 2, 3, 4, or NULL (Free)
            'start_date'      => 'required|date',
            'scope'           => 'required|in:global,building',
            
            'building_ids'    => 'required_if:scope,building|array',
            'building_ids.*'  => 'exists:buildings,id',
        ]);

        $modelClass = $request->asset_type;
        $assetsToSchedule = collect();

        if ($request->scope === 'global') {
            $assetsToSchedule = $modelClass::all();
        } 
        elseif ($request->scope === 'building') {
            $assetsToSchedule = $modelClass::whereHas('room.floor', function ($query) use ($request) {
                $query->whereIn('building_id', $request->building_ids);
            })->get();
        }

        $countSuccess = 0;
        $countSkipped = 0;

        foreach ($assetsToSchedule as $asset) {
            
            $exists = Schedule::where('assetable_type', $request->asset_type)
                              ->where('assetable_id', $asset->id)
                              ->exists();

            if (!$exists) {

                $buildingId = $asset->room->floor->building_id ?? null;

                Schedule::create([
                    'assetable_type'  => $request->asset_type,
                    'assetable_id'    => $asset->id,
                    'building_id'     => $buildingId, // Save for easier filtering later
                    'months_interval' => $request->months_interval,
                    'week_rank'       => $request->week_rank, // Can be null
                    'next_run_date'   => $request->start_date,
                ]);

                $countSuccess++;
            } else {
                $countSkipped++;
            }
        }

        $message = "Berhasil membuat $countSuccess jadwal baru.";
        if ($countSkipped > 0) {
            $message .= " ($countSkipped aset dilewati karena sudah punya jadwal).";
        }

        Artisan::call('inspections:generate');

        return redirect()->route('schedules.index')
        ->with('success', "Jadwal berhasil dibuat & Robot sudah memproses tugas inspeksi!");
    }

    public function update(Request $request, $id)
    {
        // 1. Cari Jadwal yang mau diedit
        $schedule = Schedule::findOrFail($id);

        // 2. Validasi Input
        // Kita tidak validasi 'scope' atau 'asset_type' karena target aset tidak boleh berubah saat edit.
        $request->validate([
            'months_interval' => 'required|integer|min:1',
            'week_rank'       => 'nullable|integer|min:1|max:4', // 1-4 atau Null
            'start_date'      => 'required|date', // Di form frontend namanya 'start_date'
        ]);

        // 3. Update Database
        $schedule->update([
            'months_interval' => $request->months_interval,
            'week_rank'       => $request->week_rank,
            
            // Kita update next_run_date sesuai input baru user.
            // Misal: Awalnya 1 Maret, user mau majuin jadi 9 Feb (Hari ini).
            'next_run_date'   => $request->start_date, 
        ]);

        // 4. Panggil Robot (PENTING!)
        // Kenapa? Karena jika user mengubah tanggal 'Next Run' menjadi HARI INI,
        // robot harus langsung membuat tugas inspeksinya sekarang juga.
        Artisan::call('inspections:generate');

        return redirect()->route('schedules.index')
            ->with('success', 'Aturan jadwal berhasil diperbarui. Robot telah memproses ulang.');
    }

    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Jadwal berhasil dihapus. Robot tidak akan menjadwalkan aset ini lagi.');
    }
}
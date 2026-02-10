<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use App\Models\Inspection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateInspections extends Command
{
    protected $signature = 'inspections:generate';
    protected $description = 'Generate tugas inspeksi berdasarkan Aturan Jadwal (Rule-Based)';

    public function handle()
    {
        $timezone = 'Asia/Jakarta';
        $today = Carbon::now($timezone)->startOfDay();

        $this->info("Robot mulai bekerja: " . Carbon::now($timezone)->toDateTimeString());

        $schedules = Schedule::with('buildings')
            ->whereDate('next_run_date', '<=', $today)
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('Tidak ada jadwal yang perlu diproses hari ini.');
            return;
        }

        foreach ($schedules as $schedule) {
            DB::beginTransaction();
            try {
                $modelClass = $schedule->asset_type;
                
                if (!class_exists($modelClass)) {
                    throw new \Exception("Model aset tidak ditemukan: $modelClass");
                }

                $query = $modelClass::with('room');

                if ($schedule->scope === 'building') {
                    $buildingIds = $schedule->buildings->pluck('id')->toArray();
                    
                    if (!empty($buildingIds)) {
                        $query->whereHas('room.floor', function($q) use ($buildingIds) {
                            $q->whereIn('building_id', $buildingIds);
                        });
                    }
                }

                $assets = $query->get();
                $countCreated = 0;

                foreach ($assets as $asset) {
                    
                    $targetUserId = null;

                    if ($schedule->assign_type === 'pic') {
                        // Cek apakah aset punya ruangan & ruangan punya PIC
                        if ($asset->room && $asset->room->pic_user_id) {
                            $targetUserId = $asset->room->pic_user_id;
                        } 
                    }

                    // --- LOGIC TANGGAL ---
                    $baseMonth = Carbon::parse($schedule->next_run_date, $timezone)->startOfMonth();
                    $endOfInterval = $baseMonth->copy()->addMonths($schedule->months_interval - 1)->endOfMonth();

                    if (is_null($schedule->week_rank)) {
                        $startDate = $baseMonth->copy();
                        $dueDate   = $endOfInterval;
                    } else {
                        $dayMap = [
                            1 => [1, 7],
                            2 => [8, 14],
                            3 => [15, 21],
                            4 => [22, $baseMonth->copy()->endOfMonth()->day]
                        ];
                        $range = $dayMap[$schedule->week_rank] ?? [1, 7];
                        $startDate = $baseMonth->copy()->day($range[0]);
                        $dueDate   = $baseMonth->copy()->day($range[1]);
                    }

                    // --- BUAT INSPEKSI ---
                    // Cek duplikasi dulu biar gak double entry di bulan yang sama
                    $exists = Inspection::where('schedule_id', $schedule->id)
                        ->where('assetable_type', $schedule->asset_type)
                        ->where('assetable_id', $asset->id)
                        ->whereMonth('schedule_date', $startDate->month)
                        ->whereYear('schedule_date', $startDate->year)
                        ->exists();

                    if (!$exists) {
                        Inspection::create([
                            'schedule_id'    => $schedule->id,
                            'assetable_type' => $schedule->asset_type,
                            'assetable_id'   => $asset->id,
                            'status'         => 'pending',
                            'schedule_date'  => $startDate,
                            'due_date'       => $dueDate,
                            // Masukkan ID PIC (atau NULL)
                            'user_id'        => $targetUserId, 
                        ]);
                        $countCreated++;
                    }
                }

                // 4. UPDATE JADWAL UTAMA
                $nextRun = Carbon::parse($schedule->next_run_date, $timezone)
                            ->addMonthsNoOverflow($schedule->months_interval);

                $schedule->update([
                    'last_run_at'   => Carbon::now($timezone),
                    'next_run_date' => $nextRun
                ]);

                DB::commit();
                $this->info("Jadwal ID {$schedule->id}: Berhasil generate $countCreated tugas. Next: {$nextRun->toDateString()}");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Gagal pada Jadwal ID {$schedule->id}: " . $e->getMessage());
            }
        }
        
        $this->info('Selesai.');
    }
}
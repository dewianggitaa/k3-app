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
    protected $description = 'Generate tugas inspeksi rutin berdasarkan jadwal bulanan';

    public function handle()
    {
        $today = now()->startOfDay();

        $schedules = Schedule::whereDate('next_run_date', '<=', $today)->get();

        if ($schedules->isEmpty()) {
            $this->info('Tidak ada jadwal yang perlu diproses hari ini.');
            return;
        }

        $this->info("Menemukan {$schedules->count()} jadwal yang harus diproses...");

        foreach ($schedules as $schedule) {
            
            DB::beginTransaction();

            try {
                $baseMonth = Carbon::parse($schedule->next_run_date)->startOfMonth();
                
                if (is_null($schedule->week_rank)) {
                    $startDate = $baseMonth->copy();
                    $dueDate   = $baseMonth->copy()->endOfMonth();
                } 
                else {                    
                    if ($schedule->week_rank == 1) {
                        // Minggu 1 (Tgl 1-7)
                        $startDate = $baseMonth->copy()->day(1);
                        $dueDate   = $baseMonth->copy()->day(7);
                    }
                    elseif ($schedule->week_rank == 2) {
                        // Minggu 2 (Tgl 8-14)
                        $startDate = $baseMonth->copy()->day(8);
                        $dueDate   = $baseMonth->copy()->day(14);
                    }
                    elseif ($schedule->week_rank == 3) {
                        // Minggu 3 (Tgl 15-21)
                        $startDate = $baseMonth->copy()->day(15);
                        $dueDate   = $baseMonth->copy()->day(21);
                    }
                    else { 
                        // Minggu 4 (Tgl 22 - Akhir Bulan)
                        $startDate = $baseMonth->copy()->day(22);
                        $dueDate   = $baseMonth->copy()->endOfMonth();
                    }
                }

                Inspection::create([
                    'schedule_id'    => $schedule->id,
                    'assetable_type' => $schedule->assetable_type,
                    'assetable_id'   => $schedule->assetable_id,
                    'status'         => 'pending',
                    'schedule_date'  => $startDate,
                    'due_date'       => $dueDate,
                    'completed_by'   => null,
                    'report_data'    => null,
                ]);

                $nextRun = $baseMonth->copy()->addMonths($schedule->months_interval);

                $schedule->update([
                    'last_run_at'   => now(),
                    'next_run_date' => $nextRun
                ]);

                DB::commit(); // Simpan permanen
                $this->info("Sukses: Jadwal ID {$schedule->id} ({$schedule->assetable_type}) -> Next: {$nextRun->toDateString()}");

            } catch (\Exception $e) {
                DB::rollBack(); // Batalkan kalau ada error
                $this->error("Gagal memproses Jadwal ID {$schedule->id}: " . $e->getMessage());
            }
        }

        $this->info('Selesai! Semua tugas berhasil digenerate.');
    }
}
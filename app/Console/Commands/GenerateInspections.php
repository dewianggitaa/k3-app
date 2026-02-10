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
    protected $description = 'Generate tugas inspeksi rutin dengan dukungan interval multi-bulan';

    public function handle()
    {
        // Paksa timezone ke Jakarta agar tidak ikut jam UTC Docker
        $timezone = 'Asia/Jakarta';
        $today = Carbon::now($timezone)->startOfDay();

        $this->info("Robot jalan pada: " . Carbon::now($timezone)->toDateTimeString());

        // Cari jadwal yang sudah jatuh tempo
        $schedules = Schedule::whereDate('next_run_date', '<=', $today)->get();

        if ($schedules->isEmpty()) {
            $this->info('Tidak ada jadwal yang perlu diproses hari ini.');
            return;
        }

        foreach ($schedules as $schedule) {
            DB::beginTransaction();
            try {
                // Gunakan timezone Jakarta saat parsing tanggal dari DB
                $baseMonth = Carbon::parse($schedule->next_run_date, $timezone)->startOfMonth();
                
                // LOGIKA DUE DATE: Akhir bulan sesuai interval (Jika 2 bulan, maka akhir bulan ke-2)
                $endOfInterval = $baseMonth->copy()
                    ->addMonths($schedule->months_interval - 1)
                    ->endOfMonth();

                if (is_null($schedule->week_rank)) {
                    // JADWAL BEBAS: Dari tanggal 1 sampai akhir interval
                    $startDate = $baseMonth->copy();
                    $dueDate   = $endOfInterval;
                } else {                    
                    // JADWAL PER MINGGU: Start/End mengikuti range minggu di bulan pertama
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

                // Create Inspection
                Inspection::create([
                    'schedule_id'    => $schedule->id,
                    'assetable_type' => $schedule->assetable_type,
                    'assetable_id'   => $schedule->assetable_id,
                    'status'         => 'pending',
                    'schedule_date'  => $startDate,
                    'due_date'       => $dueDate,
                ]);

                // Update Schedule untuk periode berikutnya
                // Pakai addMonthsNoOverflow agar tgl 31 tidak loncat ke Maret jika tujuannya Februari
                $nextRun = $baseMonth->copy()->addMonthsNoOverflow($schedule->months_interval);

                $schedule->update([
                    'last_run_at'   => Carbon::now($timezone), // Catat jam Jakarta (08:xx)
                    'next_run_date' => $nextRun
                ]);

                DB::commit();
                $this->info("Sukses: ID {$schedule->id} -> Next: {$nextRun->toDateString()}");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Gagal pada ID {$schedule->id}: " . $e->getMessage());
            }
        }

        $this->info('Semua tugas berhasil diproses.');
    }
}
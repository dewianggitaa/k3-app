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
    protected $description = 'Generate tugas inspeksi rutin dengan dukungan assign_type (K3/PIC)';

    public function handle()
    {
        // Paksa timezone ke Jakarta agar tidak ikut jam UTC Docker
        $timezone = 'Asia/Jakarta';
        $today = Carbon::now($timezone)->startOfDay();

        $this->info("Robot jalan pada: " . Carbon::now($timezone)->toDateTimeString());

        // Cari jadwal yang sudah jatuh tempo
        // UPDATE: Tambahkan 'with' untuk mengambil relasi asset dan room sekaligus (Optimasi Query)
        $schedules = Schedule::with(['asset.room'])
            ->whereDate('next_run_date', '<=', $today)
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('Tidak ada jadwal yang perlu diproses hari ini.');
            return;
        }

        foreach ($schedules as $schedule) {
            DB::beginTransaction();
            try {
                // Gunakan timezone Jakarta saat parsing tanggal dari DB
                $baseMonth = Carbon::parse($schedule->next_run_date, $timezone)->startOfMonth();
                
                // LOGIKA DUE DATE: Akhir bulan sesuai interval
                $endOfInterval = $baseMonth->copy()
                    ->addMonths($schedule->months_interval - 1)
                    ->endOfMonth();

                if (is_null($schedule->week_rank)) {
                    // JADWAL BEBAS
                    $startDate = $baseMonth->copy();
                    $dueDate   = $endOfInterval;
                } else {                    
                    // JADWAL PER MINGGU
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

                // ============================================================
                // LOGIC BARU: MENENTUKAN SIAPA YANG MENGERJAKAN (USER_ID)
                // ============================================================
                $assignedUserId = null; // Default NULL (Untuk tipe 'k3')

                if ($schedule->assign_type === 'pic') {
                    // Cek apakah aset punya ruangan, dan ruangan punya PIC
                    // Asumsi: Relasi di model Schedule adalah belongsTo Asset ('asset')
                    $room = $schedule->asset->room ?? null;

                    if ($room && $room->pic_area_id) {
                        $assignedUserId = $room->pic_area_id;
                        $this->comment(" -> Assign ke PIC: ID $assignedUserId");
                    } else {
                        // Jika tipe PIC tapi datanya kosong, beri peringatan di log
                        $this->warn(" -> Warning: Jadwal ID {$schedule->id} tipe PIC, tapi ruangan tidak punya PIC. Masuk ke Pool K3.");
                    }
                }
                // ============================================================

                Inspection::create([
                    'schedule_id'    => $schedule->id,
                    'assetable_type' => $schedule->assetable_type,
                    'assetable_id'   => $schedule->assetable_id,
                    'status'         => 'pending',
                    'schedule_date'  => $startDate,
                    'due_date'       => $dueDate,
                    // Masukkan hasil logic di atas
                    'user_id'        => $assignedUserId, 
                ]);

                // Hitung jadwal berikutnya
                $nextRun = $baseMonth->copy()->addMonthsNoOverflow($schedule->months_interval);

                $schedule->update([
                    'last_run_at'   => Carbon::now($timezone),
                    'next_run_date' => $nextRun
                ]);

                DB::commit();
                $this->info("Sukses: ID {$schedule->id} ({$schedule->assign_type}) -> Next: {$nextRun->toDateString()}");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Gagal pada ID {$schedule->id}: " . $e->getMessage());
            }
        }

        $this->info('Semua tugas berhasil diproses.');
    }
}
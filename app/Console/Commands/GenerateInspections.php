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
    protected $description = 'Cek tugas overdue & Generate tugas inspeksi baru (Rule-Based)';

    public function handle()
    {
        $timezone = 'Asia/Jakarta';
        $now = Carbon::now($timezone);
        $today = $now->copy()->startOfDay();

        $this->info("=== ROBOT INSPEKSI MULAI BEKERJA: " . $now->toDateTimeString() . " ===");

        // ==========================================
        // TAHAP 1: CEK TUGAS TERLAMBAT (OVERDUE)
        // ==========================================
        $this->info("1. Memeriksa tugas yang melewati deadline...");

        // Logic: Ubah status jadi 'overdue' jika status masih 'pending' DAN due_date < hari ini
        $affectedRows = Inspection::where('status', 'pending')
            ->whereDate('due_date', '<', $today)
            ->update(['status' => 'overdue']);

        if ($affectedRows > 0) {
            $this->warn("   -> Ditemukan $affectedRows tugas telat. Status diubah menjadi 'Overdue'.");
        } else {
            $this->info("   -> Aman. Tidak ada tugas yang terlambat hari ini.");
        }

        $this->newLine(); // Jarak baris biar rapi

        // ==========================================
        // TAHAP 2: GENERATE TUGAS BARU DARI JADWAL
        // ==========================================
        $this->info("2. Memproses jadwal rutin...");

        $schedules = Schedule::with('buildings')
            ->whereDate('next_run_date', '<=', $today)
            ->get();

        if ($schedules->isEmpty()) {
            $this->info("   -> Tidak ada jadwal yang perlu diproses hari ini.");
            $this->info("=== SELESAI ===");
            return;
        }

        foreach ($schedules as $schedule) {
            DB::beginTransaction();
            try {
                // --- Validasi Model ---
                $modelClass = $schedule->asset_type;
                if (!class_exists($modelClass)) {
                    throw new \Exception("Model aset tidak ditemukan: $modelClass");
                }

                // --- Query Aset ---
                $query = $modelClass::with('room');

                // --- Filter Scope (Global / Building) ---
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

                // --- Loop Setiap Aset ---
                foreach ($assets as $asset) {
                    
                    // Cek Penugasan (PIC vs Tim K3)
                    $targetUserId = null;
                    if ($schedule->assign_type === 'pic') {
                        if ($asset->room && $asset->room->pic_user_id) {
                            $targetUserId = $asset->room->pic_user_id;
                        } 
                    }

                    // Hitung Tanggal (Start & Due Date)
                    $baseMonth = Carbon::parse($schedule->next_run_date, $timezone)->startOfMonth();
                    // Due date default akhir bulan interval
                    $endOfInterval = $baseMonth->copy()->addMonths($schedule->months_interval - 1)->endOfMonth();

                    if (is_null($schedule->week_rank)) {
                        // Bebas / Awal Bulan
                        $startDate = $baseMonth->copy();
                        $dueDate   = $endOfInterval;
                    } else {
                        // Minggu Tertentu
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

                    // Cek Duplikasi (Supaya tidak double)
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
                            'user_id'        => $targetUserId, 
                        ]);
                        $countCreated++;
                    }
                }

                // Update Jadwal Induk (Next Run Date)
                $nextRun = Carbon::parse($schedule->next_run_date, $timezone)
                            ->addMonthsNoOverflow($schedule->months_interval);

                $schedule->update([
                    'last_run_at'   => $now,
                    'next_run_date' => $nextRun
                ]);

                DB::commit();
                $this->info("   -> Jadwal ID {$schedule->id}: Berhasil generate $countCreated tugas. Next: {$nextRun->toDateString()}");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("   -> Gagal pada Jadwal ID {$schedule->id}: " . $e->getMessage());
            }
        }
        
        $this->info("=== SELESAI ===");
    }
}
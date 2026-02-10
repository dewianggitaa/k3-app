<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel Pivot (Jembatan antara Jadwal & Gedung)
        Schema::create('building_schedule', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel schedules (Kalau jadwal dihapus, data ini ikut hilang)
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            
            // Relasi ke tabel buildings (Kalau gedung dihapus, data ini ikut hilang)
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_schedule');
    }
};
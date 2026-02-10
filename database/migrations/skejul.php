<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('asset_type');
            $table->enum('scope', ['global', 'building'])->default('global');
            $table->integer('months_interval')->default(1); 
            $table->tinyInteger('week_rank')->nullable();
            $table->enum('assign_type', ['k3', 'pic'])->default('k3');
            $table->date('next_run_date'); 
            $table->dateTime('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('building_schedule', function (Blueprint $table) {
        $table->id();
        $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
        $table->foreignId('building_id')->constrained()->cascadeOnDelete();
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('schedule_id')
                ->constrained('schedules')
                ->onDelete('cascade'); 

            $table->morphs('assetable'); 
            $table->enum('status', ['pending', 'completed', 'overdue'])->default('pending');
            $table->date('schedule_date');
            $table->date('due_date');
            $table->dateTime('completed_at')->nullable(); 
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->json('report_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};

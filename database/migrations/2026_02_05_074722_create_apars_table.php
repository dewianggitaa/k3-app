<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apars', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->foreignId("apar_type_id")->constrained("apar_types");
            $table->foreignId('room_id')->constrained('rooms');
            $table->enum('status', ['safe', 'warning', 'critical'])->default('safe');
            $table->integer('weight');
            $table->date('last_refilled_at')->nullable();
            $table->date('expired_at');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apars');
    }
};

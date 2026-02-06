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
        Schema::create('hydrants', function (Blueprint $table) {
            $table->id();
            $table->string("code");
            $table->foreignId("hydrant_type_id")->constrained('hydrant_types');
            $table->foreignId('room_id')->constrained('rooms');
            $table->enum('status', ['safe', 'warning', 'critical'])->default('safe');
            $table->json('location_data')->nullable();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hydrants');
    }
};

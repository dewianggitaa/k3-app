<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('p3ks', function (Blueprint $table) {
            $table->id();
            $table->string("code")->unique();
            $table->foreignId("p3k_type_id")->constrained('p3k_types');
            $table->foreignId('room_id')->constrained('floors');
            $table->json('location_data')->nullable();
            $table->timestamps();
            $table->enum('status', ['safe', 'warning', 'critical'])->default('safe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p3ks');
    }
};

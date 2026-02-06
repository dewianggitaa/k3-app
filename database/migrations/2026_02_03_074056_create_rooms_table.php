<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('floor_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('name');
            $table->string('code')->unique();
            
            $table->json('coordinates')->nullable();
            $table->string('color')->nullable();
            
            $table->foreignId('pic_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
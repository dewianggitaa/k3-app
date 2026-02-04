<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('building_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->string('name');
            
            $table->string('map_image_path')->nullable();
            $table->integer('map_width')->nullable();
            $table->integer('map_height')->nullable();
            
            $table->foreignId('pic_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floors');
    }
};
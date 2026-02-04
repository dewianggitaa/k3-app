<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('p3k_items', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->enum('type', ['consumable', 'multi_use'])->default('consumable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p3k_items');
    }
};

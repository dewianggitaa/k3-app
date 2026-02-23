<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p3k_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p3k_id')->constrained('p3ks')->cascadeOnDelete();
            $table->foreignId('p3k_item_id')->constrained('p3k_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_name'); 
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->integer('qty');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p3k_usages');
    }
};
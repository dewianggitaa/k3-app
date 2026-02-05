<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('p3k_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p3k_id')->constrained('p3ks')->onDelete('cascade');
            $table->foreignId('p3k_item_id')->constrained('p3k_items')->onDelete('cascade');
            $table->integer('current_qty')->default(0);
            $table->timestamps();
            $table->unique(['p3k_id', 'p3k_item_id']);
        });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p3k_inventories');
    }
};

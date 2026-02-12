<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('p3k_type_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId("p3k_type_id")->constrained("p3k_types");
            $table->foreignId("p3k_item_id")->constrained("p3k_items");
            $table->integer("quantity");
            $table->integer("standard")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p3k_type_items');
    }
};

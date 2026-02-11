<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('checklist_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('asset_type')->index(); 
            $table->string('label');      
            $table->enum('input_type', [
                'boolean',
                'radio',
                'select',
                'text',
                'number',
                'textarea'
            ]);; 
            $table->json('options')->nullable();  
            $table->string('standard_value')->nullable();
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('checklist_parameters');
    }
};
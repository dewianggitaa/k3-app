<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_document_versions', function (Blueprint $table) {
            $table->id();
            $table->enum('asset_type', ['p3k', 'apar', 'hydrant']);
            $table->string('document_code');
            $table->string('attachment_number')->nullable();
            $table->string('title');
            $table->date('effective_date')->nullable();
            $table->integer('revision_number')->default(0);
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['asset_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_document_versions');
    }
};

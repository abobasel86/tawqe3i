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
        Schema::create('document_folder', function (Blueprint $table) {
    $table->primary(['document_id', 'folder_id']);
    $table->foreignId('document_id')->constrained()->onDelete('cascade');
    $table->foreignId('folder_id')->constrained()->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_folder');
    }
};

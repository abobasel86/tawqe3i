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
    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // لربط المستند بصاحبه
        $table->string('name'); // اسم المستند
        $table->string('original_file_path'); // مسار الملف المخزن
        $table->string('status')->default('draft'); // حالة المستند
        $table->timestamps(); // تاريخ الإنشاء والتعديل
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

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
    Schema::create('document_participants', function (Blueprint $table) {
        $table->id();
        $table->foreignId('document_id')->constrained()->onDelete('cascade');
        $table->string('name');
        $table->string('email');
        $table->string('token')->unique(); // رابط فريد وآمن لكل موقّع
        $table->string('status')->default('pending'); // (pending, sent, signed)
        $table->integer('signing_order')->nullable(); // لترتيب التوقيع التسلسلي
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_participants');
    }
};

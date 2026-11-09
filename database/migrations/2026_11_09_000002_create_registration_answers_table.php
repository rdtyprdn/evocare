<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jawaban pendaftar. Label dan tipe disalin (snapshot) agar jawaban lama tetap utuh
        // walaupun pertanyaannya kemudian diubah atau dihapus penyelenggara.
        Schema::create('registration_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_question_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 150);
            $table->string('type', 20);
            $table->text('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_answers');
    }
};

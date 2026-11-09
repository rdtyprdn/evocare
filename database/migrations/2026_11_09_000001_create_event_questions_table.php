<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pertanyaan pendaftaran khusus per kegiatan, bisa dibatasi per peran (relawan, donatur, peserta).
        Schema::create('event_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('label', 150);
            $table->string('type', 20)->default('text'); // text, textarea, number, select, date, boolean
            $table->json('options')->nullable(); // daftar pilihan untuk tipe select
            $table->boolean('required')->default(false);
            $table->json('roles'); // peran yang wajib mengisi, mis. ["relawan","peserta"]
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_questions');
    }
};

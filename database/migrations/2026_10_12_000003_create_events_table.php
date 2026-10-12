<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // pembuat (organizer)
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('city')->index();
            $table->string('location');
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at');
            $table->unsignedInteger('quota');
            $table->string('image_url')->nullable();
            $table->string('status')->default('published')->index(); // draft, published, cancelled
            $table->timestamps();
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('relawan'); // relawan, donatur, peserta
            $table->string('status')->default('confirmed'); // confirmed, cancelled
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('events');
    }
};

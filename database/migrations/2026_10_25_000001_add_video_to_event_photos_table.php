<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_photos', function (Blueprint $table) {
            $table->string('type', 10)->default('image')->after('event_id');
            $table->string('youtube_id', 20)->nullable()->after('path');
            $table->string('path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('event_photos', function (Blueprint $table) {
            $table->dropColumn(['type', 'youtube_id']);
        });
    }
};

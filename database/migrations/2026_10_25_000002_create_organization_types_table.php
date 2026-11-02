<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organization_types')) {
            Schema::create('organization_types', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 80)->unique();
                $table->string('name', 80);
                $table->timestamps();
            });
        }

        $now = now();
        DB::table('organization_types')->insertOrIgnore([
            ['slug' => 'lsm', 'name' => 'Lembaga Swadaya Masyarakat', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'komunitas', 'name' => 'Komunitas', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'institusi', 'name' => 'Institusi Pendidikan', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_types');
    }
};

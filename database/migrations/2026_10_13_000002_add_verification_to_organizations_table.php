<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive: menambah kepemilikan & verifikasi organisasi.
 * Organisasi yang sudah ada (data demo) ditandai terverifikasi dan pemiliknya diambil
 * dari user yang terhubung. Tidak ada kegiatan yang dipindahkan ke organisasi lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending')->index()->after('city'); // pending, verified, rejected
            $table->text('admin_note')->nullable()->after('status');
            $table->timestamp('verified_at')->nullable()->after('admin_note');
            $table->string('logo_path')->nullable()->after('description');
            $table->string('contact')->nullable()->after('logo_path');
        });

        Schema::table('events', function (Blueprint $table) {
            // Kegiatan boleh sementara tanpa organisasi (menunggu organisasi pembuatnya terverifikasi).
            $table->unsignedBigInteger('organization_id')->nullable()->change();
        });

        foreach (DB::table('organizations')->get() as $org) {
            $owner = DB::table('users')->where('organization_id', $org->id)->orderBy('id')->value('id');
            DB::table('organizations')->where('id', $org->id)->update([
                'status' => 'verified', 'verified_at' => now(), 'owner_id' => $owner,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['status', 'admin_note', 'verified_at', 'logo_path', 'contact']);
        });
    }
};

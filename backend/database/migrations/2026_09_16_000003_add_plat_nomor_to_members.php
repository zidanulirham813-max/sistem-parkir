<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('plat_nomor', 20)->nullable()->after('nama_perusahaan');
        });

        // Isi plat_nomor untuk data lama yang belum ada: pakai kode_member sementara biar unique, akan diminta edit manual
        // Tidak overwrite yang sudah ada
        $members = DB::table('members')->whereNull('plat_nomor')->get(['id', 'kode_member']);
        foreach ($members as $m) {
            DB::table('members')->where('id', $m->id)->update(['plat_nomor' => $m->kode_member]);
        }

        // Index untuk pencarian cepat + cegah duplikat plat (nullable tetap boleh banyak null sebelum diisi)
        try {
            Schema::table('members', function (Blueprint $table) {
                $table->unique('plat_nomor', 'members_plat_unique');
            });
        } catch (\Throwable $_) {}
    }

    public function down(): void
    {
        try { Schema::table('members', fn(Blueprint $t) => $t->dropUnique('members_plat_unique')); } catch (\Throwable $_) {}
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('plat_nomor');
        });
    }
};

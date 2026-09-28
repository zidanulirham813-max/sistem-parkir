<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // kode_tiket unique bikin scan member kedua kali selalu error (MBR-BIKSIF duplikat).
        // Member yang sudah keluar & masuk lagi butuh baris baru dengan kode yang sama,
        // jadi unique harus dilepas. Cukup pakai index biasa.
        try {
            Schema::table('tiket_parkir', function (Blueprint $table) {
                $table->dropUnique('tiket_parkir_kode_tiket_unique');
            });
        } catch (\Throwable $e) {
            // fallback kalau nama index beda (sqlite/mysql variant)
            try { DB::statement('DROP INDEX tiket_parkir_kode_tiket_unique ON tiket_parkir'); } catch (\Throwable $_) {}
            try { DB::statement('DROP INDEX `tiket_parkir_kode_tiket_unique` ON `tiket_parkir`'); } catch (\Throwable $_) {}
        }

        // pastikan ada index biasa biar pencarian tetap cepat, gagal tidak apa-apa jika sudah ada
        try {
            Schema::table('tiket_parkir', function (Blueprint $table) {
                $table->index('kode_tiket', 'tiket_kode_idx');
            });
        } catch (\Throwable $_) {}

        try {
            Schema::table('tiket_parkir', function (Blueprint $table) {
                $table->index('plat_nomor', 'tiket_plat_idx');
            });
        } catch (\Throwable $_) {}
    }

    public function down(): void
    {
        try { Schema::table('tiket_parkir', fn(Blueprint $t) => $t->dropIndex('tiket_kode_idx')); } catch (\Throwable $_) {}
        try { Schema::table('tiket_parkir', fn(Blueprint $t) => $t->dropIndex('tiket_plat_idx')); } catch (\Throwable $_) {}
        // jangan kembalikan unique — historis sudah boleh duplikat
    }
};

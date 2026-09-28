<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rapikan data lama dulu: kosong string → null (biar tidak bentrok di unique index)
        DB::table('users')->where('no_tlp', '')->update(['no_tlp' => null]);

        // Normalisasi existing no_tlp ke format 08... (tetap 08 sesuai request)
        $users = DB::table('users')->whereNotNull('no_tlp')->get(['id', 'no_tlp']);
        foreach ($users as $u) {
            $raw = trim((string) $u->no_tlp);
            if ($raw === '') continue;
            $p = preg_replace('/[^0-9+]/', '', $raw);
            if (str_starts_with($p, '+')) $p = substr($p, 1);
            if (str_starts_with($p, '62')) {
                $p = '0' . substr($p, 2);
            } elseif (!str_starts_with($p, '0')) {
                $p = '0' . $p;
            }
            if ($p !== $u->no_tlp) {
                DB::table('users')->where('id', $u->id)->update(['no_tlp' => $p]);
            }
        }

        // Cek masih ada duplikat setelah normalisasi — kalau ada, fail dengan pesan yang jelas
        $dups = DB::select("SELECT no_tlp, COUNT(*) as c FROM users WHERE no_tlp IS NOT NULL GROUP BY no_tlp HAVING c > 1");
        if (!empty($dups)) {
            $list = implode(', ', array_map(fn($r) => $r->no_tlp . " ({$r->c}x)", $dups));
            throw new \RuntimeException("Masih ada no_tlp duplikat setelah normalisasi: $list. Perbaiki manual sebelum migrate (ganti/ kosongkan salah satunya).");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('no_tlp', 'users_no_tlp_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_no_tlp_unique');
        });
    }
};

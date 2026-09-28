<?php

namespace App\Http\Controllers;

use App\Models\TiketParkir;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ScanController extends Controller
{
    private function tarifPerJam(string $jenis): int
    {
        return match (strtolower($jenis)) {
            'mobil' => 5000,
            default => 3000,
        };
    }

    public function scan(Request $request)
    {
        $request->validate([
            'kode' => 'nullable|string',
            'kode_member' => 'nullable|string',
            'jenis' => 'nullable|string',
            'kendaraan' => 'nullable|string',
            'plat_nomor' => 'nullable|string',
        ]);

        $kodeMember = $request->input('kode_member');
        $isMemberMasuk = false;
        $prefix = 'TKT-';

        if ($kodeMember) {
            $member = Member::where('kode_member', $kodeMember)->first();
            if (!$member) {
                return response()->json(['success' => false, 'message' => 'Kode member tidak ditemukan'], 404);
            }
            if ($member->tanggal_expired && now()->gt(Carbon::parse($member->tanggal_expired))) {
                return response()->json(['success' => false, 'message' => 'Masa aktif member sudah kedaluwarsa'], 400);
            }
            $isMemberMasuk = true;
            $prefix = 'MBR-';
        }

        $kodeBaru = $prefix . strtoupper(Str::random(6));

        $tiket = TiketParkir::create([
            'kode_tiket'   => $kodeBaru,
            'qr_code'      => Str::random(60),
            'status'       => 'Aktif/Parkir',
            'waktu_masuk'  => now(),
            'waktu_keluar' => null,
            'jenis'        => $request->input('jenis', 'motor'),
            'kendaraan'    => $request->input('kendaraan', 'Kendaraan'),
            'plat_nomor'   => $request->input('plat_nomor', '-'),
            'tarif'        => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => $isMemberMasuk ? 'Member berhasil masuk' : 'Kendaraan berhasil masuk',
            'data' => $tiket,
        ]);
    }

    public function cekKeluar(Request $request)
    {
        $request->validate(['kode' => 'required|string']);

        $tiket = TiketParkir::where('kode_tiket', $request->input('kode'))
            ->where('status', '!=', 'Selesai')
            ->first();

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket atau member tidak ditemukan / sudah keluar.'
            ], 404);
        }

        $isMember = str_starts_with($tiket->kode_tiket, 'MBR-');
        $waktuMasuk = $tiket->waktu_masuk ? Carbon::parse($tiket->waktu_masuk) : now();
        $durasiJam = max(1, ceil($waktuMasuk->diffInHours(now())));
        $tarifPerJam = $this->tarifPerJam($tiket->jenis ?? 'motor');
        $totalTarif = $isMember ? 0 : ($durasiJam * $tarifPerJam);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tiket->id,
                'kode_tiket' => $tiket->kode_tiket,
                'plat_nomor' => $tiket->plat_nomor,
                'jenis_kendaraan' => $tiket->jenis,
                'is_member' => $isMember,
                'durasi_jam' => $durasiJam,
                'tarif_per_jam' => $tarifPerJam,
                'total_biaya' => $totalTarif,
            ]
        ]);
    }

    public function bayarKeluar(Request $request)
    {
        $request->validate([
            'kode' => 'required|string',
            'plat_nomor' => 'nullable|string|max:20',
        ]);

        $tiket = TiketParkir::where('kode_tiket', $request->input('kode'))
            ->where('status', '!=', 'Selesai')
            ->first();

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak valid atau sudah selesai diproses.'
            ], 404);
        }

        $isMember = str_starts_with($tiket->kode_tiket, 'MBR-');

        // Plat WAJIB untuk semua kendaraan (reguler maupun member) — tapi untuk member
        // yang sudah punya plat di tiket/member, boleh kosong (pakai yang tersimpan)
        $platInput = strtoupper(trim((string) $request->input('plat_nomor', '')));
        $platTersimpan = strtoupper(trim((string) ($tiket->plat_nomor ?? '')));
        $isPlatTersimpanValid = $platTersimpan !== '' && $platTersimpan !== '-' && !str_starts_with($platTersimpan, 'TKT-') && !str_starts_with($platTersimpan, 'MBR-');

        // Jika member belum punya plat valid di tiket dan tidak kirim plat baru → wajib isi
        if (!$isPlatTersimpanValid && $platInput === '') {
            return response()->json([
                'success' => false,
                'message' => 'Plat nomor wajib diisi saat keluar.',
            ], 422);
        }

        // Jika ada input baru, validasi & normalisasi
        if ($platInput !== '') {
            if (strlen($platInput) < 3) {
                return response()->json(['success' => false, 'message' => 'Plat nomor minimal 3 karakter.'], 422);
            }
        }

        $waktuMasuk = $tiket->waktu_masuk ? Carbon::parse($tiket->waktu_masuk) : now();
        $waktuKeluar = now();
        $durasiJam = max(1, ceil($waktuMasuk->diffInHours($waktuKeluar)));
        $tarifPerJam = $this->tarifPerJam($tiket->jenis ?? 'motor');
        $totalTarif = $isMember ? 0 : ($durasiJam * $tarifPerJam);

        $updateData = [
            'status' => 'Selesai',
            'waktu_keluar' => $waktuKeluar,
            'tarif' => $totalTarif,
        ];
        if ($platInput !== '') {
            $updateData['plat_nomor'] = $platInput;
            // Jika member, sync juga ke master members biar konsisten
            if ($isMember) {
                Member::where('kode_member', $tiket->kode_tiket)->whereNotNull('plat_nomor')->update(['plat_nomor' => $platInput]);
                // jika member belum punya plat, tetap update
                Member::where('kode_member', $tiket->kode_tiket)->whereNull('plat_nomor')->update(['plat_nomor' => $platInput]);
            }
        }

        $tiket->update($updateData);

        return response()->json([
            'success' => true,
            'message' => $isMember
                ? 'Akses member gratis, kendaraan dipersilakan keluar'
                : 'Pembayaran berhasil, kendaraan dipersilakan keluar',
            'data' => $tiket,
        ]);
    }
}

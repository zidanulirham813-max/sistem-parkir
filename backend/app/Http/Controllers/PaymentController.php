<?php

namespace App\Http\Controllers;

use App\Models\TiketParkir;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function cekKeluar(Request $request)
    {
        $kode = $request->input('kode') ?? $request->input('kode_tiket');

        $tiket = TiketParkir::where('kode_tiket', $kode)
                            ->where('status', 'Aktif/Parkir')
                            ->first();

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket atau member tidak ditemukan / sudah keluar.'
            ], 404);
        }

        $isMember = (strtolower($tiket->jenis ?? '') === 'member') || 
                    (str_starts_with(strtoupper($tiket->kode_tiket), 'MBR-')) ||
                    (str_starts_with(strtoupper($tiket->plat_nomor ?? ''), 'MBR-'));

        // Hitung durasi dengan pengaman minimal 1 jam mutlak
        $durasiJam = 1;
        if ($tiket->waktu_masuk) {
            $waktuMasuk = Carbon::parse($tiket->waktu_masuk);
            $waktuKeluar = Carbon::now();
            $selisihJam = $waktuMasuk->diffInHours($waktuKeluar);
            if ($selisihJam > 0) {
                $durasiJam = $selisihJam;
            }
        }
        
        if ($isMember) {
            $totalTarif = 0;
        } else {
            $kendaraan = strtolower(trim($tiket->kendaraan ?? $tiket->jenis ?? 'motor'));
            $tarifPerJam = (str_contains($kendaraan, 'mobil')) ? 5000 : 3000;
            $totalTarif = $durasiJam * $tarifPerJam;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tiket->id,
                'kode_tiket' => $tiket->kode_tiket,
                'jenis_kendaraan' => $tiket->kendaraan ?? 'motor',
                'durasi_jam' => $durasiJam,
                'total_bayar' => $totalTarif,
                'tipe' => $isMember ? 'Member' : 'Reguler',
                'is_member' => $isMember
            ]
        ]);
    }

    public function bayar(Request $request)
    {
        $id = $request->input('id');
        $kode = $request->input('kode') ?? $request->input('kode_tiket');

        $tiket = TiketParkir::where(function($query) use ($id, $kode) {
            if ($id) $query->orWhere('id', $id);
            if ($kode) $query->orWhere('kode_tiket', $kode);
        })->where('status', 'Aktif/Parkir')->first();

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Data parkir aktif tidak ditemukan.'
            ], 404);
        }

        $isMember = (strtolower($tiket->jenis ?? '') === 'member') || 
                    (str_starts_with(strtoupper($tiket->kode_tiket), 'MBR-')) ||
                    (str_starts_with(strtoupper($tiket->plat_nomor ?? ''), 'MBR-'));

        $durasiJam = 1;
        if ($tiket->waktu_masuk) {
            $waktuMasuk = Carbon::parse($tiket->waktu_masuk);
            $waktuKeluar = Carbon::now();
            $selisihJam = $waktuMasuk->diffInHours($waktuKeluar);
            if ($selisihJam > 0) {
                $durasiJam = $selisihJam;
            }
        }

        if ($isMember) {
            $totalTarif = 0;
        } else {
            $kendaraan = strtolower(trim($tiket->kendaraan ?? $tiket->jenis ?? 'motor'));
            $tarifPerJam = (str_contains($kendaraan, 'mobil')) ? 5000 : 3000;
            $totalTarif = $durasiJam * $tarifPerJam;
        }

        // SIMPAN tarif ke database agar laporan dan pendapatan hari ini bertambah
        $tiket->update([
            'status' => 'Selesai',
            'waktu_keluar' => Carbon::now()->format('Y-m-d H:i:s'),
            'tarif' => $totalTarif, 
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil.',
            'data' => $tiket
        ]);
    }
}
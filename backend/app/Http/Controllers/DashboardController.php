<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\TiketParkir;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function ringkasan()
    {
        $today = now()->toDateString();

        // Masuk hari ini pakai waktu_masuk agar akurat (fallback ke created_at jika waktu_masuk null)
        $masukHariIni = TiketParkir::whereDate('waktu_masuk', $today)->count();
        // Jika data lama masih pakai created_at, fallback: kalau 0 coba created_at
        if ($masukHariIni === 0) {
            $masukHariIni = TiketParkir::whereDate('created_at', $today)->count();
        }

        $keluarHariIni = TiketParkir::where('status', 'Selesai')->whereDate('waktu_keluar', $today)->count();
        $pendapatanParkirHariIni = (int) TiketParkir::where('status', 'Selesai')->whereDate('waktu_keluar', $today)->sum('tarif');
        $pendapatanMemberHariIni = (int) Member::where('status', 'lunas')->whereDate('tanggal_bayar', $today)->sum('jumlah_bayar');
        $pendapatanHariIni = $pendapatanParkirHariIni + $pendapatanMemberHariIni;

        // Aktivitas terbaru untuk beranda (5 terakhir)
        $aktivitasTerbaru = TiketParkir::latest('waktu_masuk')->latest('created_at')->limit(5)->get()->map(function ($t) {
            return [
                'id' => $t->id,
                'kode_tiket' => $t->kode_tiket,
                'plat_nomor' => $t->plat_nomor,
                'status' => $t->status,
                'waktu_masuk' => $t->waktu_masuk,
                'waktu_keluar' => $t->waktu_keluar,
                'jenis' => $t->jenis,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_member' => Member::count(),
                'kendaraan_masuk_hari_ini' => $masukHariIni,
                'kendaraan_keluar_hari_ini' => $keluarHariIni,
                'pendapatan_hari_ini' => $pendapatanHariIni,
                'pendapatan_parkir_hari_ini' => $pendapatanParkirHariIni,
                'pendapatan_member_hari_ini' => $pendapatanMemberHariIni,
                'aktivitas_terbaru' => $aktivitasTerbaru,
            ]
        ], 200);
    }

    /**
     * Trend 7 hari terakhir untuk diagram garis beranda.
     * GET /api/dashboard/trend?days=7
     */
    public function trend(Request $request)
    {
        $days = (int) $request->input('days', 7);
        $days = max(1, min(30, $days));

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();

            // label: Sen, Sel, ... atau "Hari ini" untuk hari terakhir
            if ($i === 0) {
                $label = 'Hari ini';
            } else {
                $label = $date->locale('id')->isoFormat('ddd'); // Sen, Sel, etc
                // capitalize first letter
                $label = ucfirst($label);
            }

            $masuk = TiketParkir::whereDate('waktu_masuk', $dateStr)->count();
            if ($masuk === 0) {
                $masuk = TiketParkir::whereDate('created_at', $dateStr)->count();
            }
            $keluar = TiketParkir::where('status', 'Selesai')->whereDate('waktu_keluar', $dateStr)->count();

            $result[] = [
                'date' => $dateStr,
                'label' => $label,
                'masuk' => $masuk,
                'keluar' => $keluar,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}

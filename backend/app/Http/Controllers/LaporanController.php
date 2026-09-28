<?php

namespace App\Http\Controllers;

use App\Models\TiketParkir;
use App\Models\Member;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class LaporanController extends Controller
{
    public function semua(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::today()->startOfDay());
            $endDate = $request->input('end_date', Carbon::today()->endOfDay());

            $data = TiketParkir::where('status', 'Selesai')
                ->whereBetween('waktu_keluar', [$startDate, $endDate])
                ->latest()
                ->get();

            $totalPendapatanParkir = (int) $data->sum('tarif');
            // Iuran member yang lunas pada periode yang sama — ikut menambah pendapatan
            $totalPendapatanMember = (int) Member::where('status', 'lunas')
                ->whereBetween('tanggal_bayar', [$startDate, $endDate])
                ->sum('jumlah_bayar');
            $totalPendapatan = $totalPendapatanParkir + $totalPendapatanMember;

            return response()->json([
                'status' => 'success',
                'total_pendapatan' => $totalPendapatan,
                'pendapatan_parkir' => $totalPendapatanParkir,
                'pendapatan_member' => $totalPendapatanMember,
                'total_transaksi' => $data->count(),
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function member(Request $request)
    {
        try {
            $data = TiketParkir::where('status', 'Selesai')
                ->where(function($q) {
                    $q->where('jenis', 'Member')
                      ->orWhere('kode_tiket', 'LIKE', 'MBR-%');
                })
                ->latest()
                ->get();

            // pendapatan member = iuran yang sudah lunas (bukan tarif parkir member yang 0)
            $pendapatanMember = (int) Member::where('status', 'lunas')->sum('jumlah_bayar');

            return response()->json([
                'status' => 'success',
                'total_member_keluar' => $data->count(),
                'pendapatan_member' => $pendapatanMember,
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function nonMember(Request $request)
    {
        try {
            $data = TiketParkir::where('status', 'Selesai')
                ->where('jenis', '!=', 'Member')
                ->where('kode_tiket', 'NOT LIKE', 'MBR-%')
                ->latest()
                ->get();

            return response()->json([
                'status' => 'success',
                'total_non_member' => $data->count(),
                'total_pendapatan' => (int) $data->sum('tarif'),
                'pendapatan_parkir' => (int) $data->sum('tarif'),
                'data' => $data
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

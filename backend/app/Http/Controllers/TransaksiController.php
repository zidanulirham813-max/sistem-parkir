<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Member;
use Carbon\Carbon;

class TransaksiController extends Controller
{
    public function cetakTiketMasuk(Request $request)
    {
        $request->validate([
            'jenis_kendaraan' => 'required|in:motor,mobil',
            'plat_nomor' => 'nullable|string'
        ]);

        $kode_tiket = 'TKT-' . strtoupper(substr(uniqid(), -6));
        
        $transaksi = Transaksi::create([
            'kode_tiket' => $kode_tiket,
            'plat_nomor' => $request->plat_nomor,
            'jenis_kendaraan' => $request->jenis_kendaraan,
            'waktu_masuk' => Carbon::now(),
            'status' => 'belum keluar',
            'is_member' => false
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tiket masuk berhasil dibuat',
            'data' => $transaksi
        ]);
    }

    public function scanMemberMasuk(Request $request)
    {
        $request->validate([
            'kode_member' => 'required|string'
        ]);

        $inputCode = trim($request->kode_member);

        if (str_starts_with(strtoupper($inputCode), 'TKT-')) {
            $transaksi = Transaksi::where('kode_tiket', $inputCode)->first();

            if (!$transaksi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kode tiket reguler tidak ditemukan di database.'
                ], 422);
            }

            if ($transaksi->status === 'belum keluar') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kendaraan sudah tercatat di dalam area parkir.'
                ], 422);
            }

            $transaksi->update([
                'status' => 'belum keluar',
                'waktu_masuk' => Carbon::now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tiket reguler berhasil masuk!',
                'data' => $transaksi
            ]);
        }

        $member = Member::where('kode_member', $inputCode)->first();

        if (!$member) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode tiket atau kode member tidak ditemukan.'
            ], 422);
        }

        if ($member->status !== 'lunas') {
            return response()->json([
                'status' => 'error',
                'message' => 'Status member belum lunas atau sudah expired.'
            ], 422);
        }

        $existing = Transaksi::where('kode_member', $member->kode_member)
            ->where('status', 'belum keluar')
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Member ini sudah berada di dalam area parkir.'
            ], 422);
        }

        $transaksi = Transaksi::create([
            'kode_tiket' => $member->kode_member,
            'kode_member' => $member->kode_member,
            'jenis_kendaraan' => 'motor',
            'waktu_masuk' => Carbon::now(),
            'status' => 'belum keluar',
            'is_member' => true,
            'member_id' => $member->id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Member berhasil masuk!',
            'data' => $transaksi
        ]);
    }

    public function cekTiketKeluar(Request $request)
    {
        $inputCode = trim($request->input('kode_tiket') ?? $request->input('kode'));

        if (empty($inputCode)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode tiket atau kode member wajib diisi.'
            ], 422);
        }

        $transaksi = Transaksi::where('status', 'belum keluar')
            ->where(function($query) use ($inputCode) {
                $query->where('kode_tiket', $inputCode)
                      ->orWhere('kode_member', $inputCode)
                      ->orWhereHas('member', function($q) use ($inputCode) {
                          $q->where('kode_member', $inputCode);
                      });
            })
            ->first();

        if (!$transaksi) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode tiket/member tidak ditemukan atau kendaraan sudah keluar.'
            ], 422);
        }

        $waktuMasuk = Carbon::parse($transaksi->waktu_masuk);
        $waktuKeluar = Carbon::now();
        $durasiJam = max(1, ceil($waktuMasuk->diffInMinutes($waktuKeluar) / 60));

        $isMember = !empty($transaksi->kode_member) || $transaksi->is_member == true;

        $tarifPerJam = match($transaksi->jenis_kendaraan) {
            'mobil' => 5000,
            default => 3000, // motor 3k/jam
        };

        $totalTarif = $isMember ? 0 : ($durasiJam * $tarifPerJam);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $transaksi->id,
                'kode_tiket' => $transaksi->kode_tiket,
                'jenis_kendaraan' => $transaksi->jenis_kendaraan,
                'waktu_masuk' => $transaksi->waktu_masuk,
                'durasi_jam' => $durasiJam,
                'total_bayar' => $totalTarif,
                'is_member' => $isMember,
                'tipe' => $isMember ? 'Member (Gratis)' : 'Reguler'
            ]
        ]);
    }

    public function prosesPembayaranKeluar(Request $request)
    {
        $transaksi = null;

        if ($request->filled('id')) {
            $transaksi = Transaksi::find($request->id);
        } else {
            $inputCode = trim($request->input('kode_tiket') ?? $request->input('kode'));
            if (!empty($inputCode)) {
                $transaksi = Transaksi::where('status', 'belum keluar')
                    ->where(function($query) use ($inputCode) {
                        $query->where('kode_tiket', $inputCode)
                              ->orWhere('kode_member', $inputCode);
                    })
                    ->first();
            }
        }

        if (!$transaksi) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi aktif tidak ditemukan.'
            ], 422);
        }

        $isMember = !empty($transaksi->kode_member) || $transaksi->is_member == true;

        $transaksi->update([
            'waktu_keluar' => Carbon::now(),
            'status' => 'sudah keluar',
            'jumlah_bayar' => $isMember ? 0 : ($request->jumlah_bayar ?? 0)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $isMember ? 'Akses member diterima, gerbang dibuka.' : 'Pembayaran berhasil, gerbang dibuka.'
        ]);
    }
}
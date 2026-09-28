<?php

namespace App\Http\Controllers;

use App\Models\TiketParkir;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TiketController extends Controller
{
    public function create(Request $request)
    {
        $kode = "TKT-" . rand(100000, 999999);

        $tiket = TiketParkir::create([
            'kode_tiket'   => $kode,
            'qr_code'      => Str::random(60),
            'status'       => 'Aktif/Parkir',
            'waktu_masuk'  => Carbon::now()->format('Y-m-d H:i:s'),
            'waktu_keluar' => null,
            'jenis'        => $request->input('jenis_kendaraan', 'Reguler'),
            'kendaraan'    => $request->input('jenis_kendaraan', 'motor'),
            'plat_nomor'   => $request->input('plat_nomor', $kode),
            'tarif'        => 0,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $tiket
        ]);
    }

    public function scanMember(Request $request)
    {
        $kodeMember = trim((string) $request->input('kode_member', ''));
        if ($kodeMember === '') {
            return response()->json(['success' => false, 'message' => 'Kode member wajib diisi.'], 422);
        }

        // Validasi member harus ada, lunas & belum expired, dan harus punya plat
        $member = Member::where('kode_member', $kodeMember)->first();
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Kode member tidak ditemukan.'], 404);
        }
        if ($member->tanggal_expired && Carbon::now()->greaterThan(Carbon::parse($member->tanggal_expired))) {
            // sync status
            if ($member->status !== 'sudah expired') {
                $member->update(['status' => 'sudah expired']);
            }
            return response()->json(['success' => false, 'message' => 'Masa aktif member sudah kedaluwarsa.'], 400);
        }
        if ($member->status !== 'lunas') {
            return response()->json(['success' => false, 'message' => 'Member belum lunas (Status: ' . $member->status . ').'], 400);
        }
        if (empty($member->plat_nomor)) {
            return response()->json(['success' => false, 'message' => 'Member belum memiliki plat nomor. Lengkapi plat di data member.'], 422);
        }

        // Jika member ini masih Aktif/Parkir (belum keluar), jangan buat baris baru
        $aktif = TiketParkir::where('kode_tiket', $kodeMember)
            ->where('status', 'Aktif/Parkir')
            ->first();
        if ($aktif) {
            return response()->json([
                'success' => false,
                'message' => 'Member ini masih di dalam area parkir (' . $kodeMember . '). Selesaikan scan keluar dulu sebelum masuk lagi.',
            ], 422);
        }

        try {
            $tiket = TiketParkir::create([
                'kode_tiket'   => $kodeMember,
                'qr_code'      => Str::random(60),
                'status'       => 'Aktif/Parkir',
                'waktu_masuk'  => Carbon::now()->format('Y-m-d H:i:s'),
                'waktu_keluar' => null,
                'jenis'        => 'Member',
                'kendaraan'    => 'Member',
                // pakai plat asli member, bukan kode MBR-xxx
                'plat_nomor'   => strtoupper(trim($member->plat_nomor)),
                'tarif'        => 0,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplikat kode di database (unique constraint). Jalankan: php artisan migrate.',
                ], 409);
            }
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil masuk',
            'data'    => $tiket
        ]);
    }

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

        $isMember = strtolower($tiket->jenis) === 'member' || str_starts_with(strtoupper($tiket->kode_tiket), 'MBR-');

        $waktuMasuk = Carbon::parse($tiket->waktu_masuk);
        $waktuKeluar = Carbon::now();
        $durasiJam = max(1, ceil($waktuMasuk->diffInHours($waktuKeluar)));

        $tarifPerJam = (strtolower($tiket->kendaraan) == 'mobil') ? 5000 : 3000;
        $totalTarif = $isMember ? 0 : ($durasiJam * $tarifPerJam);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tiket->id,
                'kode_tiket' => $tiket->kode_tiket,
                'plat_nomor' => $tiket->plat_nomor,
                'jenis_kendaraan' => $tiket->kendaraan,
                'durasi_jam' => $durasiJam,
                'total_bayar' => $totalTarif,
                'tipe' => $isMember ? 'Member' : 'Reguler',
                'is_member' => $isMember
            ]
        ]);
    }

    public function keluar(Request $request)
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

        $isMember = strtolower($tiket->jenis) === 'member' || str_starts_with(strtoupper($tiket->kode_tiket), 'MBR-');

        $waktuMasuk = Carbon::parse($tiket->waktu_masuk);
        $waktuKeluar = Carbon::now();
        $durasiJam = max(1, ceil($waktuMasuk->diffInHours($waktuKeluar)));
        $tarifPerJam = (strtolower($tiket->kendaraan) == 'mobil') ? 5000 : 3000;
        $totalTarif = $isMember ? 0 : ($durasiJam * $tarifPerJam);

        $tiket->update([
            'status' => 'Selesai',
            'waktu_keluar' => $waktuKeluar->format('Y-m-d H:i:s'),
            'tarif' => $totalTarif,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dan pintu terbuka.',
            'data' => $tiket
        ]);
    }

    public function index()
    {
        $tikets = TiketParkir::latest()->get()->map(function ($item) {
            $tarif = $item->tarif ?? 0;

            if ($item->status !== 'Selesai' && $item->waktu_masuk) {
                $waktuMasuk = Carbon::parse($item->waktu_masuk);
                $durasiJam = max(1, ceil($waktuMasuk->diffInHours(Carbon::now())));
                $isMember = strtolower($item->jenis ?? '') === 'member' || str_starts_with(strtoupper($item->kode_tiket), 'MBR-');
                $tarifPerJam = strtolower($item->kendaraan ?? '') === 'mobil' ? 5000 : 3000;
                $tarif = $isMember ? 0 : ($durasiJam * $tarifPerJam);
            }

            return [
                'id' => $item->id,
                'kode_tiket' => $item->kode_tiket,
                'waktu_masuk' => $item->waktu_masuk,
                'waktu_keluar' => $item->waktu_keluar,
                'status' => $item->status,
                'jenis' => $item->jenis,
                'kendaraan' => $item->kendaraan,
                'plat_nomor' => $item->plat_nomor,
                'tarif' => $tarif,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tikets
        ]);
    }

    public function destroy($id)
    {
        $tiket = TiketParkir::find($id);

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan.'
            ], 404);
        }

        if ($tiket->status !== 'Selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya tiket dengan status Selesai yang bisa dihapus.'
            ], 400);
        }

        $tiket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dihapus.'
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MemberController extends Controller
{
    // ===================================
    // TAMPIL SEMUA MEMBER + OTOMATIS CEK EXPIRED
    // ===================================

    public function index()
    {
        $members = Member::all();

        foreach ($members as $member) {
            if ($member->tanggal_expired && Carbon::now()->greaterThan(Carbon::parse($member->tanggal_expired))) {
                $member->update([
                    'status' => 'sudah expired'
                ]);
            }
        }

        $members = Member::latest()->get();

        return response()->json([
            "status" => true,
            "data" => $members
        ]);
    }


    // ===================================
    // TAMBAH MEMBER
    // ===================================

    public function store(Request $request)
    {
        $request->validate([
            'nama_member' => 'required|string|max:100',
            'nama_perusahaan' => 'required|string|max:100',
            'plat_nomor' => 'required|string|max:20|unique:members,plat_nomor',
            'jumlah_bayar' => 'required|numeric|min:0'
        ], [
            'plat_nomor.required' => 'Plat nomor wajib diisi untuk member.',
            'plat_nomor.unique' => 'Plat nomor sudah terdaftar pada member lain.',
        ]);

        $plat = strtoupper(trim($request->plat_nomor));

        $harga_member = 150000;
        $jumlah_bayar = (float) $request->jumlah_bayar;

        if ($jumlah_bayar > $harga_member) {
            return response()->json([
                "status" => false,
                "message" => "Pembayaran tidak boleh lebih dari Rp " . number_format($harga_member, 0, ',', '.')
            ], 422);
        }

        $kembalian = 0;
        $status = $jumlah_bayar >= $harga_member ? "lunas" : "belum lunas";

        $member = Member::create([
            'kode_member' => $this->generateKodeMember(),
            'token' => (string) Str::uuid(),
            'nama_member' => $request->nama_member,
            'nama_perusahaan' => $request->nama_perusahaan,
            'plat_nomor' => $plat,
            'total_harga' => $harga_member,
            'jumlah_bayar' => $jumlah_bayar,
            'kembalian' => $kembalian,
            'status' => $status,
            'tanggal_bayar' => Carbon::now(),
            'tanggal_mulai' => Carbon::now(),
            // LUNAS = aktif 1 bulan ke depan (mis. bayar 16 Sep → expired 16 Okt 23:59)
            'tanggal_expired' => $status === "lunas" ? Carbon::now()->addMonthNoOverflow()->endOfDay() : null
        ]);

        return response()->json([
            "status" => true,
            "message" => "Member berhasil dibuat",
            "data" => $member
        ], 201);
    }


    // ===================================
    // GENERATE KODE MEMBER
    // ===================================

    private function generateKodeMember()
    {
        do {
            $kode = 'MBR-' . strtoupper(Str::random(6));
        } while (
            Member::where('kode_member', $kode)->exists()
        );

        return $kode;
    }


    // ===================================
    // DETAIL MEMBER
    // ===================================

    public function show($id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                "status" => false,
                "message" => "Member tidak ditemukan"
            ], 404);
        }

        if ($member->tanggal_expired && Carbon::now()->greaterThan(Carbon::parse($member->tanggal_expired))) {
            $member->update([
                'status' => 'sudah expired'
            ]);
            $member->refresh();
        }

        return response()->json([
            "status" => true,
            "data" => [
                "id" => $member->id,
                "kode_member" => $member->kode_member,
                "token" => $member->token,
                "nama_member" => $member->nama_member,
                "nama_perusahaan" => $member->nama_perusahaan,
                "plat_nomor" => $member->plat_nomor,
                "total_harga" => $member->total_harga,
                "jumlah_bayar" => $member->jumlah_bayar,
                "kembalian" => $member->kembalian,
                "status" => $member->status,
                "tanggal_bayar" => $member->tanggal_bayar,
                "tanggal_mulai" => $member->tanggal_mulai,
                "tanggal_expired" => $member->tanggal_expired,
                "qr" => null // QR dinonaktifkan sementara agar tidak error package
            ]
        ]);
    }


    // ===================================
    // UPDATE DATA MEMBER
    // ===================================

    public function update(Request $request, $id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                "status" => false,
                "message" => "Member tidak ditemukan"
            ], 404);
        }

        // Edit tetap boleh walau sudah lunas — hanya data identitas & plat yang diubah, bukan pembayaran
        $request->validate([
            'nama_member' => 'required|string|max:100',
            'nama_perusahaan' => 'required|string|max:100',
            'plat_nomor' => 'required|string|max:20|unique:members,plat_nomor,' . $id,
        ], [
            'plat_nomor.required' => 'Plat nomor wajib diisi untuk member.',
            'plat_nomor.unique' => 'Plat nomor sudah terdaftar pada member lain.',
        ]);

        $member->update([
            'nama_member' => $request->nama_member,
            'nama_perusahaan' => $request->nama_perusahaan,
            'plat_nomor' => strtoupper(trim($request->plat_nomor)),
        ]);

        return response()->json([
            "status" => true,
            "message" => "Data member berhasil diperbarui",
            "data" => $member
        ]);
    }


    // ===================================
    // UPDATE PEMBAYARAN
    // ===================================

    public function updatePembayaran(Request $request, $id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                "status" => false,
                "message" => "Member tidak ditemukan"
            ], 404);
        }

        $request->validate([
            'jumlah_bayar' => 'required|numeric|min:0'
        ]);

        $jumlah_bayar = (float) $request->jumlah_bayar;
        $total_harga = (float) $member->total_harga;

        if ($jumlah_bayar > $total_harga) {
            return response()->json([
                "status" => false,
                "message" => "Pembayaran tidak boleh lebih dari Rp " . number_format($total_harga, 0, ',', '.')
            ], 422);
        }

        $kembalian = 0;
        $status = $jumlah_bayar >= $total_harga ? "lunas" : "belum lunas";

        $tanggal_expired = $member->tanggal_expired;

        if ($status === "lunas") {
            // Bayar/perpanjang → expired = 1 bulan dari expired lama (jika masih aktif), atau 1 bulan dari sekarang (jika sudah expired/belum pernah lunas)
            if (!$member->tanggal_expired || Carbon::now()->greaterThan(Carbon::parse($member->tanggal_expired))) {
                $tanggal_expired = Carbon::now()->addMonthNoOverflow()->endOfDay();
            } else {
                $tanggal_expired = Carbon::parse($member->tanggal_expired)->addMonthNoOverflow()->endOfDay();
            }
        }

        $member->update([
            'jumlah_bayar' => $jumlah_bayar,
            'kembalian' => $kembalian,
            'status' => $status,
            'tanggal_bayar' => Carbon::now(),
            'tanggal_expired' => $tanggal_expired
        ]);

        $member->refresh();

        return response()->json([
            "status" => true,
            "message" => $status === "lunas"
                ? "Pembayaran lunas dan masa aktif member diperpanjang sampai " . Carbon::parse($tanggal_expired)->format('d/m/Y')
                : "Pembayaran berhasil diperbarui",
            "data" => $member
        ]);
    }


    // ===================================
    // HAPUS MEMBER
    // ===================================

    public function destroy($id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                "status" => false,
                "message" => "Member tidak ditemukan"
            ], 404);
        }

        if ($member->status === 'lunas') {
            return response()->json([
                "status" => false,
                "message" => "Member yang sudah lunas tidak bisa dihapus"
            ], 403);
        }

        $member->delete();

        return response()->json([
            "status" => true,
            "message" => "Member berhasil dihapus"
        ]);
    }


    // ===================================
    // SCAN QR MEMBER
    // ===================================

    public function check(Request $request)
    {
        $request->validate([
            'token' => 'required'
        ]);

        $member = Member::where('token', $request->token)->first();

        if (!$member) {
            return response()->json([
                "status" => false,
                "message" => "QR Member tidak ditemukan"
            ], 404);
        }

        if ($member->tanggal_expired && Carbon::now()->greaterThan(Carbon::parse($member->tanggal_expired))) {
            $member->update([
                'status' => 'sudah expired'
            ]);

            return response()->json([
                "status" => false,
                "message" => "Member sudah expired"
            ]);
        }

        if ($member->status !== "lunas") {
            return response()->json([
                "status" => false,
                "message" => "Member belum melakukan pembayaran (Status: " . $member->status . ")"
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "Member valid",
            "data" => $member
        ]);
    }


    // ===================================
    // RESET BULANAN
    // ===================================

    public function resetBulanan()
    {
        if (Carbon::now()->day == 1) {
            Member::query()->update([
                'status' => 'belum lunas',
                'jumlah_bayar' => 0,
                'kembalian' => 0,
                'tanggal_bayar' => null
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "Status member diperbarui"
        ]);
    }
}
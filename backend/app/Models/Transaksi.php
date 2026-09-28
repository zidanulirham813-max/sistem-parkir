<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi'; // Menyesuaikan nama tabel di database

    protected $fillable = [
        'kode_tiket',
        'kode_member',
        'plat_nomor',
        'jenis_kendaraan',
        'waktu_masuk',
        'waktu_keluar',
        'total_biaya',
        'jumlah_bayar',
        'kembalian',
        'status',
    ];

    /**
     * Relasi ke tabel Member berdasarkan kode_member
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'kode_member', 'kode_member');
    }
}
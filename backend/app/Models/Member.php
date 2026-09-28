<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'kode_member',
        'token',
        'nama_member',
        'nama_perusahaan',
        'plat_nomor',
        'total_harga',
        'kembalian',
        'status',
        'tanggal_mulai',
        'tanggal_expired',
        'jumlah_bayar',
        'tanggal_bayar',
        'tanggal_reset',
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_expired' => 'datetime',
        'tanggal_bayar' => 'datetime',
        'tanggal_reset' => 'date',
        'total_harga' => 'integer',
        'jumlah_bayar' => 'integer',
        'kembalian' => 'integer',
    ];
}


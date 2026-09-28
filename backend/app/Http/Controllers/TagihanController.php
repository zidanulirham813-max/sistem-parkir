<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan; // Sesuaikan dengan nama model Anda

class TagihanController extends Controller
{
    public function indexUnpaid()
    {
        // Mengambil tagihan yang statusnya belum lunas
        $tagihan = Tagihan::with('member')
            ->where('status', '!=', 'lunas') // atau ->where('status', 'belum lunas')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $tagihan
        ]);
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tiket')->unique();
            $table->string('kode_member')->nullable();
            $table->string('plat_nomor')->default('-');
            $table->string('jenis_kendaraan')->default('Mobil/Motor');
            $table->timestamp('waktu_masuk')->nullable();
            $table->timestamp('waktu_keluar')->nullable();
            $table->decimal('total_biaya', 10, 2)->default(0);
            $table->decimal('jumlah_bayar', 10, 2)->default(0);
            $table->decimal('kembalian', 10, 2)->default(0);
            $table->string('status')->default('masuk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
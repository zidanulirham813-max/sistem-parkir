<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('kode_member')->unique();
            $table->string('token')->nullable();
            $table->string('nama_member');
            $table->string('nama_perusahaan')->nullable();
            $table->decimal('total_harga', 10, 2)->default(0);
            $table->decimal('jumlah_bayar', 10, 2)->default(0);
            $table->decimal('kembalian', 10, 2)->default(0);
            $table->string('status')->default('belum lunas');
            $table->dateTime('tanggal_bayar')->nullable();
            $table->dateTime('tanggal_mulai')->nullable();
            $table->dateTime('tanggal_expired')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
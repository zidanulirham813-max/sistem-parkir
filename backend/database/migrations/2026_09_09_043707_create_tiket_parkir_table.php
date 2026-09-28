<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('tiket_parkir', function (Blueprint $table) {
        $table->id();
        $table->string('kode_tiket')->unique();
        $table->string('qr_code')->nullable();
        $table->string('status')->default('Aktif/Parkir');
        $table->dateTime('waktu_masuk')->nullable();
        $table->dateTime('waktu_keluar')->nullable();
        $table->string('jenis')->default('Reguler');
        $table->string('kendaraan')->nullable();
        $table->string('plat_nomor')->nullable();
        $table->decimal('tarif', 12, 2)->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiket_parkir');
    }
};
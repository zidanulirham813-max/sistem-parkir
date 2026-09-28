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
        Schema::table('members', function (Blueprint $table) {
            // Ubah tipe data kolom status menjadi string / varchar(50)
            $table->string('status', 50)->default('belum lunas')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Mengembalikan ke enum awal jika migration di-rollback
            $table->enum('status', ['lunas', 'belum lunas'])->default('belum lunas')->change();
        });
    }
};
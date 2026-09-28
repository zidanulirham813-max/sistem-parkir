<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\TiketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransaksiController;

// ===== PUBLIC — tidak butuh login =====
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Gate masuk/keluar — dipakai halaman /users/scan-masuk (tanpa login) & petugas scan
Route::post('/parkir/masuk/tiket', [TiketController::class, 'create']);
Route::post('/parkir/masuk/member', [TiketController::class, 'scanMember']);
Route::post('/scan/keluar/cek', [\App\Http\Controllers\ScanController::class, 'cekKeluar']);
Route::post('/scan/keluar/bayar', [\App\Http\Controllers\ScanController::class, 'bayarKeluar']);

// ===== AUTH — butuh token (petugas atau super_admin) =====
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profil akun sendiri
    Route::get('/me', [PetugasController::class, 'me']);
    Route::post('/me', [PetugasController::class, 'updateMe']);
    Route::put('/me', [PetugasController::class, 'updateMe']);

    // Dashboard — boleh petugas & super_admin
    Route::get('/dashboard/ringkasan', [DashboardController::class, 'ringkasan']);
    Route::get('/dashboard/trend', [DashboardController::class, 'trend']);

    // Tiket & Parkir umum
    Route::get('/tiket', [TiketController::class, 'index']);
    Route::post('/tiket', [TiketController::class, 'create']);
    Route::post('/tiket/{id}/keluar', [PaymentController::class, 'bayar']);
    Route::delete('/tiket/{id}', [\App\Http\Controllers\TiketController::class, 'destroy']);

    // Member — petugas & super_admin boleh kelola
    Route::apiResource('members', MemberController::class);
    Route::put('members/{id}/pembayaran', [MemberController::class, 'updatePembayaran']);
    Route::post('members/check', [MemberController::class, 'check']);

    // Tagihan & Transaksi
    Route::get('/tagihan/belum-lunas', [TagihanController::class, 'indexUnpaid']);
    Route::get('/transaksi', [TransaksiController::class, 'index']);
    Route::get('/transaksi/{id}', [TransaksiController::class, 'show']);
    Route::post('/transaksi', [TransaksiController::class, 'store']);
    Route::put('/transaksi/{id}', [TransaksiController::class, 'update']);
    Route::delete('/transaksi/{id}', [TransaksiController::class, 'destroy']);

    // ===== SUPER ADMIN ONLY =====
    Route::middleware('role:super_admin')->group(function () {
        // CRUD Petugas
        Route::get('/petugas', [PetugasController::class, 'index']);
        Route::post('/petugas', [PetugasController::class, 'store']);
        Route::get('/petugas/{id}', [PetugasController::class, 'show']);
        Route::put('/petugas/{id}', [PetugasController::class, 'update']);
        Route::post('/petugas/{id}', [PetugasController::class, 'update']);
        Route::delete('/petugas/{id}', [PetugasController::class, 'destroy']);

        // Laporan
        Route::get('/laporan', [LaporanController::class, 'semua']);
        Route::get('/laporan/semua', [LaporanController::class, 'semua']);
        Route::get('/laporan/member', [LaporanController::class, 'member']);
        Route::get('/laporan/non-member', [LaporanController::class, 'nonMember']);
    });
});

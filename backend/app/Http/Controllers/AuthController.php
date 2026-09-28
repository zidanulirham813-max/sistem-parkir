<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\FonnteService;

class AuthController extends Controller
{
    // Masa berlaku OTP reset password (menit)
    const OTP_EXPIRY_MINUTES = 5;

    // Jeda minimum sebelum boleh kirim ulang OTP (detik)
    const OTP_RESEND_COOLDOWN_SECONDS = 60;

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->username)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'access_token' => $token,
                'user' => $user
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Username atau password salah.'
        ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Lupa password — kirim OTP via Fonnte WA ke no_tlp user.
     * Frontend hanya kirim email, backend cari user, ambil no_tlp, kirim OTP 6 digit ke WA.
     * OTP berlaku 5 menit, dan hanya bisa dikirim ulang setelah cooldown 60 detik.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email tidak ditemukan.'
            ], 404);
        }

        if (empty($user->no_tlp)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor HP belum terdaftar untuk email ini. Silakan hubungi admin untuk melengkapi nomor HP di data petugas.'
            ], 422);
        }

        // Cek cooldown resend: kalau OTP sebelumnya masih fresh (< 60 detik), tolak kirim ulang
        $existing = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        if ($existing) {
            $secondsSinceLastSend = now()->diffInSeconds($existing->created_at);

            if ($secondsSinceLastSend < self::OTP_RESEND_COOLDOWN_SECONDS) {
                $sisaDetik = self::OTP_RESEND_COOLDOWN_SECONDS - $secondsSinceLastSend;

                return response()->json([
                    'status' => 'error',
                    'message' => "Tunggu {$sisaDetik} detik lagi sebelum meminta kode OTP baru.",
                    'retry_after_seconds' => $sisaDetik,
                ], 429);
            }
        }

        // OTP 6 digit lebih ramah untuk WA (bukan 64 char random)
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($otp), 'created_at' => now()]
        );

        $waMessage = "🔐 *PARKIR SYSTEM — Kode Reset Password*\n\n"
            . "Halo {$user->name},\n"
            . "Kode OTP reset password kamu: *{$otp}*\n"
            . "Berlaku " . self::OTP_EXPIRY_MINUTES . " menit. Jangan bagikan kode ini ke siapapun.\n\n"
            . "Jika kamu tidak meminta reset, abaikan pesan ini.";

        $fonnteResult = FonnteService::send($user->no_tlp, $waMessage);
        $masked = FonnteService::maskPhone($user->no_tlp);

        // Jika Fonnte belum dikonfigurasi (token kosong) → fallback dev: tetap return OTP agar bisa dites
        // Di produksi hapus blok fallback ini agar OTP tidak bocor di response.
        $isFonnteNotConfigured = !$fonnteResult['ok'] && str_contains($fonnteResult['error'] ?? '', 'FONNTE_TOKEN');

        if ($isFonnteNotConfigured) {
            return response()->json([
                'status' => 'success',
                'message' => "Token berhasil dibuat, tapi FONNTE_TOKEN belum diatur. Kode OTP (DEV ONLY): {$otp}. Segera isi FONNTE_TOKEN di .env agar terkirim ke WA {$masked}.",
                'phone_masked' => $masked,
                'wa_sent' => false,
                'dev_otp' => $otp, // HAPUS di produksi
                'fonnte_error' => $fonnteResult['error'],
                'expires_in_minutes' => self::OTP_EXPIRY_MINUTES,
                'resend_cooldown_seconds' => self::OTP_RESEND_COOLDOWN_SECONDS,
            ]);
        }

        if (!$fonnteResult['ok']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim OTP ke WhatsApp (' . $fonnteResult['error'] . '). Coba lagi atau hubungi admin.',
                'phone_masked' => $masked,
                'wa_sent' => false,
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Kode OTP telah dikirim ke WhatsApp nomor {$masked}. Berlaku " . self::OTP_EXPIRY_MINUTES . " menit.",
            'phone_masked' => $masked,
            'wa_sent' => true,
            'expires_in_minutes' => self::OTP_EXPIRY_MINUTES,
            'resend_cooldown_seconds' => self::OTP_RESEND_COOLDOWN_SECONDS,
            // Jangan kirim OTP asli jika WA sudah sukses (keamanan). Untuk debug dev uncomment baris bawah:
            // 'dev_otp' => $otp,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token/OTP tidak valid.'
            ], 400);
        }

        if (now()->diffInMinutes($record->created_at) > self::OTP_EXPIRY_MINUTES) {
            // Hapus token expired supaya tidak nyangkut dan blokir resend berikutnya
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'status' => 'error',
                'message' => 'Token/OTP sudah kadaluarsa (lebih dari ' . self::OTP_EXPIRY_MINUTES . ' menit). Silakan minta OTP baru.'
            ], 400);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil direset. Silakan login dengan password baru.'
        ]);
    }

    public function listUsers()
    {
        return response()->json([
            'status' => 'success',
            'data' => User::all()
        ]);
    }
}
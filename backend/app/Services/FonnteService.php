<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    /**
     * Kirim pesan WhatsApp via Fonnte.
     *
     * @param string $target Nomor tujuan (08..., 628..., atau +62...). Otomatis dinormalisasi ke 62...
     * @param string $message Isi pesan
     * @return array{ok: bool, response: mixed, error: string|null}
     */
    public static function send(string $target, string $message): array
    {
        $token = config('services.fonnte.token');
        $url = config('services.fonnte.url', 'https://api.fonnte.com/send');

        if (empty($token)) {
            Log::warning('Fonnte token belum diisi (FONNTE_TOKEN di .env). Pesan tidak dikirim.', ['target' => $target]);
            return ['ok' => false, 'response' => null, 'error' => 'FONNTE_TOKEN belum diatur di .env'];
        }

        $normalized = self::normalizePhone($target);

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->asForm()->post($url, [
                'target' => $normalized,
                'message' => $message,
                // Optional: 'countryCode' => '62',
            ]);

            $body = $response->json() ?? $response->body();

            if ($response->successful()) {
                Log::info('Fonnte send OK', ['target' => $normalized, 'response' => $body]);
                return ['ok' => true, 'response' => $body, 'error' => null];
            }

            Log::error('Fonnte send gagal', ['target' => $normalized, 'status' => $response->status(), 'body' => $body]);
            return ['ok' => false, 'response' => $body, 'error' => 'Fonnte HTTP ' . $response->status()];

        } catch (\Throwable $e) {
            Log::error('Fonnte exception', ['message' => $e->getMessage()]);
            return ['ok' => false, 'response' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * 08xxxxxxxxxx -> 628xxxxxxxxxx
     * +628xxxxxxxxxx -> 628xxxxxxxxxx
     * 628xxxxxxxxxx -> tetap
     */
    public static function normalizePhone(string $phone): string
    {
        $p = preg_replace('/[^0-9+]/', '', trim($phone));
        if (str_starts_with($p, '+')) {
            $p = substr($p, 1);
        }
        if (str_starts_with($p, '0')) {
            $p = '62' . substr($p, 1);
        }
        return $p;
    }

    /**
     * Mask nomor untuk ditampilkan ke user: 62812****890
     */
    public static function maskPhone(string $phone): string
    {
        $n = self::normalizePhone($phone);
        if (strlen($n) <= 7) {
            return str_repeat('*', strlen($n));
        }
        return substr($n, 0, 5) . str_repeat('*', strlen($n) - 7) . substr($n, -2);
    }
}

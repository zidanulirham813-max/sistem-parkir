# Panduan Lengkap: Lupa Password via WhatsApp (Fonnte) — Parkir Zidan

Dokumen ini menjelaskan setup **dari nol** sampai test end-to-end. Sudah disesuaikan dengan kode yang ada di repo ini.

---

## 1) Arsitektur yang sudah jadi

| Layer | File | Fungsi |
|---|---|---|
| Service WA | `backend/app/Services/FonnteService.php` | `send(target, message)` — normalisasi `08xx→628xx`, kirim `POST https://api.fonnte.com/send` pakai header `Authorization: <token>` |
| Auth | `backend/app/Http/Controllers/AuthController.php` | `forgotPassword()` cari user by email → cek `no_tlp` → generate OTP 6 digit → simpan hash ke `password_reset_tokens` → `FonnteService::send()` → `resetPassword()` verifikasi OTP + update password |
| Config | `backend/config/services.php` | `'fonnte' => ['token'=>env('FONNTE_TOKEN'), 'url'=>env('FONNTE_URL','https://api.fonnte.com/send')]` |
| DB | `password_reset_tokens` (dari migration users), `users.no_tlp` (`2026_09_15_093626_add_no_tlp_to_users_table.php`), `users.foto_profil` | OTP disimpan hashed, expiry 60 menit |
| Routes | `backend/routes/api.php` | `POST /api/forgot-password`, `POST /api/reset-password` (public) |
| Frontend | `frontend/app/pages/forgot-password.vue` + `frontend/app/pages/reset-password.vue` + `frontend/app/pages/Login.vue` | Flow 2-step: Step1 kirim OTP ke WA, Step2 input OTP + password baru. Link "Lupa Password?" ada di Login |

**Alur:**
```
User input email di /forgot-password
  → POST /api/forgot-password {email}
    → backend: User::where(email)->first()
    → cek no_tlp kosong? → 422 "Nomor HP belum terdaftar"
    → OTP = random 0-999999 pad 6 digit
    → DB::table(password_reset_tokens).updateOrInsert(email, token=Hash::make(OTP))
    → FonnteService::send(no_tlp, "Kode OTP ...")
    → response { phone_masked, wa_sent, dev_otp? }
  → frontend pindah Step 2 (input OTP + password baru)
    → POST /api/reset-password {email, token(OTP), password, password_confirmation}
    → cek hash + expiry 60 menit → update users.password → hapus token
```

---

## 2) Setup Fonnte dari nol (5-10 menit)

### 2.1 Daftar & buat device
1. Buka https://fonnte.com → **Daftar** (pakai email).
2. Login → menu **Device** → **Add Device** (nama bebas: `PARKIR_ZIDAN`).
3. Status awal `Disconnect` — klik device → **Scan QR** pakai WhatsApp di HP yang mau jadi pengirim OTP (disarankan nomor khusus, bukan nomor pribadi utama).
4. Tunggu status jadi **Connect** (hijau). Jika lama: klik **Reconnect** → scan ulang.

### 2.2 Ambil Token
1. Di halaman device → tab **Setting** / **Token** → copy **Token** (string panjang ~ 30-50 char).
2. Catat juga **URL**: default `https://api.fonnte.com/send` (sudah benar untuk project ini).

> Biaya: Fonnte gratis trial terbatas. Untuk produksi, top-up paket (cek https://fonnte.com/pricing). Satu OTP = 1 pesan.

---

## 3) Setup Backend Laravel

### 3.1 Env
Buka `backend/.env` (copy dari `.env.example` kalau belum ada), tambahkan di bawah:

```env
FONNTE_TOKEN=isi_token_dari_fonnte_di_sini
FONNTE_URL=https://api.fonnte.com/send

# pastikan APP_URL & DB benar
APP_URL=http://localhost:8000
```

> Jangan commit token ke git. `.env` sudah di `.gitignore`.

### 3.2 Config (sudah ada — verifikasi)
`backend/config/services.php` harus ada blok:
```php
'fonnte' => [
    'token' => env('FONNTE_TOKEN', null),
    'url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),
],
```
Kalau belum, tambahkan.

### 3.3 Dependencies
```powershell
cd backend
composer install
php artisan key:generate   # jika APP_KEY masih kosong
```

### 3.4 Migration
Kolom `no_tlp` & `foto_profil` + tabel `password_reset_tokens` harus ada:
```powershell
php artisan migrate
# cek:
php artisan migrate:status
```
File yang diandalkan:
- `0001_01_01_000000_create_users_table.php` → buat `users` + `password_reset_tokens`
- `2026_09_15_093626_add_no_tlp_to_users_table.php` → `users.no_tlp nullable`
- `2026_09_15_104254_add_foto_profil_to_users_table.php`

### 3.5 Isi nomor HP user (wajib!)
OTP dikirim ke `users.no_tlp`, bukan ke tabel members. Isi lewat salah satu cara:

**Opsi A — via Tinker:**
```powershell
php artisan tinker
```
```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
// update super admin
User::where('email','admin@gmail.com')->update(['no_tlp'=>'081234567890']);
// atau buat user baru
User::create(['name'=>'Petugas 1','email'=>'petugas1@gmail.com','password'=>Hash::make('password'),'role'=>'petugas','no_tlp'=>'081234567891']);
```

**Opsi B — via API Edit Akun:**
- Login sebagai user → buka `/petugas/profil` atau `/super-admin/profil` → isi **No. HP** → Simpan (akan `POST /api/me`).

Format diterima: `08xx`, `628xx`, `+62 8xx`, `62 8xx` — service otomatis normalisasi ke `628xx`.

### 3.6 Clear cache (jika ganti .env)
```powershell
php artisan config:clear
php artisan cache:clear
```

### 3.7 Jalankan backend
```powershell
php artisan serve --host=127.0.0.1 --port=8000
# test:
curl http://localhost:8000/api/dashboard/ringkasan
```

---

## 4) Setup Frontend Nuxt

Tidak perlu env tambahan — frontend pakai `frontend/app/plugins/api.ts` dengan `baseURL: http://localhost:8000/api`.

```powershell
cd frontend
npm install
npm run dev
# buka http://localhost:3000/login → klik "Lupa Password?"
```

---

## 5) Test End-to-End

### 5.1 Test tanpa WA (DEV fallback)
Jika `FONNTE_TOKEN` kosong, backend **tidak error** — response akan berisi:
```json
{
  "status":"success",
  "phone_masked":"62812****90",
  "wa_sent": false,
  "dev_otp": "482913",
  "fonnte_error": "FONNTE_TOKEN belum diatur di .env"
}
```
Frontend akan tampilkan kotak kuning **DEV OTP** — klik **Pakai OTP ini** lalu isi password baru → Berhasil.

### 5.2 Test dengan WA asli
1. Pastikan device Fonnte **Connect** dan token sudah di `.env`.
2. Pastikan `users.no_tlp` sudah terisi nomor WA aktif.
3. Di `/forgot-password` masukkan email → **Kirim OTP ke WhatsApp**.
4. Cek WA tujuan → pesan masuk:
   ```
   🔐 PARKIR SYSTEM — Kode Reset Password
   Halo <nama>,
   Kode OTP reset password kamu: 482913
   Berlaku 60 menit. Jangan bagikan kode ini ke siapapun.
   ```
5. Masukkan OTP 6 digit di Step 2 + password baru → **Ubah Password** → login dengan password baru.

### 5.3 Test via curl (tanpa frontend)
```powershell
# 1. minta OTP
curl -X POST http://localhost:8000/api/forgot-password `
  -H "Content-Type: application/json" `
  -d '{"email":"admin@gmail.com"}'

# 2. reset (ganti 482913 dengan OTP dari WA / dev_otp)
curl -X POST http://localhost:8000/api/reset-password `
  -H "Content-Type: application/json" `
  -d '{"email":"admin@gmail.com","token":"482913","password":"password123","password_confirmation":"password123"}'
```

---

## 6) Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `Email tidak ditemukan` (404) | email typo / belum seed | Cek `select email from users;` di DB |
| `Nomor HP belum terdaftar` (422) | `no_tlp` kosong | Isi via profil atau tinker (lihat 3.5) |
| `Gagal mengirim OTP ke WhatsApp (Fonnte HTTP 401/403)` | Token salah / expired | Copy ulang token device, `php artisan config:clear` |
| `Device Disconnect` di Fonnte | WA logout / HP mati | Re-scan QR di dashboard Fonnte |
| OTP tertolak `Token/OTP tidak valid` | Salah ketik / sudah dipakai / kadaluarsa >60 menit | Minta OTP baru, pastikan 6 digit tanpa spasi |
| `FONNTE_TOKEN belum diatur` terus | `.env` belum terbaca | Pastikan edit `backend/.env` bukan `.env.example`, lalu `config:clear` |
| WA tidak masuk tapi `wa_sent:true` | Nomor tujuan salah format / WA tidak aktif | Cek `FonnteService::normalizePhone`, coba `62812...` tanpa `0` depan |
| Ingin ganti durasi OTP | Hardcode 60 menit di `AuthController::resetPassword` | Ubah `now()->diffInMinutes(...) > 60` sesuai kebutuhan |

---

## 7) Keamanan & Produksi

- **Hapus `dev_otp` di produksi:** Di `AuthController::forgotPassword`, blok `if ($isFonnteNotConfigured)` sengaja return `dev_otp` agar bisa tes tanpa WA. **Sebelum deploy, hapus key `dev_otp` dari response** (atau bungkus `if (app()->environment('local'))`).
- **Rate limit:** Tambahkan throttle di `routes/api.php` jika perlu: `Route::post('/forgot-password', ...)->middleware('throttle:5,1')` (5x per menit).
- **OTP hash:** Disimpan pakai `Hash::make`, tidak plain — aman meski DB bocor.
- **Expiry:** 60 menit via `diffInMinutes`. Token dihapus setelah sukses reset.
- **Jangan share token Fonnte** — treat seperti password.

---

## 8) Referensi file penting

- `backend/app/Services/FonnteService.php` — kirim & normalisasi nomor
- `backend/app/Http/Controllers/AuthController.php` — `forgotPassword`, `resetPassword`
- `backend/config/services.php` — `fonnte.token/url`
- `backend/routes/api.php` — `POST /forgot-password`, `POST /reset-password`
- `frontend/app/pages/forgot-password.vue` — UI 3-step (email → OTP+password → done)
- `frontend/app/pages/reset-password.vue` — halaman reset alternatif
- `frontend/app/pages/Login.vue` — link "Lupa Password?"

---

## 9) Checklist cepat

- [ ] Device Fonnte Connect + token di-copy
- [ ] `backend/.env` isi `FONNTE_TOKEN` + `FONNTE_URL`
- [ ] `composer install` + `php artisan migrate` + `config:clear`
- [ ] `users.no_tlp` terisi untuk semua akun yang mau test
- [ ] `php artisan serve` + `npm run dev` jalan
- [ ] Test forgot → dapat OTP di WA → reset → login dengan password baru berhasil

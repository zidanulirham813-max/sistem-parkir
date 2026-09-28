<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PetugasController extends Controller
{
    /**
     * Normalisasi no_tlp ke format 08... (tetap 08) agar 0812, 62812, +62812 dianggap sama.
     * Disimpan tetap 08... sesuai request. FonnteService otomatis convert ke 62... saat kirim WA.
     * Return null jika kosong.
     */
    private function normalizeNoTlp(?string $raw): ?string
    {
        if ($raw === null) return null;
        $trimmed = trim($raw);
        if ($trimmed === '') return null;
        $p = preg_replace('/[^0-9+]/', '', $trimmed);
        if (str_starts_with($p, '+')) $p = substr($p, 1);
        if (str_starts_with($p, '62')) {
            $p = '0' . substr($p, 2);
        } elseif (!str_starts_with($p, '0')) {
            $p = '0' . $p;
        }
        return $p;
    }

    public function index()
    {
        $petugas = User::orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $petugas]);
    }

    public function store(Request $request)
    {
        // super_admin hanya boleh 1 di seluruh sistem
        $requestedRole = $request->input('role', 'petugas');
        if ($requestedRole === 'super_admin' && User::where('role', 'super_admin')->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Super Admin sudah ada — hanya 1 Super Admin yang diperbolehkan.',
            ], 422);
        }

        // Normalisasi dulu agar cek duplikat pakai format yang sama
        $normalized = $this->normalizeNoTlp($request->input('no_tlp'));
        $request->merge(['no_tlp' => $normalized]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'no_tlp' => 'nullable|string|max:20|unique:users,no_tlp',
            'password' => 'required|string|min:6',
            'role' => 'nullable|in:petugas',
        ], [
            'no_tlp.unique' => 'Nomor HP sudah digunakan oleh akun lain.',
        ]);

        $petugas = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'no_tlp' => $normalized,
            'password' => Hash::make($request->password),
            'role' => 'petugas',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Petugas berhasil ditambahkan', 'data' => $petugas], 201);
    }

    public function show($id)
    {
        $petugas = User::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $petugas]);
    }

    public function update(Request $request, $id)
    {
        $petugas = User::findOrFail($id);

        // super_admin hanya boleh 1 — cegah ubah role jadi super_admin jika sudah ada yang lain
        $newRole = $request->input('role');
        if ($newRole === 'super_admin' && $petugas->role !== 'super_admin' && User::where('role', 'super_admin')->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal: Super Admin sudah ada — hanya 1 yang diperbolehkan.',
            ], 422);
        }

        $normalized = $this->normalizeNoTlp($request->input('no_tlp'));
        $request->merge(['no_tlp' => $normalized]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'no_tlp' => 'nullable|string|max:20|unique:users,no_tlp,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|in:petugas,super_admin',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'no_tlp.unique' => 'Nomor HP sudah digunakan oleh akun lain.',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'no_tlp' => $normalized,
        ];
        if ($request->filled('role')) $data['role'] = $request->role;
        if ($request->filled('password')) $data['password'] = Hash::make($request->password);

        // handle foto_profil upload
        if ($request->hasFile('foto_profil')) {
            if ($petugas->foto_profil && !str_starts_with($petugas->foto_profil, 'http')) {
                Storage::disk('public')->delete($petugas->foto_profil);
            }
            $path = $request->file('foto_profil')->store('foto_profil', 'public');
            $data['foto_profil'] = $path;
        }

        $petugas->update($data);

        return response()->json(['status' => 'success', 'message' => 'Petugas berhasil diperbarui', 'data' => $petugas]);
    }

    public function destroy($id)
    {
        $petugas = User::findOrFail($id);
        if ($petugas->role === 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Akun Super Admin tidak dapat dihapus.'], 403);
        }
        if ($petugas->foto_profil && !str_starts_with($petugas->foto_profil, 'http')) {
            Storage::disk('public')->delete($petugas->foto_profil);
        }
        $petugas->delete();
        return response()->json(['status' => 'success', 'message' => 'Petugas berhasil dihapus']);
    }

    // === Profil petugas yang sedang login (pakai token) ===
    public function me(Request $request)
    {
        return response()->json(['status' => 'success', 'data' => $request->user()]);
    }

    public function updateMe(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $normalized = $this->normalizeNoTlp($request->input('no_tlp'));
        $request->merge(['no_tlp' => $normalized]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'no_tlp' => 'nullable|string|max:20|unique:users,no_tlp,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'no_tlp.unique' => 'Nomor HP sudah digunakan oleh akun lain.',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'no_tlp' => $normalized,
        ];
        if ($request->filled('password')) $data['password'] = Hash::make($request->password);

        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && !str_starts_with($user->foto_profil, 'http')) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $path = $request->file('foto_profil')->store('foto_profil', 'public');
            $data['foto_profil'] = $path;
        }

        $user->update($data);
        $user->refresh();

        return response()->json(['status' => 'success', 'message' => 'Profil berhasil diperbarui', 'data' => $user]);
    }
}

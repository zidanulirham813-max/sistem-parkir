<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Cek role user yang sudah login (via sanctum).
     * Pakai: ->middleware('role:super_admin') atau ->middleware('role:super_admin,petugas')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized — silakan login.',
            ], 401);
        }

        // Normalisasi role biar tidak case-sensitive
        $userRole = strtolower(trim((string) ($user->role ?? '')));
        $allowed = array_map(fn($r) => strtolower(trim($r)), $roles);

        // Alias: 'admin' dianggap super_admin
        if ($userRole === 'admin') $userRole = 'super_admin';
        $allowed = array_map(fn($r) => $r === 'admin' ? 'super_admin' : $r, $allowed);

        if (!in_array($userRole, $allowed, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden — role ' . $user->role . ' tidak diizinkan mengakses resource ini.',
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Periksa apakah pengguna terautentikasi
        if (!Auth::check()) {
            // Jika tidak terautentikasi, kembalikan response 401 Unauthorized
            // Ini penting untuk API agar tidak redirect ke halaman login HTML
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Periksa apakah pengguna memiliki peran 'admin'
        // Asumsi: model User Anda memiliki kolom 'role'
        if ($request->user()->role !== 'admin') {
            // Jika pengguna bukan admin, kembalikan response 403 Forbidden
            return response()->json(['message' => 'Forbidden: Anda tidak memiliki akses admin.'], 403);
        }

        // Jika pengguna terautentikasi dan adalah admin, lanjutkan request
        return $next($request);
    }
}

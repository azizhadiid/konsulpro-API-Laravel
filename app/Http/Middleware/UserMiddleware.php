<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($request->user()->role !== 'user') {
            return response()->json(['message' => 'Forbidden: Anda bukan user biasa.'], 403);
        }

        return $next($request);
    }
}

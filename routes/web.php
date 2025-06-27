<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/api/ping', function () {
    return response()->json([
        'message' => 'API Laravel aktif 🚀'
    ]);
});

Route::get('/password/reset/{token}', function ($token) {
    return redirect(config('app.frontend_url') . '/auth/reset?token=' . $token . '&email=' . request('email'));
})->name('password.reset');

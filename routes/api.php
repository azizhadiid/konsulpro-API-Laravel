<?php

use App\Http\Controllers\ArtikelController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\KonsultasiController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('/forgot-password', [AuthController::class, 'forgot']);
Route::post('/reset-password', [AuthController::class, 'reset']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::post('/profile', [UserProfileController::class, 'update']);

    Route::get('/artikels', [ArtikelController::class, 'index']);
    Route::post('/artikels', [ArtikelController::class, 'store']);
    Route::get('/artikels/{id}', [ArtikelController::class, 'show']); // Untuk mengambil data 1 artikel
    Route::put('/artikels/{id}', [ArtikelController::class, 'update']); // Untuk update, menggunakan POST dengan _method=PUT
    Route::delete('/artikels/{id}', [ArtikelController::class, 'destroy']); // Untuk menghapus artikel
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/send-contact-email', [ContactController::class, 'sendContactEmail']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/konsultasi', [KonsultasiController::class, 'index']);
    Route::post('/konsultasi', [KonsultasiController::class, 'store']);
});

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out successfully']);
});

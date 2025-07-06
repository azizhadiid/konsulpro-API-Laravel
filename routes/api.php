<?php

use App\Http\Controllers\ArtikelController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('/forgot-password', [AuthController::class, 'forgot']);
Route::post('/reset-password', [AuthController::class, 'reset']);

Route::post('/midtrans-notification', [ConsultationController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::post('/profile', [UserProfileController::class, 'update']);

    Route::get('/artikels', [ArtikelController::class, 'index']);
    Route::post('/artikels', [ArtikelController::class, 'store']);
    Route::get('/artikels/{id}', [ArtikelController::class, 'show']); // Untuk mengambil data 1 artikel
    Route::put('/artikels/{id}', [ArtikelController::class, 'update']); // Untuk update, menggunakan POST dengan _method=PUT
    Route::delete('/artikels/{id}', [ArtikelController::class, 'destroy']); // Untuk menghapus artikel

    Route::get('/user', [AuthController::class, 'user']);

    // Konsultasi API Resources
    // 'store' untuk membuat permohonan baru
    // 'index' untuk melihat daftar permohonan user
    // 'show' untuk melihat detail permohonan
    Route::apiResource('consultations', ConsultationController::class)->only(['index', 'store', 'show']);

    // Jika Anda ingin menambahkan endpoint untuk mendapatkan konsultasi berdasarkan midtrans_transaction_id
    // Ini berguna untuk halaman finish/error/pending di frontend
    Route::prefix('consultations')->group(function () {
        Route::post('/', [ConsultationController::class, 'store']);
        Route::get('/', [ConsultationController::class, 'getUserConsultations']);
        Route::get('/{orderId}/status', [ConsultationController::class, 'checkStatus']);
    });
    // Anda perlu menambahkan method showByMidtransId di ConsultationController
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/send-contact-email', [ContactController::class, 'sendContactEmail']);
});

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out successfully']);
});

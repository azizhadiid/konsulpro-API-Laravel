<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ArtikelController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ConsultationController;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgot']);
Route::post('/reset-password', [AuthController::class, 'reset']);
Route::get('/top-ratings', [RatingController::class, 'getHighRatedTestimonials']);


// System
Route::middleware('auth:sanctum')->group(function () {
    // Admin dan user
    Route::get('/artikels', [ArtikelController::class, 'index']);
    Route::get('/artikels/{id}', [ArtikelController::class, 'show']);

    Route::middleware('user')->group(function () {
        // User/Members
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [UserProfileController::class, 'show']);
        Route::post('/profile', [UserProfileController::class, 'update']);
        // Route::post('/consultation', [ConsultationController::class, 'create']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        // Kontak untuk members
        Route::post('/send-contact-email', [ContactController::class, 'sendContactEmail']);
        // Untuk Melakukan Konsultassi Members
        Route::post('/payment-token', [ConsultationController::class, 'getSnapToken']);
        Route::post('/consultation/save', [ConsultationController::class, 'saveAfterPayment']);
        Route::get('/consultation/history', [ConsultationController::class, 'history']);
        // Rating
        Route::post('/ratings', [RatingController::class, 'store']); // Mengirim rating baru
        Route::get('/ratings', [RatingController::class, 'index']); // Mengambil daftar rating dan statistik
    });

    Route::middleware('admin')->group(function () {
        // Admin
        Route::get('/consultation/verifikasi', [ConsultationController::class, 'getAdminConsultations']);
        Route::put('/consultations/{id}/status', [ConsultationController::class, 'updateConsultationStatus']);
        Route::get('/dashboard', [DashboardController::class, 'getAdminDashboardData']);
        Route::get('/dashboard/generate-report', [DashboardController::class, 'generateReport']);
        // Artikel
        Route::post('/artikels', [ArtikelController::class, 'store']);
        Route::put('/artikels/{id}', [ArtikelController::class, 'update']); // Untuk update, menggunakan POST dengan _method=PUT
        Route::delete('/artikels/{id}', [ArtikelController::class, 'destroy']); // Untuk menghapus artikel
    });
});

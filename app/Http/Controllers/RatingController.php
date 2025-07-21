<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreRatingRequest;

class RatingController extends Controller
{
    public function store(StoreRatingRequest $request)
    {
        $user = $request->user();

        // Cek apakah user sudah pernah memberikan rating untuk layanan ini
        $existingRating = Rating::where('user_id', $user->id)
            ->where('service_name', $request->service_name)
            ->first();

        if ($existingRating) {
            return response()->json([
                'message' => 'Anda sudah memberikan review untuk layanan ini.',
                'rating' => $existingRating
            ], 409); // Conflict
        }

        try {
            $rating = Rating::create([
                'user_id' => $user->id,
                'service_name' => $request->service_name,
                'rating' => $request->rating,
                'review' => $request->review,
            ]);

            return response()->json([
                'message' => 'Review Anda berhasil disimpan!',
                'rating' => $rating
            ], 201); // Created
        } catch (\Exception $e) {
            Log::error('Error storing rating:', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Gagal menyimpan review. Silakan coba lagi.'], 500);
        }
    }

    public function index()
    {
        try {
            // Ambil 3 testimoni terbaru
            $testimonials = Rating::orderBy('created_at', 'desc')
                ->with('user:id,name') // Ini akan mengambil id dan nama dari tabel users
                ->limit(3)
                ->get()
                ->map(function ($rating) {
                    return [
                        'id' => $rating->id,
                        'name' => $rating->user->name ?? 'Anonim', // Menggunakan nama dari relasi user
                        'service' => $rating->service_name,
                        'rating' => $rating->rating,
                        'review' => $rating->review,
                        'date' => $rating->created_at->diffForHumans(), // Format tanggal relatif
                    ];
                });

            // Hitung statistik
            $totalClients = User::where('role', 'user')->count(); // Total klien bisa dihitung dari jumlah user
            $totalReviews = Rating::count();
            $averageRating = Rating::avg('rating');
            // Tingkat kepuasan bisa dihitung dari total review dibagi total klien (sederhana)
            $satisfactionRate = ($totalClients > 0) ? round(($totalReviews / $totalClients) * 100) : 0;

            $stats = [
                'total_clients' => $totalClients,
                'average_rating' => number_format($averageRating, 1) . '/5', // Format 4.9/5
                'satisfaction_rate' => $satisfactionRate . '%',
                'total_reviews' => $totalReviews,
            ];

            return response()->json([
                'message' => 'Data rating dan statistik berhasil diambil.',
                'testimonials' => $testimonials,
                'stats' => $stats,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching ratings and stats:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Gagal mengambil data rating.'], 500);
        }
    }
}

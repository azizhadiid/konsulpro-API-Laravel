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

    public function getHighRatedTestimonials()
    {
        try {
            // --- Mengambil Testimoni ---
            $testimonials = Rating::where('rating', '>=', 4)
                ->orderBy('created_at', 'desc')
                ->with(['user' => function ($query) {
                    $query->select('id', 'name')->with('profile');
                }])
                ->limit(3)
                ->get()
                ->map(function ($rating) {
                    $userName = $rating->user->name ?? 'Anonim';
                    $userProfession = $rating->user->profile->pekerjaan ?? null;
                    $userPhotoFileName = $rating->user->profile->foto ?? null;

                    $userPhotoUrl = null;
                    if ($userPhotoFileName) {
                        $userPhotoUrl = asset('img/profile/user/' . $userPhotoFileName);
                    }

                    return [
                        'id' => $rating->id,
                        'name' => $userName,
                        'service' => $rating->service_name,
                        'rating' => $rating->rating,
                        'review' => $rating->review,
                        'date' => $rating->created_at->diffForHumans(),
                        'user_profile' => [
                            'pekerjaan' => $userProfession,
                            'foto' => $userPhotoFileName,
                            'foto_url' => $userPhotoUrl,
                        ]
                    ];
                });

            // --- Menghitung Statistik Rating Keseluruhan ---
            $totalReviews = Rating::count();
            $averageRating = Rating::avg('rating');

            // Untuk 'total_clients' Anda bisa menghitung user yang memiliki rating unik
            // Ini akan lebih akurat jika user_id di tabel rating benar-benar unik per klien yang memberikan rating
            $totalClients = Rating::distinct('user_id')->count('user_id');

            // Format sesuai kebutuhan frontend
            $formattedAverageRating = number_format($averageRating, 1) . '/5'; // e.g., "4.9/5"
            // Asumsi satisfaction_rate = (average_rating / 5) * 100%
            $satisfactionRate = number_format(($averageRating / 5) * 100, 0) . '%'; // e.g., "98%"

            $ratingStats = [
                'total_clients' => $totalClients,
                'average_rating' => $formattedAverageRating,
                'satisfaction_rate' => $satisfactionRate,
                'total_reviews' => $totalReviews,
            ];

            return response()->json([
                'message' => 'Top high-rated testimonials and overall stats retrieved successfully.',
                'testimonials' => $testimonials,
                'stats' => $ratingStats, // Tambahkan ini ke respons
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching high-rated testimonials and stats:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Gagal mengambil testimoni dan statistik terbaik.'], 500);
        }
    }
}

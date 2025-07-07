<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Consultation; // Pastikan model Consultation ada
use Carbon\Carbon; // Impor Carbon untuk formatting tanggal

class DashboardController extends Controller
{
    /**
     * Mengambil data dashboard untuk admin.
     * Pastikan hanya diakses oleh admin.
     */
    public function getAdminDashboardData(Request $request)
    {
        try {
            // Verifikasi Admin (sesuaikan dengan sistem otorisasi Anda)
            // Contoh:
            // if (Auth::user()->role !== 'admin') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Forbidden: You do not have admin access.'
            //     ], 403);
            // }

            // --- Statistik Konsultasi (Tidak Berubah) ---
            $totalConsultations = Consultation::count();
            $pendingConsultations = Consultation::where('status', 'pending')->count();
            $paidConsultations = Consultation::where('status', 'paid')->count();
            $completedConsultations = Consultation::where('status', 'completed')->count();
            $cancelledConsultations = Consultation::where('status', 'cancelled')->count();

            // --- Statistik Artikel (Disesuaikan dengan model Artikel Anda) ---
            $totalArticles = Artikel::count();

            // Logika untuk menentukan draft dan published berdasarkan `tanggal_publish`
            $publishedArticles = Artikel::whereNotNull('tanggal_publish')
                ->where('tanggal_publish', '<=', Carbon::now())
                ->count();

            $draftArticles = Artikel::whereNull('tanggal_publish')
                ->orWhere('tanggal_publish', '>', Carbon::now())
                ->count();


            // --- Konsultasi Terbaru (Tidak Berubah) ---
            $latestConsultations = Consultation::with('user')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($consultation) {
                    return [
                        'id' => $consultation->id,
                        'user_name' => $consultation->user ? $consultation->user->name : 'N/A',
                        'topik' => $consultation->title, // Asumsi ini kolom judul konsultasi
                        'status' => $consultation->status,
                        'created_at_formatted' => Carbon::parse($consultation->created_at)->diffForHumans(),
                    ];
                });

            // --- Artikel Terbaru (Disesuaikan dengan model Artikel Anda) ---
            $latestArticles = Artikel::with('user') // Jika ingin menampilkan nama penulis artikel
                ->orderBy('created_at', 'desc') // Menggunakan created_at untuk urutan terbaru
                ->take(5)
                ->get()
                ->map(function ($artikel) { // Variabel diubah menjadi $artikel
                    // Tentukan status berdasarkan `tanggal_publish`
                    $status = ($artikel->tanggal_publish && Carbon::parse($artikel->tanggal_publish)->lte(Carbon::now())) ? 'published' : 'draft';

                    return [
                        'id' => $artikel->id,
                        'title' => $artikel->judul, // Menggunakan kolom 'judul' dari model Artikel
                        'user_name' => $artikel->user ? $artikel->user->name : 'N/A', // Nama penulis artikel
                        'status' => $status, // Status yang ditentukan berdasarkan logika tanggal_publish
                        'created_at_formatted' => Carbon::parse($artikel->created_at)->diffForHumans(), // Menggunakan created_at
                    ];
                });

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_consultations' => $totalConsultations,
                    'pending_consultations' => $pendingConsultations,
                    'paid_consultations' => $paidConsultations,
                    'completed_consultations' => $completedConsultations,
                    'cancelled_consultations' => $cancelledConsultations,
                    'total_articles' => $totalArticles,
                    'draft_articles' => $draftArticles,
                    'published_articles' => $publishedArticles,
                ],
                'latest_consultations' => $latestConsultations,
                'latest_articles' => $latestArticles,
                'message' => 'Data dashboard berhasil diambil.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}

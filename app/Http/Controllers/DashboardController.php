<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Consultation;
use App\Models\Artikel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response; // Import Response facade
use Illuminate\Support\Facades\Auth; // Import Auth facade

class DashboardController extends Controller
{
    /**
     * Get data for the admin dashboard.
     * Includes various statistics, latest consultations, and latest articles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdminDashboardData(Request $request)
    {
        try {
            // --- LANGKAH DEBUGGING KRUSIAL: PERIKSA PENGGUNA TERAUTENTIKASI ---
            // Saat Anda mengakses halaman dashboard admin dari frontend:
            // 1. Ini akan menghentikan eksekusi Laravel.
            // 2. Browser Anda akan menampilkan halaman debug Laravel dengan detail objek pengguna.
            // 3. CARI ATRIBUT 'role' di objek pengguna tersebut.
            //    Jika 'role' BUKAN 'admin', maka itu penyebab 403 Anda.
            // Hapus atau komentari baris ini setelah debugging selesai.
            // dd($request->user());

            // --- Authorization Check: Pastikan pengguna terautentikasi memiliki peran 'admin' ---
            // Ini adalah bagian KRUSIAL yang harus diaktifkan dan benar.
            if (!$request->user() || $request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: Anda tidak memiliki akses admin.'
                ], 403);
            }

            // --- Consultation Statistics ---
            $totalConsultations = Consultation::count();
            $pendingConsultations = Consultation::where('status', 'pending')->count();
            $paidConsultations = Consultation::where('status', 'paid')->count();
            $completedConsultations = Consultation::where('status', 'completed')->count();
            $cancelledConsultations = Consultation::where('status', 'cancelled')->count();

            // --- Article Statistics ---
            $totalArticles = Artikel::count();
            $publishedArticles = Artikel::whereNotNull('tanggal_publish')
                ->where('tanggal_publish', '<=', Carbon::now())
                ->count();
            $draftArticles = Artikel::whereNull('tanggal_publish')
                ->orWhere('tanggal_publish', '>', Carbon::now())
                ->count();

            // --- User Statistics ---
            $totalUsers = User::where('role', 'user')->count();

            // --- Latest Consultations (Limit to 5) ---
            $latestConsultations = Consultation::with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($consultation) {
                    return [
                        'id' => $consultation->id,
                        'user_name' => $consultation->user ? $consultation->user->name : 'N/A',
                        'topik' => $consultation->title,
                        'status' => $consultation->status,
                        'created_at_formatted' => Carbon::parse($consultation->created_at)->diffForHumans(),
                    ];
                });

            // --- Latest Articles (Limit to 5) ---
            $latestArticles = Artikel::with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($artikel) {
                    $status = ($artikel->tanggal_publish && Carbon::parse($artikel->tanggal_publish)->lte(Carbon::now())) ? 'published' : 'draft';
                    return [
                        'id' => $artikel->id,
                        'title' => $artikel->judul,
                        'user_name' => $artikel->user ? $artikel->user->name : 'N/A',
                        'status' => $status,
                        'created_at_formatted' => Carbon::parse($artikel->created_at)->diffForHumans(),
                    ];
                });

            // --- Return Dashboard Data ---
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
                    'total_users' => $totalUsers,
                ],
                'latest_consultations' => $latestConsultations,
                'latest_articles' => $latestArticles,
                'message' => 'Data dashboard berhasil diambil.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching admin dashboard data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateReport(Request $request)
    {
        try {
            // --- Authorization Check: Pastikan pengguna terautentikasi memiliki peran 'admin' ---
            if (!$request->user() || $request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: Anda tidak memiliki akses admin untuk membuat laporan.'
                ], 403);
            }

            // Simulate report generation process
            // For a real PDF, you'd use a library like Dompdf or Snappy.
            // Here, we'll generate a simple CSV and return it as a download.
            $reportData = [
                ['Report Type', 'Date Generated', 'Total Consultations', 'Total Users'],
                ['Summary Report', Carbon::now()->toDateTimeString(), Consultation::count(), User::count()]
            ];

            $fileName = 'dashboard_report_' . Carbon::now()->format('Ymd_His') . '.csv';
            $filePath = storage_path('app/public/' . $fileName); // Simpan ke storage/app/public

            $file = fopen($filePath, 'w');
            if ($file) {
                foreach ($reportData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            } else {
                throw new \Exception("Could not open file for writing: " . $filePath);
            }


            // Langsung kembalikan file untuk diunduh
            return Response::download($filePath, $fileName, [
                'Content-Type' => 'text/csv', // Ubah ke 'application/pdf' jika Anda benar-benar membuat PDF
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ])->deleteFileAfterSend(true); // Hapus file setelah dikirim

        } catch (\Exception $e) {
            Log::error('Error generating report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat laporan: ' . $e->getMessage()
            ], 500);
        }
    }
}

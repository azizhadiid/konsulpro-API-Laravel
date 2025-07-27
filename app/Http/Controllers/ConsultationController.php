<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ConsultationController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function history(Request $request)
    {
        try {
            // Ambil user yang sedang login
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Ambil riwayat konsultasi user
            $consultations = Consultation::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($consultation) {
                    return [
                        'id' => $consultation->id,
                        'topik' => $consultation->title,
                        'kategori' => $consultation->category,
                        'deskripsi' => $consultation->description,
                        'durasi' => $consultation->duration,
                        'harga' => 'Rp ' . number_format($consultation->total_price, 0, ',', '.'),
                        'status' => $consultation->status, // Pastikan ini mengembalikan string status yang benar ('completed', 'pending', 'paid', 'cancelled')
                        'tanggalBayar' => $consultation->created_at->format('Y-m-d'),
                        'tanggalBayarFormatted' => $consultation->created_at->format('d M Y'), // Format untuk tampilan frontend
                        'created_at' => $consultation->created_at,
                        'updated_at' => $consultation->updated_at,
                    ];
                });

            // Hitung statistik
            $stats = [
                'total' => $consultations->count(),
                'completed' => $consultations->where('status', 'completed')->count(),
                'pending' => $consultations->where('status', 'pending')->count(),
                'paid' => $consultations->where('status', 'paid')->count(), // Tambahkan hitungan untuk status 'paid'
                'cancelled' => $consultations->where('status', 'cancelled')->count(), // Pastikan nama status konsisten
            ];

            return response()->json([
                'success' => true,
                'data' => $consultations,
                'stats' => $stats,
                'user_info' => [ // PASTIKAN BAGIAN INI ADA DI KODE ANDA
                    'id' => $user->id,
                    'name' => $user->name, // Ini yang akan mengisi user?.name di frontend
                    'email' => $user->email,
                    // Tambahkan properti user lain yang relevan jika diperlukan oleh frontend
                ],
                'message' => 'Data berhasil diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'user_info' => [ // Tambahkan ini
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    // Tambahkan data user lain yang relevan
                ],
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAdminConsultations(Request $request)
    {
        try {
            // --- Authorization Check: Pastikan pengguna terautentikasi memiliki peran 'admin' ---
            if (!$request->user() || $request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: Anda tidak memiliki akses admin untuk melihat konsultasi.'
                ], 403);
            }

            // Dapatkan parameter dari request
            $perPage = $request->input('per_page', 10); // Default 10 item per halaman
            $search = $request->input('search'); // Keyword pencarian
            $statusFilter = $request->input('status', 'all'); // Filter status, default 'all'

            // Mulai query Consultation dengan eager loading user
            $query = Consultation::with('user');

            // Terapkan filter pencarian jika ada
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%') // Cari di topik
                        ->orWhere('category', 'like', '%' . $search . '%') // Cari di kategori
                        ->orWhereHas('user', function ($userQuery) use ($search) { // Cari di nama atau email user
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            }

            // Terapkan filter status jika bukan 'all'
            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            // Urutkan berdasarkan tanggal dibuat terbaru dan terapkan paginasi
            $consultations = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Transformasi data untuk frontend
            $transformedConsultations = $consultations->getCollection()->map(function ($consultation) {
                return [
                    'id' => $consultation->id,
                    'user_id' => $consultation->user_id,
                    'user_name' => $consultation->user ? $consultation->user->name : 'N/A',
                    'user_email' => $consultation->user ? $consultation->user->email : 'N/A',
                    'topik' => $consultation->title,
                    'kategori' => $consultation->category,
                    'deskripsi' => $consultation->description,
                    'durasi' => $consultation->duration,
                    'harga' => 'Rp ' . number_format($consultation->total_price, 0, ',', '.'),
                    'total_price' => $consultation->total_price,
                    'status' => $consultation->status,
                    'tanggal_konsultasi_formatted' => $consultation->consultation_date ? Carbon::parse($consultation->consultation_date)->isoFormat('D MMMM YYYY') : 'Belum Ditetapkan',
                    'created_at_formatted' => $consultation->created_at->isoFormat('D MMMM YYYY, HH:mm'),
                ];
            });

            // Hitung statistik untuk admin dari query yang sudah difilter (opsional, bisa juga dari semua data)
            // Untuk statistik yang akurat berdasarkan filter yang diterapkan, Anda bisa menghitung ulang:
            $stats = [
                'total' => Consultation::count(), // Total semua konsultasi (tanpa filter)
                'pending' => Consultation::where('status', 'pending')->count(),
                'paid' => Consultation::where('status', 'paid')->count(),
                'completed' => Consultation::where('status', 'completed')->count(),
                'cancelled' => Consultation::where('status', 'cancelled')->count(),
            ];
            // Catatan: Jika Anda ingin statistik yang hanya mencerminkan hasil filter,
            // gunakan $query->clone()->where('status', 'status_value')->count();

            return response()->json([
                'success' => true,
                'message' => 'Data konsultasi admin berhasil diambil.',
                'data' => $transformedConsultations,
                'stats' => $stats,
                'current_page' => $consultations->currentPage(),
                'last_page' => $consultations->lastPage(),
                'per_page' => $consultations->perPage(),
                'total' => $consultations->total(),
                'from' => $consultations->firstItem(),
                'to' => $consultations->lastItem(),
                // 'links' => $consultations->links(), // Laravel pagination links, tidak selalu dibutuhkan di API
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching admin consultations:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data konsultasi admin: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateConsultationStatus(Request $request, $id)
    {
        try {
            // --- Authorization Check: Pastikan pengguna terautentikasi memiliki peran 'admin' ---
            if (!$request->user() || $request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: Anda tidak memiliki akses admin untuk memperbarui status konsultasi.'
                ], 403);
            }

            $request->validate([
                'status' => ['required', 'string', 'in:pending,paid,completed,cancelled'],
            ]);

            $consultation = Consultation::findOrFail($id);

            // Jika status tidak berubah, tidak perlu update
            if ($consultation->status === $request->status) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status konsultasi sudah sama. Tidak ada perubahan.'
                ]);
            }

            DB::beginTransaction(); // Mulai transaksi database

            $consultation->status = $request->status;
            $consultation->save();

            // Logika tambahan sesuai perubahan status:
            // - Jika status berubah menjadi 'paid', mungkin kirim notifikasi ke user.
            // - Jika status berubah menjadi 'completed', mungkin update riwayat selesai, dll.
            // - Jika status dibatalkan, mungkin ada proses refund (jika sistem mendukung).

            DB::commit(); // Commit transaksi

            return response()->json([
                'success' => true,
                'message' => 'Status konsultasi berhasil diperbarui menjadi ' . $request->status,
                'consultation' => $consultation->fresh() // Mengembalikan data konsultasi terbaru
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation Error updating consultation status:', ['errors' => $e->errors(), 'request' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Consultation not found:', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Konsultasi tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating consultation status:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'consultation_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat memperbarui status konsultasi: ' . $e->getMessage()
            ], 500);
        }
    }



    // public function create(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required|string',
    //         'description' => 'required|string',
    //         'category' => 'required|string',
    //         'duration' => 'required|integer|min:1',
    //     ]);

    //     $user = Auth::user();
    //     $totalPrice = $request->duration * 500000;

    //     $consultation = Consultation::create([
    //         'user_id' => $user->id,
    //         'title' => $request->title,
    //         'description' => $request->description,
    //         'category' => $request->category,
    //         'duration' => $request->duration,
    //         'total_price' => $totalPrice,
    //         'status' => 'pending',
    //     ]);

    //     $params = [
    //         'transaction_details' => [
    //             'order_id' => 'CONS-' . $consultation->id . '-' . time(),
    //             'gross_amount' => $totalPrice,
    //         ],
    //         'customer_details' => [
    //             'first_name' => $user->name,
    //             'email' => $user->email,
    //         ],
    //         'item_details' => [[
    //             'id' => $consultation->id,
    //             'price' => 500000,
    //             'quantity' => $request->duration,
    //             'name' => $request->title,
    //         ]]
    //     ];

    //     $snapToken = Snap::getSnapToken($params);

    //     return response()->json([
    //         'message' => 'Konsultasi berhasil dibuat',
    //         'snap_token' => $snapToken,
    //         'consultation' => $consultation
    //     ]);
    // }

    // Controller Posisi 1
    public function getSnapToken(Request $request)
    {
        $user = auth()->user();
        $total = $request->duration * 500000;

        $params = [
            'transaction_details' => [
                'order_id' => 'CONS-' . time(),
                'gross_amount' => $total,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ]
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);

        return response()->json(['snap_token' => $snapToken]);
    }

    public function saveAfterPayment(Request $request)
    {
        $user = auth()->user();

        $consultation = \App\Models\Consultation::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'duration' => $request->duration,
            'total_price' => $request->duration * 500000,
            'status' => $request->status ?? 'pending',
        ]);

        return response()->json(['message' => 'Data konsultasi disimpan', 'consultation' => $consultation], 201);
    }
}

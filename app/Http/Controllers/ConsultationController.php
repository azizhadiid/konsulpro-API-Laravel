<?php

namespace App\Http\Controllers;

use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
            // Verifikasi Admin (asumsi role 'admin' atau kolom 'is_admin' di tabel users)
            // Anda perlu menyesuaikan ini dengan sistem otentikasi dan otorisasi admin Anda.
            // Contoh sederhana:
            // if (Auth::user()->role !== 'admin') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Forbidden: You do not have admin access.'
            //     ], 403);
            // }

            // Ambil semua konsultasi dengan eager loading user
            $consultations = Consultation::with('user') // Memuat relasi user
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($consultation) {
                    return [
                        'id' => $consultation->id,
                        'user_name' => $consultation->user ? $consultation->user->name : 'N/A', // Nama user
                        'user_email' => $consultation->user ? $consultation->user->email : 'N/A', // Email user
                        'topik' => $consultation->title,
                        'kategori' => $consultation->category,
                        'deskripsi' => $consultation->description,
                        'durasi' => $consultation->duration,
                        'harga' => 'Rp ' . number_format($consultation->total_price, 0, ',', '.'),
                        'total_price' => $consultation->total_price, // Harga asli
                        'status' => $consultation->status,
                        'tanggal_konsultasi_formatted' => $consultation->consultation_date ? \Carbon\Carbon::parse($consultation->consultation_date)->isoFormat('D MMMM YYYY') : 'Belum Ditetapkan',
                        'created_at_formatted' => $consultation->created_at->isoFormat('D MMMM YYYY, HH:mm'), // Menggunakan Carbon untuk format lebih baik
                    ];
                });

            // Hitung statistik untuk admin
            $stats = [
                'total' => $consultations->count(),
                'pending' => $consultations->where('status', 'pending')->count(),
                'paid' => $consultations->where('status', 'paid')->count(),
                'completed' => $consultations->where('status', 'completed')->count(),
                'cancelled' => $consultations->where('status', 'cancelled')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $consultations,
                'stats' => $stats,
                'message' => 'Data konsultasi admin berhasil diambil.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data konsultasi admin: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateConsultationStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,paid,completed,cancelled'],
        ]);

        try {
            // Verifikasi Admin (seperti di atas)
            // if (Auth::user()->role !== 'admin') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Forbidden: You do not have admin access.'
            //     ], 403);
            // }

            $consultation = Consultation::findOrFail($id);

            // Jika status tidak berubah, tidak perlu update
            if ($consultation->status === $request->status) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status konsultasi sudah sama. Tidak ada perubahan.'
                ]);
            }

            // Mulai transaksi database
            DB::beginTransaction();

            $consultation->status = $request->status;
            $consultation->save();

            // Logika tambahan sesuai perubahan status:
            // - Jika status berubah menjadi 'paid', mungkin kirim notifikasi ke user.
            // - Jika status berubah menjadi 'completed', mungkin update riwayat selesai, dll.
            // - Jika status dibatalkan, mungkin ada proses refund (jika sistem mendukung).

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status konsultasi berhasil diperbarui menjadi ' . $request->status
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Konsultasi tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui status konsultasi: ' . $e->getMessage()
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

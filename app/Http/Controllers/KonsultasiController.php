<?php

namespace App\Http\Controllers;

use App\Models\Konsultasi;
use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Http\Request;

class KonsultasiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $request->profile();
        $konsultasiList = $user->konsultasis()->latest()->get();

        return response()->json([
            $konsultasiList,
            "bidang_keahlian" => $profile->bidang_keahlian
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
        ]);

        $user = $request->user();

        // Simpan data konsultasi
        $konsultasi = Konsultasi::create([
            'user_id' => $user->id,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'status_verifikasi' => 'pending',
        ]);

        // Konfigurasi Midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $params = [
            'transaction_details' => [
                'order_id' => 'KONSUL-' . $konsultasi->id . '-' . time(),
                'gross_amount' => 50000, // Harga konsultasi
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ]
        ];

        $snapToken = Snap::getSnapToken($params);

        // Simpan token Midtrans
        $konsultasi->payment_token = $snapToken;
        $konsultasi->save();

        return response()->json([
            'message' => 'Konsultasi berhasil diajukan',
            'snap_token' => $snapToken
        ]);
    }
}

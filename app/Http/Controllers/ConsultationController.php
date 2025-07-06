<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class ConsultationController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }
    public function create(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'category' => 'required|string',
            'duration' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $totalPrice = $request->duration * 500000;

        $consultation = Consultation::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'duration' => $request->duration,
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);

        $params = [
            'transaction_details' => [
                'order_id' => 'CONS-' . $consultation->id . '-' . time(),
                'gross_amount' => $totalPrice,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'item_details' => [[
                'id' => $consultation->id,
                'price' => 500000,
                'quantity' => $request->duration,
                'name' => $request->title,
            ]]
        ];

        $snapToken = Snap::getSnapToken($params);

        return response()->json([
            'message' => 'Konsultasi berhasil dibuat',
            'snap_token' => $snapToken,
            'consultation' => $consultation
        ]);
    }
}

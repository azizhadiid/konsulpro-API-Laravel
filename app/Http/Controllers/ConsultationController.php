<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class ConsultationController extends Controller
{
    public function __construct()
    {
        // Set konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'duration' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
        ]);

        try {
            // Buat konsultasi baru
            $consultation = Consultation::create([
                'user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
                'category' => $request->category,
                'duration' => $request->duration,
                'total_price' => $request->total_price,
                'status' => 'pending',
            ]);

            // Generate unique order ID
            $orderId = 'CONSULT-' . $consultation->id . '-' . time();

            // Update consultation dengan transaction ID
            $consultation->update(['midtrans_transaction_id' => $orderId]);

            // Prepare parameter untuk Midtrans
            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $request->total_price,
                ],
                'customer_details' => [
                    'first_name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ],
                'item_details' => [
                    [
                        'id' => 'consultation-' . $consultation->id,
                        'price' => (int) $request->total_price,
                        'quantity' => 1,
                        'name' => $request->title,
                        'category' => $request->category,
                    ],
                ],
                'callbacks' => [
                    'finish' => config('app.frontend_url') . '/consultation/success',
                    'error' => config('app.frontend_url') . '/consultation/failed',
                    'pending' => config('app.frontend_url') . '/consultation/pending',
                ],
            ];

            // Dapatkan Snap Token
            $snapToken = Snap::getSnapToken($params);

            return response()->json([
                'success' => true,
                'message' => 'Konsultasi berhasil dibuat',
                'data' => [
                    'consultation' => $consultation,
                    'snap_token' => $snapToken,
                    'order_id' => $orderId
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating consultation: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat konsultasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $notification = new Notification();

            $transactionStatus = $notification->transaction_status;
            $transactionId = $notification->order_id;
            $fraudStatus = $notification->fraud_status;

            // Cari consultation berdasarkan transaction ID
            $consultation = Consultation::where('midtrans_transaction_id', $transactionId)->first();

            if (!$consultation) {
                return response()->json(['message' => 'Consultation not found'], 404);
            }

            // Update consultation berdasarkan status
            switch ($transactionStatus) {
                case 'capture':
                    if ($fraudStatus == 'challenge') {
                        $consultation->update([
                            'payment_status' => 'challenge',
                            'midtrans_response' => json_encode($notification->getResponse())
                        ]);
                    } else if ($fraudStatus == 'accept') {
                        $consultation->update([
                            'status' => 'paid',
                            'payment_status' => 'settlement',
                            'midtrans_response' => json_encode($notification->getResponse())
                        ]);
                    }
                    break;

                case 'settlement':
                    $consultation->update([
                        'status' => 'paid',
                        'payment_status' => 'settlement',
                        'midtrans_response' => json_encode($notification->getResponse())
                    ]);
                    break;

                case 'pending':
                    $consultation->update([
                        'payment_status' => 'pending',
                        'midtrans_response' => json_encode($notification->getResponse())
                    ]);
                    break;

                case 'deny':
                    $consultation->update([
                        'status' => 'cancelled',
                        'payment_status' => 'deny',
                        'midtrans_response' => json_encode($notification->getResponse())
                    ]);
                    break;

                case 'expire':
                    $consultation->update([
                        'status' => 'cancelled',
                        'payment_status' => 'expire',
                        'midtrans_response' => json_encode($notification->getResponse())
                    ]);
                    break;

                case 'cancel':
                    $consultation->update([
                        'status' => 'cancelled',
                        'payment_status' => 'cancel',
                        'midtrans_response' => json_encode($notification->getResponse())
                    ]);
                    break;
            }

            return response()->json(['message' => 'Payment notification processed successfully']);
        } catch (\Exception $e) {
            Log::error('Error processing payment callback: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing callback'], 500);
        }
    }

    public function checkStatus($orderId)
    {
        try {
            $consultation = Consultation::where('midtrans_transaction_id', $orderId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$consultation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consultation not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'consultation' => $consultation,
                    'payment_status' => $consultation->payment_status,
                    'status' => $consultation->status
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking consultation status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error checking status'
            ], 500);
        }
    }

    public function getUserConsultations()
    {
        try {
            $consultations = Consultation::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $consultations
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user consultations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching consultations'
            ], 500);
        }
    }
}

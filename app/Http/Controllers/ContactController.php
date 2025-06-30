<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http; // Import Http Facade
use Illuminate\Support\Facades\Log; // Untuk logging error
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function sendContactEmail(Request $request)
    {
        $request->validate([
            'subject' => 'required|string',
            'message' => 'required|string',
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        try {
            Mail::to(env('MAIL_FROM_ADDRESS'))->send(new \App\Mail\ContactMessageMail($data));

            return response()->json([
                'message' => 'Pesan berhasil dikirim.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending contact email:', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return response()->json([
                'message' => 'Gagal mengirim email.',
                'error' => $e->getMessage(), // jangan tampilkan di production
            ], 500);
        }
    }
}

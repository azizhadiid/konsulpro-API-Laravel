<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function register(RegisterRequest $request) // Use RegisterRequest here
    {
        // Validasi sudah ditangani oleh RegisterRequest
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Use Hash::make()
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => $user
        ], 201);
    }

    public function login(LoginRequest $request) // Gunakan LoginRequest di sini
    {
        // Validasi sudah ditangani oleh LoginRequest
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Menggunakan ValidationException untuk respons 422 yang konsisten
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'], // Pesan error generik untuk keamanan
            ]);
        }

        // Hapus token lama untuk user ini jika Anda ingin hanya satu token aktif per perangkat/sesi
        // $user->tokens()->where('name', 'userToken')->delete(); // Hapus token dengan nama 'userToken'

        // Buat token baru
        $token = $user->createToken('userToken')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user, // Laravel akan otomatis menyembunyikan 'password' karena ada di $hidden
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return response()->json(['status' => __($status)], $status === Password::RESET_LINK_SENT ? 200 : 400);
    }

    // Reset password berdasarkan token dari email
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password berhasil direset'])
            : response()->json(['message' => __($status)], 400);
    }
}

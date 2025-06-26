<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $profileData = $profile ? $profile->toArray() : [];

        // Tambahkan URL gambar profil (jika ada)
        if ($profile && $profile->foto) {
            $profileData['foto_url'] = asset('img/profile/user/' . $profile->foto);
        } else {
            $profileData['foto_url'] = null;
        }

        return response()->json([
            'message' => 'Data profil ditemukan.',
            'nama' => $user->name, // nama dari tabel users
            ...$profileData // spread langsung agar langsung terbaca di Next.js
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'tanggal_lahir' => 'nullable|date',
            'pekerjaan' => 'nullable|string',
            'bidang_keahlian' => 'nullable|string',
            'nohp' => 'nullable|string',
            'alamat' => 'nullable|string',
            'kantor' => 'nullable|string',
            'about' => 'nullable|string',
            'foto' => 'nullable|image|max:2048'
        ]);

        $user = $request->user();

        // Cek apakah user sudah punya profil, kalau belum buat baru
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();

            // Pindahkan ke folder img/profile/user
            $file->move(public_path('img/profile/user'), $filename);
            $data['foto'] = $filename;
        }

        $profile->fill($data)->save();

        // Tambahkan foto_url saat response
        $profileData = $profile->toArray();
        $profileData['foto_url'] = $profile->foto ? asset('img/profile/user/' . $profile->foto) : null;

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            ...$profileData
        ]);
    }
}

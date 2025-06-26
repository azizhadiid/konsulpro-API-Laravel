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
        return response()->json([
            'message' => 'Data profil ditemukan.',
            'profile' => $profile
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

        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads'), $filename);
            $data['foto'] = $filename;
        }

        $profile->fill($data)->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'profile' => $profile
        ]);
    }
}

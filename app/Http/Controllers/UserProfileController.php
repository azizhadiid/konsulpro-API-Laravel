<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Http\Requests\UserProfileUpdateRequest;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        // Pastikan relasi 'profile' sudah didefinisikan di model User
        // public function profile() { return $this->hasOne(UserProfile::class); }
        $profile = $user->profile;

        $profileData = $profile ? $profile->toArray() : [];

        // Tambahkan URL gambar profil (jika ada)
        if ($profile && $profile->foto) {
            // Gunakan Storage::url() jika Anda menyimpan di storage/app/public
            // Atau asset() jika Anda menyimpan di public/img/profile/user
            $profileData['foto_url'] = asset('img/profile/user/' . $profile->foto);
        } else {
            $profileData['foto_url'] = null;
        }

        return response()->json([
            'message' => 'Data profil ditemukan.',
            'id' => $user->id, // Tambahkan ID user
            'name' => $user->name, // nama dari tabel users
            'email' => $user->email, // email dari tabel users
            // ... Jika ada data lain dari user model yang ingin Anda sertakan
            ...$profileData // spread langsung agar langsung terbaca di Next.js
        ]);
    }

    public function update(UserProfileUpdateRequest $request)
    {
        $user = $request->user();
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);

        $data = $request->validated(); // Hanya ambil data yang sudah divalidasi oleh Form Request

        // Hapus field 'foto' dari $data jika tidak ada file baru yang diupload,
        // agar tidak menimpa nilai 'foto' yang sudah ada dengan null atau string kosong
        if (!$request->hasFile('foto')) {
            unset($data['foto']);
        }

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalExtension(); // Gunakan getClientOriginalExtension()
            $destinationPath = public_path('img/profile/user');

            // Hapus foto lama jika ada dan file-nya ada di disk
            if ($profile->foto && File::exists($destinationPath . '/' . $profile->foto)) {
                File::delete($destinationPath . '/' . $profile->foto);
            }

            // Pindahkan file baru
            $file->move($destinationPath, $filename);
            $data['foto'] = $filename;
        }

        $profile->fill($data)->save(); // Hanya mengisi field yang ada di $data (yang sudah divalidasi)

        // Perbarui data user di AuthContext jika ada perubahan nama
        // Ini tidak diperlukan karena nama tidak diupdate di sini.
        // Jika nama perlu diupdate, itu harus melalui endpoint terpisah atau di model User.

        $profileData = $profile->toArray();
        $profileData['foto_url'] = $profile->foto ? asset('img/profile/user/' . $profile->foto) : null;

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'id' => $user->id,
            'nama' => $user->name, // Tetap kirim nama asli dari user model
            'email' => $user->email,
            ...$profileData
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ArtikelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Mendapatkan token untuk otentikasi (jika diperlukan di sini)
        $token = $request->bearerToken();
        if (!$token) {
            // Jika Anda menggunakan middleware auth:sanctum di route, ini mungkin tidak perlu
            // Tapi baik untuk penanganan error di controller juga
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Dapatkan parameter dari request
        $perPage = $request->input('per_page', 10); // Default 10 item per halaman
        $search = $request->input('search'); // Keyword pencarian

        // Mulai query Artikel
        $query = Artikel::query();

        // Terapkan filter pencarian jika ada
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', '%' . $search . '%')
                    ->orWhere('kategori', 'like', '%' . $search . '%'); // Cari juga di kolom tag
            });
        }

        // Terapkan paginasi
        $artikels = $query->paginate($perPage);

        // Tambahkan foto_url ke setiap artikel dalam koleksi paginated
        $artikels->getCollection()->transform(function ($artikel) {
            $artikel->foto_url = $artikel->foto ? asset('img/artikel/' . $artikel->foto) : null;
            return $artikel;
        });

        // Response dengan data paginasi
        return response()->json($artikels, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Forbidden: Anda tidak memiliki akses untuk menambah artikel.'
            ], 403);
        }

        // 1. Validasi Request
        $validatedData = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori' => 'required|string|max:255',
            'tanggal_publish' => 'required|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif', // 'foto' sesuai dengan nama field di Next.js
        ]);

        // 2. Mendapatkan user_id dari user yang terautentikasi
        // Jika route Anda di dalam middleware 'auth:sanctum', $request->user() akan tersedia.
        $user = $request->user();

        // Jika user tidak terautentikasi, kembalikan response Unauthorized
        if (!$user) {
            return response()->json(['message' => 'Unauthorized. User not logged in.'], 401);
        }

        // Variabel untuk nama file gambar yang akan disimpan di database
        $gambarNama = null;

        // 3. Penanganan Upload Gambar
        if ($request->hasFile('foto')) {
            $gambar = $request->file('foto');
            // Membuat nama file unik untuk menghindari duplikasi
            $gambarNama = time() . '_' . uniqid() . '.' . $gambar->getClientOriginalExtension();

            // Mendefinisikan jalur tujuan penyimpanan di direktori public
            $destinationPath = public_path('img/artikel');

            // Cek apakah direktori ada, jika tidak, buat direktori tersebut
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true); // 0755 adalah izin yang umum untuk folder
            }

            // Pindahkan file gambar ke lokasi tujuan
            $gambar->move($destinationPath, $gambarNama);
        }

        // 4. Menyimpan data artikel ke database
        $artikel = Artikel::create([
            'user_id' => $user->id, // Menggunakan ID user yang sedang terautentikasi
            'judul' => $validatedData['judul'],
            'deskripsi' => $validatedData['deskripsi'],
            'kategori' => $validatedData['kategori'],
            'tanggal_publish' => $validatedData['tanggal_publish'],
            'foto' => $gambarNama, // Simpan nama file gambar (akan null jika tidak ada foto yang di-upload)
        ]);

        // 5. Menambahkan URL gambar ke objek artikel untuk respons
        // Ini memungkinkan frontend Anda untuk langsung mendapatkan URL gambar yang dapat diakses publik.
        $artikel->foto_url = $artikel->foto ? asset('img/artikel/' . $artikel->foto) : null;

        // 6. Mengembalikan respons JSON yang sukses
        return response()->json([
            'message' => 'Artikel berhasil ditambahkan!',
            'artikel' => $artikel // Mengembalikan objek artikel yang baru dibuat beserta URL fotonya
        ], 201); // Kode status 201 Created menunjukkan bahwa sumber daya baru telah berhasil dibuat
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Temukan artikel berdasarkan ID
        $artikel = Artikel::find($id);

        // Jika artikel tidak ditemukan, kembalikan response 404
        if (!$artikel) {
            return response()->json(['message' => 'Artikel not found.'], 404);
        }

        // Opsional: Pastikan hanya user pemilik yang bisa melihat/mengedit artikelnya sendiri
        // if ($artikel->user_id !== Auth::id()) {
        //     return response()->json(['message' => 'Unauthorized to view this article.'], 403);
        // }

        // Tambahkan foto_url untuk kemudahan di frontend
        $artikel->foto_url = $artikel->foto ? asset('img/artikel/' . $artikel->foto) : null;

        // Kembalikan data artikel
        return response()->json(['artikel' => $artikel], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Temukan artikel yang akan diperbarui
        $artikel = Artikel::find($id);

        if (!$artikel) {
            return response()->json(['message' => 'Artikel not found.'], 404);
        }

        // Opsional: Pastikan hanya user pemilik yang bisa mengedit artikelnya sendiri
        // Jika Anda menggunakan Passport/Sanctum dan user_id di tabel artikel, ini penting
        if ($artikel->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to update this article.'], 403);
        }

        // 1. Validasi Request
        $validatedData = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori' => 'required|string|max:255',
            // Pastikan 'tag' juga divalidasi karena ada di frontend
            'tanggal_publish' => 'required|date',
            // 'foto' bisa nullable jika tidak ada perubahan, atau 'sometimes' jika hanya diisi saat ada file
            // 'foto' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Gunakan 'sometimes' jika field ini hanya dikirim saat ada perubahan
            // Atau jika selalu dikirim tapi bisa null (seperti yang Anda pakai sekarang):
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        // 2. Penanganan Upload Gambar
        $oldFoto = $artikel->foto; // Simpan nama foto lama

        if ($request->hasFile('foto')) {
            // Ada gambar baru di-upload

            // Hapus gambar lama jika ada
            if ($oldFoto && File::exists(public_path('img/artikel/' . $oldFoto))) {
                File::delete(public_path('img/artikel/' . $oldFoto));
            }

            $gambar = $request->file('foto');
            // Pastikan nama file unik untuk menghindari konflik
            $gambarNama = time() . '_' . uniqid() . '.' . $gambar->getClientOriginalExtension();
            $destinationPath = public_path('img/artikel');

            // Pastikan folder tujuan ada
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $gambar->move($destinationPath, $gambarNama);
            $artikel->foto = $gambarNama; // Update nama foto di model
        } elseif ($request->input('foto_changed_to_null')) {
            // Logika untuk menghapus gambar yang sudah ada jika frontend mengirimkan sinyal
            // bahwa gambar harus dihapus (misal: user menghapus gambar di form)
            // Anda perlu menambahkan input tersembunyi di frontend, misal <input type="hidden" name="foto_changed_to_null" value="true/false">
            if ($oldFoto && File::exists(public_path('img/artikel/' . $oldFoto))) {
                File::delete(public_path('img/artikel/' . $oldFoto));
            }
            $artikel->foto = null; // Set foto menjadi null di database
        }
        // Jika tidak ada file baru di-upload dan tidak ada indikasi untuk menghapus,
        // maka foto yang sudah ada akan dipertahankan (tidak diubah).

        // 3. Perbarui data artikel
        $artikel->judul = $validatedData['judul'];
        $artikel->deskripsi = $validatedData['deskripsi'];
        $artikel->kategori = $validatedData['kategori'];
        // Pastikan 'tag' juga diperbarui
        $artikel->tanggal_publish = $validatedData['tanggal_publish'];

        $artikel->save(); // Simpan perubahan ke database

        // Tambahkan foto_url untuk respons agar frontend bisa langsung menampilkannya
        $artikel->foto_url = $artikel->foto ? asset('img/artikel/' . $artikel->foto) : null;

        return response()->json([
            'message' => 'Artikel berhasil diperbarui!',
            'artikel' => $artikel
        ], 200); // 200 OK untuk update
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            $artikel = Artikel::find($id);

            if (!$artikel) {
                return response()->json(['message' => 'Artikel not found.'], 404);
            }

            // Opsional: Pastikan hanya user pemilik yang bisa menghapus artikelnya sendiri
            if ($artikel->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized to delete this article.'], 403);
            }

            // Hapus file gambar terkait dari server
            if ($artikel->foto && file_exists(public_path('img/artikel/' . $artikel->foto))) {
                unlink(public_path('img/artikel/' . $artikel->foto));
            }
            // Atau jika menggunakan Storage Facade:
            // if ($artikel->foto && Storage::disk('public')->exists('img/artikel/' . $artikel->foto)) {
            //     Storage::disk('public')->delete('img/artikel/' . $artikel->foto);
            // }

            $artikel->delete(); // Hapus artikel dari database

            return response()->json(['message' => 'Artikel berhasil dihapus!'], 200); // Atau 204 No Content

        } catch (\Exception $e) {
            Log::error('Error deleting article:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'article_id' => $id,
            ]);
            return response()->json(['message' => 'Terjadi kesalahan pada server saat menghapus artikel.'], 500);
        }
    }
}

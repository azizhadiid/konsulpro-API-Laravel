<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArtikelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

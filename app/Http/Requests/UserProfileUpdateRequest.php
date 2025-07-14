<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tanggal_lahir' => ['nullable', 'date'],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'bidang_keahlian' => ['nullable', 'string', 'max:255'],
            'nohp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'kantor' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'], // Max 2MB
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'pekerjaan.string' => 'Pekerjaan harus berupa teks.',
            'bidang_keahlian.string' => 'Bidang keahlian harus berupa teks.',
            'nohp.string' => 'Nomor telepon harus berupa teks.',
            'alamat.string' => 'Alamat harus berupa teks.',
            'kantor.string' => 'Perusahaan harus berupa teks.',
            'about.string' => 'Tentang Saya harus berupa teks.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.max' => 'Ukuran gambar tidak boleh lebih dari 2MB.',
        ];
    }
}

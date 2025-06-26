<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'tanggal_lahir',
        'pekerjaan',
        'bidang_keahlian',
        'nohp',
        'alamat',
        'kantor',
        'about',
        'foto'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

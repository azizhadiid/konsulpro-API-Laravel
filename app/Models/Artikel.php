<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artikel extends Model
{
    use HasFactory;

    protected $table = 'artikel';

    protected $fillable = [
        'user_id',
        'foto',
        'judul',
        'deskripsi',
        'kategori',
        'tanggal_publish'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

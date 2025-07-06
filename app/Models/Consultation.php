<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'duration',
        'total_price',
        'status',
        'midtrans_transaction_id',
        'payment_status',
        'midtrans_response',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'duration' => 'integer',
        'midtrans_response' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the consultation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the formatted total price.
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Lunas',
            'cancelled' => 'Dibatalkan',
            'completed' => 'Selesai',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Get the payment status label.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'settlement' => 'Berhasil',
            'pending' => 'Menunggu',
            'deny' => 'Ditolak',
            'expire' => 'Kedaluwarsa',
            'cancel' => 'Dibatalkan',
            'challenge' => 'Tantangan',
            default => 'Belum Diproses',
        };
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'it-consulting' => 'Konsultasi IT Umum',
            'strategy-business' => 'Strategi Bisnis Digital',
            'digital-transformation' => 'Transformasi Digital',
            'cloud-solutions' => 'Solusi Cloud & Infrastruktur',
            'cybersecurity' => 'Keamanan Siber',
            'data-analytics' => 'Analisis Data & AI',
            'software-development' => 'Pengembangan Perangkat Lunak',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Scope untuk filter berdasarkan status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter berdasarkan payment status.
     */
    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Scope untuk konsultasi yang sudah dibayar.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope untuk konsultasi yang masih pending.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

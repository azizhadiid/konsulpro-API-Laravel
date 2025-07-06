<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relasi dengan tabel users
            $table->string('title'); // Judul konsultasi
            $table->text('description'); // Deskripsi masalah
            $table->string('category'); // Bidang konsultasi IT
            $table->integer('duration'); // Durasi dalam bulan
            $table->decimal('total_price', 15, 2); // Total harga
            $table->string('status')->default('pending'); // Status konsultasi: pending, paid, cancelled, completed
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};

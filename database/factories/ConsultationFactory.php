<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Consultation>
 */
class ConsultationFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Consultation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Daftar kategori konsultasi
        $categories = ['IT Consulting', 'Business Strategy', 'Digital Transformation', 'Cyber Security'];
        // Daftar status konsultasi
        $statuses = ['pending', 'paid', 'completed', 'cancelled'];

        // Harga dasar per durasi (misal per bulan)
        $basePricePerMonth = $this->faker->randomFloat(2, 500000, 2000000); // Rp 500rb - Rp 2jt

        $duration = $this->faker->numberBetween(1, 12); // Durasi 1-12 bulan
        $totalPrice = $basePricePerMonth * $duration;

        return [
            'user_id' => User::factory(), // Akan membuat user baru jika tidak ada yang diberikan
            'title' => $this->faker->sentence(rand(3, 7)) . ' IT Consulting', // Judul konsultasi
            'description' => $this->faker->paragraph(rand(5, 10)), // Deskripsi
            'category' => $this->faker->randomElement($categories), // Kategori random
            'duration' => $duration, // Durasi random
            'total_price' => $totalPrice, // Total harga
            'status' => $this->faker->randomElement($statuses), // Status random
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'), // Dibuat dalam 1 tahun terakhir
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'), // Diperbarui dalam 1 tahun terakhir
            // consultation_date tidak ada di skema yang Anda berikan, jadi tidak disertakan di factory
            // Jika ada, Anda bisa tambahkan: 'consultation_date' => $this->faker->dateTimeBetween('now', '+6 months'),
        ];
    }

    /**
     * Indicate that the consultation is pending.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Indicate that the consultation is paid.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function paid()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
            ];
        });
    }

    /**
     * Indicate that the consultation is completed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }

    /**
     * Indicate that the consultation is cancelled.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
            ];
        });
    }
}

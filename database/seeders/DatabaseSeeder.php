<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\UserProfile;
use App\Models\Consultation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Menonaktifkan pemeriksaan foreign key sementara
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Hapus data lama untuk memastikan database bersih sebelum seeding baru
        // Urutan penting: hapus tabel anak (child) sebelum tabel induk (parent)
        Consultation::truncate(); // Anak dari users
        UserProfile::truncate(); // Anak dari users (berdasarkan error Anda)
        User::truncate();       // Induk

        // Mengaktifkan kembali pemeriksaan foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Buat satu user admin
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // Password: password
        ]);

        // Buat beberapa user biasa
        User::factory()->count(10)->create(); // Membuat 10 user biasa

        // Ambil semua user yang ada (termasuk admin dan user biasa)
        $users = User::all();

        // Buat 50 konsultasi dan distribusikan di antara user yang ada
        Consultation::factory()->count(50)->make()->each(function ($consultation) use ($users) {
            $consultation->user_id = $users->random()->id; // Assign random user_id
            $consultation->save();
        });

        // Contoh membuat konsultasi dengan status spesifik
        Consultation::factory()->count(5)->pending()->create([
            'user_id' => $users->random()->id,
            'title' => 'Urgent Pending Consultation',
        ]);
        Consultation::factory()->count(5)->paid()->create([
            'user_id' => $users->random()->id,
            'title' => 'Paid Consultation Follow Up',
        ]);
        Consultation::factory()->count(5)->completed()->create([
            'user_id' => $users->random()->id,
            'title' => 'Completed Project Review',
        ]);
        Consultation::factory()->count(5)->cancelled()->create([
            'user_id' => $users->random()->id,
            'title' => 'Cancelled Inquiry',
        ]);

        $this->command->info('Database seeded successfully!');
    }
}

# 📘 KonsulPro API (Laravel)
Ini adalah repositori backend (API) untuk platform KonsulPro, yang dibangun menggunakan Laravel. API ini bertanggung jawab untuk mengelola semua data, otentikasi pengguna, otorisasi, serta menyediakan endpoint untuk fungsionalitas inti platform seperti manajemen artikel, permintaan konsultasi, pemrosesan pembayaran, dan laporan admin. API ini dirancang untuk berinteraksi dengan aplikasi frontend (misalnya, yang dibangun dengan Next.js) melalui HTTP requests.

## 📌 Fitur Utama
### Fitur Utama API
- Autentikasi & Otorisasi:
  - Pendaftaran Pengguna Baru.
  - Login Pengguna (menggunakan Laravel Sanctum untuk token API).
  - Logout Pengguna.
  - Lupa dan Reset Password (melalui email).
  - Middleware berbasis peran untuk melindungi rute admin.
- Manajemen Profil Pengguna:
  - Melihat detail profil pengguna.
  - Memperbarui informasi profil pengguna, termasuk foto profil.
- Manajemen Konsultasi:
  - Mengajukan permintaan konsultasi baru.
  - Integrasi dengan gateway pembayaran (Midtrans Snap) untuk menghasilkan token pembayaran.
  - Menyimpan status pembayaran setelah notifikasi dari gateway pembayaran.
  - Melihat riwayat konsultasi pengguna.
  - Admin: Melihat daftar konsultasi dengan paginasi, pencarian, dan filter status.
  - Admin: Memperbarui status konsultasi (pending, paid, completed, cancelled).
- Manajemen Artikel (Blog):
  - Melihat daftar artikel (publik dan admin).
  - Melihat detail satu artikel.
  - Admin: Membuat, memperbarui, dan menghapus artikel.
- Sistem Rating & Testimonial:
  - Mengirim rating dan testimonial.
  - Melihat daftar rating dan statistik.
  - Melihat testimonial dengan rating tertinggi.
- Sistem Kontak:
  - Mengirim pesan kontak melalui email.
- Dashboard Admin & Laporan:
  - Menyediakan data statistik untuk dashboard admin.
  - Menghasilkan laporan data (misalnya, daftar konsultasi) dalam format file.

## 🚀 Teknologi yang Digunakan
- Framework: Laravel 11+
- Bahasa Pemrograman: PHP 8.2+
- Database: MySQL 8.0+
- Otentikasi API: Laravel Sanctum
- Manajemen File: Laravel Storage Facade (untuk upload gambar profil dan artikel)
- Seeder & Factory: Untuk data dummy pengembangan/pengujian
- Middleware: Untuk otorisasi berbasis peran (AdminMiddleware, UserMiddleware)
- Gateway Pembayaran: Midtrans Snap (integrasi backend)
- Email: Konfigurasi SMTP (untuk reset password, pesan kontak)

## 📜 Persyaratan Sistem
Pastikan Anda memiliki perangkat lunak berikut terinstal di sistem Anda:
- PHP: v8.2 atau lebih tinggi
- Composer: Versi terbaru
- MySQL: v8.0 atau lebih tinggi (atau database relasional lain yang kompatibel)
- Git: Versi terbaru
- Web Server: Nginx atau Apache (atau gunakan php artisan serve untuk pengembangan)

## 🛠️ Instalasi
### 1. Clone Repository
```bash
git clone https://github.com/azizhadiid/konsulpro-API-Laravel.git
cd konsulpro-project # Atau nama folder proyek Anda
```
### 2. Instal Dependensi Composer
```bash
composer install
```
### 3. Konfigurasi Environment dan generate application key
```bash
cp .env.example .env
php artisan key:generate
```
### 4. Konfigurasi File .env
Buka file .env dan sesuaikan konfigurasi berikut:
- Database:
    ```bash
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=konsulpro_db # Ganti dengan nama database Anda
    DB_USERNAME=root # Ganti dengan username database Anda
    DB_PASSWORD= # Ganti dengan password database Anda
    ```

- URL Aplikasi & Frontend:
    ```bash
    APP_URL=http://localhost:8000 # URL di mana API Laravel akan berjalan
    FRONTEND_URL=http://localhost:3000 # URL frontend Next.js Anda (PENTING untuk link reset password)
    ```

- Konfigurasi Mail (untuk reset password dan kontak):
    ```bash
    MAIL_MAILER=smtp
    MAIL_HOST=mailpit # Contoh: gunakan 'mailpit' untuk pengembangan lokal
    MAIL_PORT=1025
    MAIL_USERNAME=null
    MAIL_PASSWORD=null
    MAIL_ENCRYPTION=null
    MAIL_FROM_ADDRESS="no-reply@konsulpro.com" # Ganti dengan email pengirim Anda
    MAIL_FROM_NAME="${APP_NAME}"
    ```
- Konfigurasi Midtrans (jika diimplementasikan):
    ```bash
    MIDTRANS_SERVER_KEY=YOUR_MIDTRANS_SERVER_KEY
    MIDTRANS_CLIENT_KEY=YOUR_MIDTRANS_CLIENT_KEY
    MIDTRANS_IS_PRODUCTION=false # Set true jika di lingkungan produksi
    ```

### 5. Migrasi Database dan Seeder
Jalankan migrasi database untuk membuat tabel, dan seeder untuk mengisi data dummy.
Ini akan menghapus semua data yang ada di database Anda dan mengisi ulang.
```bash
php artisan migrate:fresh --seed
php artisan storage:link # Penting untuk membuat symlink ke folder public/storage untuk file yang diupload
```
### 6. Jalankan Server Laravel
```bash
php artisan serve
```
Server API akan berjalan di http://localhost:8000 (atau port lain yang ditentukan di .env).

## 🚩Endpoint API
Berikut adalah daftar singkat endpoint API yang tersedia:

Autentikasi
- POST /api/register - Mendaftar pengguna baru.
- POST /api/login - Login pengguna dan mendapatkan token Sanctum.
- POST /api/logout - Logout pengguna (membutuhkan token).
- POST /api/forgot-password - Mengirim link reset password ke email.
- POST /api/reset-password - Mereset password pengguna dengan token.
- GET /api/user - Mendapatkan informasi pengguna yang sedang login (membutuhkan token).

Profil Pengguna
- GET /api/profile - Mendapatkan detail profil pengguna yang sedang login (membutuhkan token).
- POST /api/profile - Memperbarui detail profil pengguna (membutuhkan token, mendukung multipart/form-data untuk foto).

Konsultasi
- POST /api/payment-token - Mendapatkan token pembayaran Midtrans Snap (membutuhkan token).
- POST /api/consultation/save - Menyimpan data konsultasi setelah pembayaran (membutuhkan token).
- GET /api/consultation/history - Mendapatkan riwayat konsultasi pengguna (membutuhkan token).

Konsultasi (Admin - Membutuhkan peran admin)
- GET /api/consultation/verifikasi - Mendapatkan daftar konsultasi dengan paginasi, pencarian (search), dan filter status (status=pending|paid|completed|cancelled|all) (membutuhkan token admin).
- PUT /api/consultations/{id}/status - Memperbarui status konsultasi (membutuhkan token admin).

Artikel
- GET /api/artikels - Mendapatkan daftar semua artikel (publik dan admin).
- GET /api/artikels/{id} - Mendapatkan detail satu artikel.

Artikel (Admin - Membutuhkan peran admin)
- POST /api/artikels - Membuat artikel baru (membutuhkan token admin, mendukung multipart/form-data).
- POST /api/artikels/{id} - Memperbarui artikel (menggunakan _method=PUT untuk spoofing, membutuhkan token admin, mendukung multipart/form-data).
- DELETE /api/artikels/{id} - Menghapus artikel (membutuhkan token admin).

Rating & Testimonial
- POST /api/ratings - Mengirim rating dan testimonial baru (membutuhkan token).
- GET /api/ratings - Mendapatkan daftar semua rating dan statistik terkait.
- GET /api/top-ratings - Mendapatkan testimonial dengan rating tertinggi.

Kontak
- POST /api/send-contact-email - Mengirim pesan kontak ke admin (membutuhkan token).

Dashboard Admin (Membutuhkan peran admin)
- GET /api/dashboard - Mendapatkan data statistik untuk dashboard admin (membutuhkan token admin).
- GET /api/dashboard/generate-report - Menghasilkan laporan data (membutuhkan token admin).

## 🖥️ Pengujian API (Menggunakan Postman / Insomnia)
1. Login Admin: Lakukan POST request ke http://localhost:8000/api/login dengan email: admin@example.com dan password: password. Simpan token yang dikembalikan.
2. Gunakan Token: Sertakan token ini di header Authorization: Bearer <YOUR_TOKEN> untuk semua request ke endpoint yang dilindungi.
3. Uji Endpoint Admin: Coba akses endpoint seperti /api/consultation/verifikasi atau /api/dashboard dengan token admin. Anda akan mendapatkan 403 Forbidden jika menggunakan token user biasa.
4. Uji Endpoint User: Coba akses /api/profile atau /api/consultation/history dengan token user biasa.

## 📬 Kontak
Jika kamu tertarik untuk bekerja sama atau memiliki pertanyaan, silakan hubungi melalui form kontak di website ini atau email langsung ke azizalhadiid88@gmail.com.
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;
    public $email;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // PENTING: Buat URL reset password yang mengarah ke frontend Next.js Anda
        // Pastikan Anda memiliki FRONTEND_URL di .env Laravel Anda
        // Saya akan menggunakan /auth/reset-password sebagai path di Next.js
        $url = env('FRONTEND_URL') . '/auth/reset-password?token=' . $this->token . '&email=' . $this->email;

        return (new MailMessage)
            ->subject('Notifikasi Reset Password')
            ->line('Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.')
            ->action('Reset Password', $url)
            ->line('Link reset password ini akan kedaluwarsa dalam ' . config('auth.passwords.users.expire') . ' menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

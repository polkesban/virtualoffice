<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortalResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $routeName,
        private readonly string $portalLabel,
        private readonly string $brokerName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config("auth.passwords.{$this->brokerName}.expire", 60);
        $url = route($this->routeName, [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset Password ' . $this->portalLabel)
            ->greeting('Halo ' . ($notifiable->name ?? 'Pengguna') . ',')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun Anda.')
            ->action('Reset Password', $url)
            ->line('Tautan ini berlaku selama ' . $minutes . ' menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.');
    }
}
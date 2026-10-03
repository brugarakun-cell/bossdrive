<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('BossDrive password reset code')
            ->greeting('Password reset request')
            ->line('Use this 6-digit code to reset your BossDrive password:')
            ->line($this->code)
            ->line('This code expires in 60 minutes. If you did not request a password reset, you can ignore this email.');
    }
}

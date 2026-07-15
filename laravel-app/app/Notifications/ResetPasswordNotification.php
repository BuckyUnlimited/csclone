<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;
    private $token;
    private string $callback_url;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token, string $callback_url)
    {
        $this->token = $token;
        $this->callback_url = $callback_url;
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
    public function toMail($notifiable)
    {
        $resetUrl = route('set.new-password', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('Click the button below to set your new password.')
            ->action('Set New Password', $this->callback_url . '?forwarded-url=' . urlencode($resetUrl))
            ->line('If you did not request a password reset, no further action is required.');
    }
}

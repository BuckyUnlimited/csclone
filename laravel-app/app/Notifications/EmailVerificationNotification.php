<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    private ?string $callback_url;

    /**
     * Create a new notification instance.
     */
    public function __construct($callback_url = null)
    {
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
    public function toMail(object $notifiable): MailMessage
    {
        $verificationURL = $this->verificationURL($notifiable);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->line('Click the button below to verify your email address.')
            ->action('Verify Email', $this->callback_url . '?forwarded-url=' . urlencode($verificationURL))
            ->line('If you did not create an account, no further action is required.')
            ->line('This verification link will expire in 5 minutes.');
    }

    protected function verificationURL($notifiable)
    {
        return URL::temporarySignedRoute(
            'verify.email',
            Carbon::now()->addMinutes(5),
            [
                'id' => $notifiable->getKey(), // user id
                'hash' => sha1($notifiable->getEmailForVerification()), // hash using user email
            ]
        );
    }
}

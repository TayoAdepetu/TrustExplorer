<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class APIPasswordResetNotification extends Notification
{
    use Queueable;

    protected $reset_code;

    /**
     * Create a new notification instance.
     */
    public function __construct($code)
    {
        //
        $this->reset_code = $code;
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
        return (new MailMessage)
            ->greeting("Hello")
            ->line('A password reset for the account associated with this email has been requested.')
            ->line("Please enter the code below in your password reset page")
            ->line($this->reset_code)
            // ->action('Click To Change Password', url(env("FRONTEND_UR") . '/' . $this->reset_code))
            ->line("If you did not request for a password reset, please ignore this message.")
            ->line('Thank you for using our application!')
            ->subject("BizGrowthHackerz - New Password Reset Link");
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

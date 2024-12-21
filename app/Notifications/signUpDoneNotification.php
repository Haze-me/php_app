<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class signUpDoneNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $checkUserOtp;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $checkUserOtp)
    {
        $this->checkUserOtp = $checkUserOtp;
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
                    ->subject('Welcome to Silfrica!')
                    ->greeting('Hey ' . $notifiable->firstname)
                    ->line('We are glad to have you here on our platform!')
                    ->line('Proceed with the App for more exciting features and Info!')
                    ->action('Here is our Official Website!', url('https://silfrica.com'))
                    ->line('This is a no reply message')
                    ->line('If you did not create an account, Contact us at support@silfrica.com.');
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

<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    private $otp;
    private $user;

    public function generateOTP()
    {
        $otp = mt_rand(100000,999999);
        
        return $otp;
    }

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->otp = $this->generateOTP();
        $this->user->update(['otp' => $this->otp]);
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
                    ->subject('Verify Email Address')
                    ->greeting('Hello '.$notifiable->firstname)
                    ->line('Please use the code below to verify your email address.')
                    ->action($this->otp, url('https://silfrica.com'))
                    ->line('Your OTP is '.$this->otp)
                    ->line('This is a no reply message')
                    ->line('If you did not create an account, no further action is required.');
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

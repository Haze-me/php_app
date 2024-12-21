<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class forgotPasswordNotification extends Notification
{
    use Queueable;

    private $randomPassword;
    private $user;

    public function generateRandomPassword() {
        $lowerCaseChars = range('a', 'z');
        $upperCaseChars = range('A', 'Z');
        $digitChars = range('0', '9');
        $symbolChars = '!@#$%^&*()_+-=[]{};:,.<>?';

        $password = '';

        while (strlen($password) < 8) {
            $password .= $lowerCaseChars[rand(0, count($lowerCaseChars) - 1)];
            $password .= $upperCaseChars[rand(0, count($upperCaseChars) - 1)];
            $password .= $digitChars[random_int(0, count($digitChars) - 1)];
            $password .= $symbolChars[rand(0, strlen($symbolChars) - 1)];
        }
    
        return $password;
    }

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->randomPassword = $this->generateRandomPassword();
        $this->user->update([
            'reset_password' => true,
            'password' => Hash::make($this->randomPassword),
        ]);
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
            ->subject('Forgot Password')
            ->greeting('Hello ' . $notifiable->firstname)
            ->line('You have requested for a password change!!!')
            ->line('Please use the password below to login to your account.')
            ->action('Use: ' . $this->randomPassword, null)
            ->line('Your Password is now ' . $this->randomPassword)
            ->line('This is a no reply message')
            ->line('If you did not make this request for your account, Please contact us immediately!!!')
            ->line('Here at', url('mailto:silfricatech@gmail.com'));
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

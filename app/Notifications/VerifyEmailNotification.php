<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the verification URL for the given notifiable.
     * Points to our custom route that doesn't require auth.
     */
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify.custom',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Welcome to NeedAnEstimate! Verify Your Email')
            ->greeting("Welcome aboard, {$notifiable->name}! 🎉")
            ->line('Thank you for joining **NeedAnEstimate.com** — the trusted directory for finding and listing professional service providers.')
            ->line('To get started, please verify your email address by clicking the button below:')
            ->action('Verify Email Address', $verificationUrl)
            ->line('Once verified, you can:')
            ->line('✅ Create and manage your business listings')
            ->line('✅ Connect with potential customers')
            ->line('✅ Build your online reputation with reviews')
            ->line('If you did not create an account, no further action is required.')
            ->salutation("— The NeedAnEstimate Team");
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $amount,
        protected string $invoiceId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Payment Received — NeedAnEstimate')
            ->greeting("Hello {$notifiable->name},")
            ->line('We've successfully received your payment.')
            ->line('---')
            ->line("**Amount:** {$this->amount}")
            ->line("**Invoice ID:** {$this->invoiceId}")
            ->line('---')
            ->line('Your subscription is active and your listing remains live.')
            ->action('View Billing', config('app.frontend_url') . '/dashboard/billing')
            ->line('Thank you for being a NeedAnEstimate professional!')
            ->salutation('— The NeedAnEstimate Team');
    }
}

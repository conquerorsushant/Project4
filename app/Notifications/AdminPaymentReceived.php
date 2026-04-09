<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $customerName,
        protected string $customerEmail,
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
            ->subject("💰 Payment Received — {$this->customerName}")
            ->greeting('Hello Admin,')
            ->line('A subscription payment has been received.')
            ->line('---')
            ->line("**Customer:** {$this->customerName}")
            ->line("**Email:** {$this->customerEmail}")
            ->line("**Amount:** {$this->amount}")
            ->line("**Invoice ID:** {$this->invoiceId}")
            ->line('---')
            ->action('View in Admin Panel', config('app.url') . '/admin/payment-history')
            ->salutation('— NeedAnEstimate System');
    }
}

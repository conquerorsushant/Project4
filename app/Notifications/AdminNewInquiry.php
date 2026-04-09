<?php

namespace App\Notifications;

use App\Models\ContactInquiry;
use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminNewInquiry extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ContactInquiry $inquiry,
        protected Listing $listing,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ownerName = $this->listing->user?->name ?? 'Unknown';

        return (new MailMessage)
            ->subject("🔔 New Inquiry — {$this->listing->company_name}")
            ->greeting('Hello Admin,')
            ->line("A new contact inquiry has been verified and delivered.")
            ->line('---')
            ->line("**Listing:** {$this->listing->company_name}")
            ->line("**Listing Owner:** {$ownerName}")
            ->line("**From:** {$this->inquiry->name} ({$this->inquiry->email})")
            ->line("**Phone:** " . ($this->inquiry->phone ?: '—'))
            ->line("**Message:**")
            ->line("> {$this->inquiry->message}")
            ->line('---')
            ->action('View in Admin Panel', config('app.url') . '/admin/contact-inquiries')
            ->salutation('— NeedAnEstimate System');
    }
}

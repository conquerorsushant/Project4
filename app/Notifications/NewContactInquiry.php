<?php

namespace App\Notifications;

use App\Models\ContactInquiry;
use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactInquiry extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject("📩 New inquiry for {$this->listing->company_name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have a new contact inquiry for your listing **\"{$this->listing->company_name}\"**.")
            ->line('---')
            ->line("**From:** {$this->inquiry->name}")
            ->line("**Email:** {$this->inquiry->email}")
            ->line("**Message:**")
            ->line("> {$this->inquiry->message}")
            ->line('---')
            ->line('We recommend responding within **24 hours** to make a great impression.')
            ->action('View All Inquiries', config('app.frontend_url') . '/dashboard/inquiries')
            ->salutation("— The NeedAnEstimate Team");
    }
}

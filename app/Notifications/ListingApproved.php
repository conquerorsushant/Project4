<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Listing $listing,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("✅ Your listing \"{$this->listing->company_name}\" is now live!")
            ->greeting("Great news, {$notifiable->name}! 🎉")
            ->line("Your business listing **\"{$this->listing->company_name}\"** has been reviewed and approved. It is now live on NeedAnEstimate.com and visible to potential customers.")
            ->line('Here\'s what happens next:')
            ->line('🔍 Your listing is now searchable in our directory')
            ->line('📩 Customers can contact you directly through your listing')
            ->line('⭐ Start collecting reviews to boost your visibility')
            ->action('View Your Listing', config('app.frontend_url') . '/listing/' . $this->listing->slug)
            ->line('Want more visibility? Consider upgrading to a **Featured Listing** from your dashboard.')
            ->salutation("— The NeedAnEstimate Team");
    }
}

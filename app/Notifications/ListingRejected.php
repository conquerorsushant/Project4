<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Listing $listing,
        protected ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Action needed: Your listing \"{$this->listing->company_name}\"")
            ->greeting("Hello {$notifiable->name},")
            ->line("We\'ve reviewed your listing **\"{$this->listing->company_name}\"** and it wasn\'t approved at this time.");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        return $mail
            ->line('Don\'t worry — you can update your listing and resubmit it for review. Common reasons for rejection include:')
            ->line('• Incomplete business information')
            ->line('• Inappropriate content or images')
            ->line('• Duplicate listing')
            ->action('Edit & Resubmit', config('app.frontend_url') . '/dashboard/listings/' . $this->listing->id . '/edit')
            ->line('Need help? Reply to this email or contact us at **support@needanestimate.com**.')
            ->salutation("— The NeedAnEstimate Team");
    }
}

<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyReviewEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Review $review,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verifyUrl = URL::temporarySignedRoute(
            'review.verify',
            now()->addHours(48),
            ['review' => $this->review->id]
        );

        $companyName = $this->review->listing->company_name ?? 'the business';

        return (new MailMessage)
            ->subject("Verify your review for {$companyName} — NeedAnEstimate")
            ->greeting("Hello {$this->review->reviewer_name},")
            ->line("Thank you for submitting a review for **\"{$companyName}\"** on NeedAnEstimate.")
            ->line("To ensure authenticity, please verify your email by clicking the button below:")
            ->action('Verify My Review', $verifyUrl)
            ->line("After verification, your review will be sent to our team for approval.")
            ->line("This link will expire in **48 hours**.")
            ->line("If you did not submit this review, you can simply ignore this email.")
            ->salutation("— The NeedAnEstimate Team");
    }
}

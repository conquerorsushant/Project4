<?php

namespace App\Notifications;

use App\Models\ContactInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyInquiryEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ContactInquiry $inquiry,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verifyUrl = URL::temporarySignedRoute(
            'inquiry.verify',
            now()->addHours(48),
            ['inquiry' => $this->inquiry->id]
        );

        $companyName = $this->inquiry->listing->company_name ?? 'the business';

        return (new MailMessage)
            ->subject("Verify your estimate request for {$companyName} — NeedAnEstimate")
            ->greeting("Hello {$this->inquiry->name},")
            ->line("Thank you for requesting an estimate from **\"{$companyName}\"** on NeedAnEstimate.")
            ->line("To ensure your request reaches the business, please verify your email:")
            ->action('Verify My Request', $verifyUrl)
            ->line("After verification, your request will be forwarded to the business owner.")
            ->line("This link will expire in **48 hours**.")
            ->line("If you did not submit this request, you can simply ignore this email.")
            ->salutation("— The NeedAnEstimate Team");
    }
}

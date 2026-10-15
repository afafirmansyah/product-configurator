<?php

namespace App\Notifications;

use App\Models\QuotationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuotationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly QuotationRequest $quotation) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $quotation = $this->quotation->loadMissing('configuration.product');

        return (new MailMessage)
            ->subject('Quotation request received — '.$quotation->reference_number)
            ->greeting('Hello '.($quotation->contact_person ?: $notifiable->name).',')
            ->line('We have received your quotation request.')
            ->line('Reference: '.$quotation->reference_number)
            ->line('Product: '.($quotation->configuration?->product?->name ?? '—'))
            ->line('Quantity: '.$quotation->quantity)
            ->action('View configuration', route('configurator.show', $quotation->configuration?->ulid))
            ->line('Our team will review the request and get back to you shortly.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'quotation_id' => $this->quotation->id,
            'reference_number' => $this->quotation->reference_number,
            'status' => $this->quotation->status,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\QuotationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuotationStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly QuotationRequest $quotation,
        public readonly string $previousStatus,
    ) {}

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
        $quotation = $this->quotation->loadMissing('configuration');

        return (new MailMessage)
            ->subject('Quotation '.$quotation->reference_number.' is now '.ucfirst($quotation->status))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The status of your quotation request has changed.')
            ->line('Reference: '.$quotation->reference_number)
            ->line('Previous status: '.ucfirst($this->previousStatus))
            ->line('New status: '.ucfirst($quotation->status))
            ->action('View configuration', route('configurator.show', $quotation->configuration?->ulid));
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
            'previous_status' => $this->previousStatus,
            'status' => $this->quotation->status,
        ];
    }
}

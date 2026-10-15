<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class BomExportReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $path,
        public readonly string $filename,
        public readonly string $configurationNumber,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build a signed, time-limited download URL for the stored export.
     */
    public function downloadUrl(): string
    {
        return URL::temporarySignedRoute(
            'sales.bom.download',
            now()->addDay(),
            ['path' => $this->path],
        );
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your BOM export is ready — '.$this->configurationNumber)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The export you requested for configuration '.$this->configurationNumber.' is ready to download.')
            ->action('Download '.$this->filename, $this->downloadUrl())
            ->line('This download link expires in 24 hours.');
    }
}

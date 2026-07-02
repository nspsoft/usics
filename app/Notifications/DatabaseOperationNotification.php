<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DatabaseOperationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $message;

    public function __construct(string $title, string $message)
    {
        $this->title = $title;
        $this->message = $message;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $isError = str_contains(strtolower($this->title), 'failed');
        
        return (new MailMessage)
            ->subject('[USICS] ' . $this->title)
            ->greeting($isError ? '⚠️ Database Operation Alert' : '✅ Database Operation Complete')
            ->line($this->message)
            ->line('Timestamp: ' . now()->format('Y-m-d H:i:s'))
            ->action('View Activity Log', url('/admin/activity-logs'));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => str_contains(strtolower($this->title), 'failed') ? 'error' : 'success',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

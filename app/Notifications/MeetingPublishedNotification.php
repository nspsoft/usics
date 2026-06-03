<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingPublishedNotification extends Notification
{
    use Queueable;

    protected Meeting $meeting;

    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dateFormatted = date('d F Y', strtotime($this->meeting->meeting_date));
        $startTime = substr($this->meeting->start_time, 0, 5);
        $endTime = substr($this->meeting->end_time, 0, 5);
        $chairpersonName = $this->meeting->chairperson ? $this->meeting->chairperson->name : 'Pimpinan Rapat';

        return (new MailMessage)
            ->subject("📢 Notulen Rapat: {$this->meeting->title}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Notulen resmi untuk rapat *\"{$this->meeting->title}\"* telah diterbitkan oleh {$chairpersonName}.")
            ->line("Berikut rincian rapat terkait:")
            ->line("• **Tanggal**: {$dateFormatted}")
            ->line("• **Waktu**: {$startTime} - {$endTime} WIB")
            ->line("• **Lokasi / Ruang**: {$this->meeting->location}")
            ->line("• **Pimpinan Rapat**: {$chairpersonName}")
            ->line("• **Notulis**: " . ($this->meeting->secretary ? $this->meeting->secretary->name : '-'))
            ->line(" ")
            ->line("**Pembahasan Rapat / Notulensi**:")
            ->line($this->meeting->discussion_notes ?: 'Tidak ada pembahasan khusus.')
            ->action("Lihat Detail Rapat", url("/meeting-command/{$this->meeting->id}"))
            ->line("Terima kasih atas partisipasi Anda dalam rapat ini.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'meeting_published',
            'icon' => 'video-camera',
            'color' => 'purple',
            'title' => 'Notulen Rapat Diterbitkan',
            'message' => "Notulen rapat \"{$this->meeting->title}\" telah diterbitkan.",
            'meeting_id' => $this->meeting->id,
            'action_url' => "/meeting-command/{$this->meeting->id}",
            'action_text' => 'Lihat Notulen',
        ];
    }
}

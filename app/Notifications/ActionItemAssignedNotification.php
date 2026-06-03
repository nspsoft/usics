<?php

namespace App\Notifications;

use App\Models\MeetingActionItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActionItemAssignedNotification extends Notification
{
    use Queueable;

    protected MeetingActionItem $item;

    public function __construct(MeetingActionItem $item)
    {
        $this->item = $item;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $meeting = $this->item->meeting;
        $dateFormatted = date('d F Y', strtotime($this->item->due_date));
        $meetingTitle = $meeting ? $meeting->title : 'Rapat';

        return (new MailMessage)
            ->subject("📋 Penugasan Tugas Baru: {$this->item->description}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Anda telah mendapatkan penugasan baru dari rapat *\"{$meetingTitle}\"*:")
            ->line("• **Tugas / Penugasan**: {$this->item->description}")
            ->line("• **Target Selesai (Due Date)**: {$dateFormatted}")
            ->line("• **Status Awal**: " . strtoupper($this->item->status))
            ->line(" ")
            ->action("Buka Kanban Board & Rapat", url("/meeting-command/{$this->item->meeting_id}"))
            ->line("Mohon untuk menyelesaikan penugasan ini tepat waktu. Anda dapat memperbarui progress pengerjaan tugas Anda di Kanban Board portal ERP.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'action_item_assigned',
            'icon' => 'clipboard-document-list',
            'color' => 'indigo',
            'title' => 'Tugas Rapat Baru',
            'message' => "Tugas baru: \"{$this->item->description}\" ditugaskan kepada Anda.",
            'meeting_id' => $this->item->meeting_id,
            'action_url' => "/meeting-command/{$this->item->meeting_id}",
            'action_text' => 'Lihat Tugas',
        ];
    }
}

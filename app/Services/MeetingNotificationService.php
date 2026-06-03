<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Notifications\MeetingPublishedNotification;
use App\Notifications\ActionItemAssignedNotification;
use Illuminate\Support\Facades\Log;

class MeetingNotificationService
{
    protected WhatsappBotService $whatsapp;

    public function __construct(WhatsappBotService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Dispatch notifications for all internal attendees when a meeting is published.
     */
    public function dispatchMeetingPublished(Meeting $meeting): void
    {
        // Eager load relations for performance
        $meeting->load(['chairperson', 'secretary', 'attendees.user.employee']);
        
        $dateFormatted = date('d F Y', strtotime($meeting->meeting_date));
        $startTime = substr($meeting->start_time, 0, 5);
        $endTime = substr($meeting->end_time, 0, 5);
        $chairpersonName = $meeting->chairperson ? $meeting->chairperson->name : 'Pimpinan Rapat';
        
        $shortNotes = $meeting->discussion_notes ? mb_strimwidth(strip_tags($meeting->discussion_notes), 0, 200, '...') : 'Tidak ada pembahasan khusus.';

        foreach ($meeting->attendees as $attendee) {
            if ($attendee->user) {
                try {
                    // 1. Send Email & Database Notification
                    $attendee->user->notify(new MeetingPublishedNotification($meeting));
                    
                    // 2. Send WhatsApp Notification if employee phone number is registered
                    $phone = $attendee->user->employee?->phone;
                    if ($phone) {
                        $waMessage = "📢 *PT. JIDOKA RESULT INDONESIA - NOTULEN RAPAT*\n\n"
                            . "Yth. Bapak/Ibu *{$attendee->user->name}*,\n"
                            . "Notulen resmi rapat berikut telah diterbitkan:\n\n"
                            . "• *Judul Rapat*: {$meeting->title}\n"
                            . "• *Tanggal*: {$dateFormatted}\n"
                            . "• *Waktu*: {$startTime} - {$endTime} WIB\n"
                            . "• *Lokasi*: {$meeting->location}\n"
                            . "• *Pimpinan*: {$chairpersonName}\n\n"
                            . "*Ringkasan Pembahasan*:\n"
                            . "_{$shortNotes}_\n\n"
                            . "Silakan cek detail lengkap dan tanda tangan digital Anda di link berikut:\n"
                            . config('app.url') . "/meeting-command/{$meeting->id}\n\n"
                            . "Terima kasih.";

                        $this->whatsapp->sendNotification($phone, $waMessage);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send meeting published notification to user {$attendee->user->id}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Dispatch notification to a PIC when an action item is assigned to them.
     */
    public function dispatchActionItemAssigned(MeetingActionItem $item): void
    {
        $item->load(['meeting', 'pic.employee']);
        $meeting = $item->meeting;
        $dateFormatted = date('d F Y', strtotime($item->due_date));
        $meetingTitle = $meeting ? $meeting->title : 'Rapat';

        if ($item->pic) {
            try {
                // 1. Send Email & Database Notification
                $item->pic->notify(new ActionItemAssignedNotification($item));

                // 2. Send WhatsApp Notification if employee phone number is registered
                $phone = $item->pic->employee?->phone;
                if ($phone) {
                    $waMessage = "📋 *PT. JIDOKA RESULT INDONESIA - PENUGASAN BARU*\n\n"
                        . "Yth. *{$item->pic->name}*,\n"
                        . "Anda mendapatkan penugasan baru dari rapat *{$meetingTitle}*:\n\n"
                        . "• *Tugas*: {$item->description}\n"
                        . "• *Target Selesai*: {$dateFormatted}\n"
                        . "• *Status*: " . strtoupper($item->status) . "\n\n"
                        . "Silakan kelola status penugasan Anda di Kanban Board atau link berikut:\n"
                        . config('app.url') . "/meeting-command/{$item->meeting_id}\n\n"
                        . "Terima kasih.";

                    $this->whatsapp->sendNotification($phone, $waMessage);
                }
            } catch (\Exception $e) {
                Log::error("Failed to send action item assigned notification to user {$item->pic->id}: " . $e->getMessage());
            }
        }
    }
}

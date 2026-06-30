<?php

namespace App\Services;

use App\Models\HelpdeskTicket;
use App\Models\HelpdeskTicketReply;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Log;
use Throwable;

class HelpdeskNotificationService
{
    protected FonnteService $fonnteService;
    protected EmailService $emailService;

    public function __construct(FonnteService $fonnteService, EmailService $emailService)
    {
        $this->fonnteService = $fonnteService;
        $this->emailService = $emailService;
    }

    /**
     * Get Central IT Notification Contacts from Settings
     */
    protected function getCentralContacts(): array
    {
        return [
            'whatsapp' => AppSetting::get('helpdesk_wa_number'),
            'email' => AppSetting::get('helpdesk_email_address'),
        ];
    }

    /**
     * Helper to safely format priority
     */
    protected function formatPriority(string $priority): string
    {
        return match($priority) {
            'low' => '🟢 Low',
            'medium' => '🟡 Medium',
            'high' => '🟠 High',
            'critical' => '🔴 CRITICAL',
            default => '⚪ ' . ucfirst($priority),
        };
    }

    /**
     * Notify Central IT when a new ticket is created
     */
    public function notifyNewTicket(HelpdeskTicket $ticket)
    {
        try {
            $contacts = $this->getCentralContacts();
            $url = url('/portal/helpdesk/' . $ticket->id);
            $creator = $ticket->user->name ?? 'Unknown User';
            
            $message = "*TIKET HELPDESK BARU*\n\n";
            $message .= "ID: {$ticket->ticket_number}\n";
            $message .= "Pelapor: {$creator}\n";
            $message .= "Kategori: " . ucfirst($ticket->category) . "\n";
            $message .= "Prioritas: " . $this->formatPriority($ticket->priority) . "\n";
            $message .= "Judul: *{$ticket->title}*\n\n";
            $message .= "Mohon segera ditugaskan/ditindaklanjuti. Link:\n{$url}";

            if (!empty($contacts['whatsapp'])) {
                $this->fonnteService->sendMessage($contacts['whatsapp'], $message);
            }

            if (!empty($contacts['email'])) {
                $htmlBody = nl2br(e($message));
                $this->emailService->sendEmail($contacts['email'], "[New Ticket] {$ticket->ticket_number} - {$ticket->title}", $htmlBody);
            }
        } catch (Throwable $e) {
            Log::error('HelpdeskNotification Error (New Ticket): ' . $e->getMessage());
        }
    }

    /**
     * Notify when a reply is added
     */
    public function notifyTicketReply(HelpdeskTicketReply $reply)
    {
        try {
            $ticket = $reply->ticket;
            // If the replier is the creator, notify assigned IT or Central IT
            // If the replier is IT, notify the creator
            
            $replierId = $reply->user_id;
            $creatorId = $ticket->user_id;
            
            $url = url('/portal/helpdesk/' . $ticket->id);
            $message = "*BALASAN BARU PADA TIKET*\n\n";
            $message .= "ID: {$ticket->ticket_number}\n";
            $message .= "Judul: *{$ticket->title}*\n";
            $message .= "Dari: {$reply->user->name}\n";
            $message .= "Pesan:\n\"" . substr($reply->message, 0, 100) . (strlen($reply->message) > 100 ? '...' : '') . "\"\n\n";
            $message .= "Balas di sistem: {$url}";

            if ($replierId === $creatorId) {
                // User replied, notify IT
                if ($ticket->assignedTo) {
                    $this->sendToUser($ticket->assignedTo, $message, "[Reply] {$ticket->ticket_number}");
                } else {
                    $contacts = $this->getCentralContacts();
                    if (!empty($contacts['whatsapp'])) $this->fonnteService->sendMessage($contacts['whatsapp'], $message);
                    if (!empty($contacts['email'])) $this->emailService->sendEmail($contacts['email'], "[Reply] {$ticket->ticket_number}", nl2br(e($message)));
                }
            } else {
                // IT replied, notify User (unless it's an internal note)
                if (!$reply->is_internal) {
                    $this->sendToUser($ticket->user, $message, "[Reply] {$ticket->ticket_number}");
                }
            }
        } catch (Throwable $e) {
            Log::error('HelpdeskNotification Error (Ticket Reply): ' . $e->getMessage());
        }
    }

    /**
     * Notify when status changes
     */
    public function notifyStatusUpdate(HelpdeskTicket $ticket)
    {
        try {
            if (in_array($ticket->status, ['resolved', 'closed'])) {
                $url = url('/portal/helpdesk/' . $ticket->id);
                $message = "*TIKET HELPDESK SELESAI*\n\n";
                $message .= "ID: {$ticket->ticket_number}\n";
                $message .= "Judul: *{$ticket->title}*\n";
                $message .= "Status saat ini: *" . strtoupper($ticket->status) . "*\n\n";
                $message .= "Tiket Anda telah ditandai selesai. Terima kasih atas laporannya!\n{$url}";
                
                $this->sendToUser($ticket->user, $message, "[Closed] {$ticket->ticket_number}");
            }
        } catch (Throwable $e) {
            Log::error('HelpdeskNotification Error (Status Update): ' . $e->getMessage());
        }
    }

    /**
     * Send WA & Email to a specific User model
     */
    protected function sendToUser($user, string $message, string $subject)
    {
        if (!$user) return;

        if (!empty($user->phone)) {
            $this->fonnteService->sendMessage($user->phone, $message);
        }

        if (!empty($user->email)) {
            $this->emailService->sendEmail($user->email, $subject, nl2br(e($message)));
        }
    }
}

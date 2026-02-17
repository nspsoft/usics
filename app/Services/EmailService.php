<?php

namespace App\Services;

use App\Models\EmailAttachment;
use App\Models\EmailMessage;
use App\Models\Customer;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmailService
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Fetch unread emails from the IMAP server and process them.
     */
    public function fetchEmails()
    {
        try {
            $company = Company::first();
            $settings = $company->settings['email'] ?? null;

            if (!$settings || empty($settings['imap_host'])) {
                Log::warning('Email Fetch skipped: IMAP not configured in UI settings.');
                return 0;
            }

            // Dynamically set config for default account
            config([
                'imap.accounts.default.host' => $settings['imap_host'],
                'imap.accounts.default.port' => (int) $settings['imap_port'],
                'imap.accounts.default.encryption' => $settings['imap_encryption'],
                'imap.accounts.default.validate_cert' => false, // Disable for IP/Subdomain usage
                'imap.accounts.default.username' => $settings['imap_username'],
                'imap.accounts.default.password' => $settings['imap_password'],
            ]);

            $client = Client::account('default');
            $client->connect();

            $inbox = $client->getFolder('INBOX');
            
            // Log total messages to debug
            $allMessagesCount = $inbox->messages()->all()->get()->count();
            Log::info("IMAP Sync: Found {$allMessagesCount} total messages in INBOX.");

            // Fetch unread messages
            $messages = $inbox->messages()->unseen()->get();
            Log::info("IMAP Sync: Found {$messages->count()} unread messages.");

            $processedCount = 0;

            foreach ($messages as $message) {
                // Check if already processed
                if (EmailMessage::where('message_id', $message->getMessageId())->exists()) {
                    continue;
                }

                $this->processEmail($message);
                $processedCount++;
            }

            return $processedCount;
        } catch (\Exception $e) {
            Log::error('IMAP Fetch Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process a single email message.
     */
    protected function processEmail($message)
    {
        $from = $message->getFrom()[0];
        $fromAddress = $from->mail;
        $fromName = $from->personal;
        
        // Find existing customer
        $customer = Customer::where('email', $fromAddress)->first();

        // Save Email Message
        $emailRecord = EmailMessage::create([
            'message_id' => $message->getMessageId(),
            'subject' => $message->getSubject(),
            'from_address' => $fromAddress,
            'from_name' => $fromName,
            'to_address' => $message->getTo()[0]->mail ?? null,
            'body_html' => $message->getHTMLBody(),
            'body_text' => $message->getTextBody(),
            'status' => 'unread',
            'customer_id' => $customer?->id,
            'email_date' => Carbon::parse($message->getDate()),
        ]);

        // Process Attachments
        foreach ($message->getAttachments() as $attachment) {
            $filename = $attachment->getName();
            $path = 'email-attachments/' . $emailRecord->id . '/' . $filename;
            
            Storage::disk('public')->put($path, $attachment->getContent());

            EmailAttachment::create([
                'email_message_id' => $emailRecord->id,
                'file_path' => $path,
                'file_name' => $filename,
                'mime_type' => $attachment->getMimeType(),
                'size' => $attachment->getSize(),
            ]);
        }

        // Trigger AI Analysis
        $this->analyzeEmail($emailRecord);

        // Mark as seen in IMAP if needed (optional based on preference)
        // $message->setFlag(['Seen']);
    }

    /**
     * Run AI analysis on the email.
     */
    public function analyzeEmail(EmailMessage $email)
    {
        $body = $email->body_text ?: strip_tags($email->body_html);
        
        // 1. Analyze Intent/Sentiment/Urgency
        $analysis = $this->gemini->analyzeEmailContent($body);
        
        $email->update([
            'intent' => $analysis['intent'] ?? 'unknown',
            'sentiment' => $analysis['sentiment'] ?? 'neutral',
            'urgency_score' => $analysis['urgency'] ?? 0,
            'ai_metadata' => $analysis,
        ]);

        // 2. If it's a PO, try to extract items from attachments
        if ($email->intent === 'order_status' || $email->intent === 'request_quotation' || str_contains(strtolower($email->subject), 'po')) {
            foreach ($email->attachments as $attachment) {
                if ($this->isExtractable($attachment)) {
                    $poData = $this->gemini->extractPOData(
                        storage_path('app/public/' . $attachment->file_path),
                        $attachment->mime_type
                    );

                    if ($poData) {
                        $attachment->update(['is_po' => true]);
                        $email->update([
                            'ai_metadata' => array_merge($email->ai_metadata ?? [], ['extracted_po' => $poData])
                        ]);
                        break; // Stop after first successful PO extraction
                    }
                }
            }
        }
    }

    protected function isExtractable($attachment)
    {
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        return in_array($attachment->mime_type, $allowedMimes);
    }
}

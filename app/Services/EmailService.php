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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

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
            
            // Log total messages to debug (Optimized to avoid memory issues)
            $allMessagesCount = $inbox->messages()->all()->count();
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
        try {
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
        } catch (\Exception $e) {
            Log::error('AI Email Analysis Failed for Email ID ' . $email->id . ': ' . $e->getMessage());
        }
    }

    protected function isExtractable($attachment)
    {
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        return in_array($attachment->mime_type, $allowedMimes);
    }

    /**
     * Send an email using Company SMTP settings.
     */
    public function sendEmail($to, $subject, $body, $attachments = [])
    {
        $company = Company::first();
        $settings = $company->settings['email'] ?? null;

        if (!$settings || empty($settings['smtp_host'])) {
            throw new \Exception('SMTP settings are not configured.');
        }

        // Dynamically configure SMTP
        Config::set('mail.mailers.smtp_dynamic', [
            'transport' => 'smtp',
            'host' => $settings['smtp_host'],
            'port' => $settings['smtp_port'],
            'encryption' => $settings['smtp_encryption'],
            'username' => $settings['smtp_username'],
            'password' => $settings['smtp_password'],
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ]);

        Config::set('mail.from.address', $settings['from_address']);
        Config::set('mail.from.name', $settings['from_name']);

        // Send Email
        Mail::mailer('smtp_dynamic')->send([], [], function ($message) use ($to, $subject, $body, $attachments, $settings) {
            $message->to($to)
                ->subject($subject)
                ->html($body)
                ->from($settings['from_address'], $settings['from_name']);

            foreach ($attachments as $file) {
                // Determine path - if uploaded file object or path string
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $message->attach($file->getRealPath(), [
                        'as' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                    ]);
                }
            }
        });

        // Save to Sent Folder (Optional - for now just return success)
        // In a real app we would save this to 'email_messages' with status='sent' or folder='sent'
        
        return true;
    }
}

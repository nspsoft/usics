<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailInboxController extends Controller
{
    /**
     * Display the AI Inbox list.
     */
    public function index(Request $request)
    {
        $query = EmailMessage::with(['customer:id,name', 'attachments'])
            ->orderByDesc('email_date');

        $status = (string) $request->input('status', '');
        if (in_array($status, ['unread', 'read', 'archived', 'sent'], true)) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['unread', 'read']);
        }

        // Filter by Intent
        if ($request->has('intent') && $request->intent) {
            $query->where('intent', $request->intent);
        }

        if ($request->boolean('urgent')) {
            $query->where('urgency_score', '>=', 0.7);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhere('body_text', 'like', "%{$request->search}%")
                  ->orWhere('from_name', 'like', "%{$request->search}%")
                  ->orWhere('from_address', 'like', "%{$request->search}%");
            });
        }

        $emails = $query->paginate(20);

        return Inertia::render('Sales/Email/Index', [
            'emails' => $emails,
            'filters' => $request->only(['search', 'intent', 'status', 'urgent']),
        ]);
    }

    /**
     * Display a specific email with its AI analysis.
     */
    public function show(EmailMessage $email)
    {
        $email->load(['customer', 'attachments']);
        
        if ($email->status === 'unread') {
            $email->update(['status' => 'read']);
        }

        return Inertia::render('Sales/Email/Show', [
            'email' => $email
        ]);
    }

    /**
     * Clear and sync emails from the IMAP server.
     */
    public function sync(EmailService $emailService, Request $request)
    {
        try {
            $count = $emailService->fetchEmails();
            
            if ($request->wantsJson()) {
                return response()->json(['message' => "Processed {$count} new emails.", 'count' => $count]);
            }

            return back()->with('success', "Processed {$count} new emails.");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => "Failed to sync: " . $e->getMessage()], 500);
            }
            return back()->with('error', "Failed to sync: " . $e->getMessage());
        }
    }

    /**
     * Send a new email.
     */
    public function store(Request $request, EmailService $emailService)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        try {
            $company = \App\Models\Company::first();
            $emailSettings = $company->settings['email'] ?? [];
            $fromAddress = $emailSettings['from_address'] ?? config('mail.from.address');
            $fromName = $emailSettings['from_name'] ?? config('mail.from.name');

            // Send via SMTP
            $emailService->sendEmail(
                $request->to,
                $request->subject,
                $request->body,
                $request->file('attachments') ?? []
            );

            // Save to Database (Sent Folder)
            $sentEmail = EmailMessage::create([
                'message_id' => '<' . time() . '.' . uniqid() . '@' . request()->getHost() . '>', // Mock Message-ID
                'subject' => $request->subject,
                'from_address' => $fromAddress,
                'from_name' => $fromName,
                'to_address' => $request->to,
                'body_text' => strip_tags($request->body),
                'body_html' => nl2br(e($request->body)),
                'status' => 'sent', // Mark as sent
                'intent' => 'outgoing',
                'email_date' => now(),
            ]);

            // Save Attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('email-attachments', 'public');
                    $sentEmail->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Email sent successfully.']);
            }

            return back()->with('success', 'Email sent successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    /**
     * Delete an email message.
     */
    public function destroy(EmailMessage $email)
    {
        $email->delete();
        return redirect()->route('sales.emails.index')->with('success', 'Email deleted.');
    }
}

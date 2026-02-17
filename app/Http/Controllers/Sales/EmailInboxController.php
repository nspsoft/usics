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
        $emails = EmailMessage::with(['customer:id,name', 'attachments'])
            ->orderByDesc('email_date')
            ->paginate(20);

        return Inertia::render('Sales/Email/Index', [
            'emails' => $emails,
            'filters' => $request->only(['search', 'intent', 'status']),
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
    public function sync(EmailService $emailService)
    {
        try {
            $count = $emailService->fetchEmails();
            return back()->with('success', "Processed {$count} new emails.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed to sync: " . $e->getMessage());
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

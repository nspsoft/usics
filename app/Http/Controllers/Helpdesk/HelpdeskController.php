<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Models\HelpdeskTicket;
use App\Models\HelpdeskTicketReply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HelpdeskController extends Controller
{
    public function index(Request $request): Response
    {
        $query = HelpdeskTicket::with(['user', 'assignedTo'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->category, function ($q, $cat) {
                $q->where('category', $cat);
            })
            ->when($request->priority, function ($q, $prio) {
                $q->where('priority', $prio);
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->my_tickets, function ($q) {
                $q->where('user_id', auth()->id());
            });

        $tickets = (clone $query)->orderByDesc('id')->paginate(15)->withQueryString();

        $stats = [
            'total' => HelpdeskTicket::count(),
            'open' => HelpdeskTicket::where('status', 'open')->count(),
            'in_progress' => HelpdeskTicket::where('status', 'in_progress')->count(),
            'resolved' => HelpdeskTicket::whereIn('status', ['resolved', 'closed'])->count(),
        ];

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Helpdesk/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'users' => $users,
            'filters' => $request->only(['search', 'category', 'priority', 'status', 'my_tickets']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Helpdesk/Create', [
            'referralUrl' => $request->query('url', ''),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:bug,revision,feature_request',
            'priority' => 'required|in:low,medium,high,critical',
            'description' => 'required|string',
            'url' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip|max:10240', // 10MB
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('helpdesk/attachments', 'public');
        }

        $ticket = HelpdeskTicket::create([
            'ticket_number' => HelpdeskTicket::generateTicketNumber(),
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
            'description' => $validated['description'],
            'url' => $validated['url'] ?? null,
            'attachment_path' => $attachmentPath,
        ]);

        return redirect()->route('helpdesk.show', $ticket->id)
            ->with('success', 'Tiket helpdesk berhasil dibuat!');
    }

    public function show(HelpdeskTicket $ticket): Response
    {
        $ticket->load(['user', 'assignedTo', 'replies.user']);
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Helpdesk/Show', [
            'ticket' => $ticket,
            'users' => $users,
        ]);
    }

    public function reply(Request $request, HelpdeskTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'is_internal' => 'nullable|boolean',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip|max:10240',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('helpdesk/replies', 'public');
        }

        $ticket->replies()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_internal' => $validated['is_internal'] ?? false,
            'attachment_path' => $attachmentPath,
        ]);

        // If user replies and status was pending_user, set to in_progress
        if ($ticket->status === 'pending_user' && $ticket->user_id === auth()->id()) {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Balasan berhasil dikirim!');
    }

    public function updateStatus(Request $request, HelpdeskTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,pending_user,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $updateData = [
            'status' => $validated['status'],
        ];

        if (array_key_exists('assigned_to', $validated)) {
            $updateData['assigned_to'] = $validated['assigned_to'];
        }

        if (in_array($validated['status'], ['resolved', 'closed']) && !$ticket->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);

        return back()->with('success', 'Status tiket berhasil diperbarui!');
    }
}

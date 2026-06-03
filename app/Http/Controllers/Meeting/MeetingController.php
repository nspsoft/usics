<?php

namespace App\Http\Controllers\Meeting;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\MeetingActionItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Meeting::with(['chairperson', 'secretary'])
            ->withCount(['attendees', 'actionItems'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('discussion_notes', 'like', "%{$search}%");
                });
            })
            ->when($request->type, function ($q, $type) {
                $q->where('type', $type);
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });

        $meetings = $query->latest('meeting_date')
            ->latest('start_time')
            ->paginate(15)
            ->withQueryString();

        // Calculate KPI values
        $totalMeetings = Meeting::count();
        $pendingActions = MeetingActionItem::where('status', '!=', 'completed')->count();
        
        $totalAttendees = MeetingAttendee::count();
        $presentCount = MeetingAttendee::where('status', 'present')->count();
        $attendanceRate = $totalAttendees > 0 ? round(($presentCount / $totalAttendees) * 100, 1) : 0;

        return Inertia::render('Meeting/Index', [
            'meetings' => $meetings,
            'filters' => $request->only(['search', 'type', 'status']),
            'kpis' => [
                'total_meetings' => $totalMeetings,
                'pending_actions' => $pendingActions,
                'attendance_rate' => $attendanceRate,
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        return Inertia::render('Meeting/Form', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'meeting_date' => 'required|date',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'location' => 'required|string|max:255',
            'type' => 'required|in:internal,external,project',
            'chairperson_id' => 'required|exists:users,id',
            'secretary_id' => 'required|exists:users,id',
            'discussion_notes' => 'nullable|string',
            'status' => 'required|in:draft,published,locked',
            'attendees' => 'required|array|min:1',
            'attendees.*.user_id' => 'nullable|exists:users,id',
            'attendees.*.guest_name' => 'nullable|string|max:255',
            'attendees.*.status' => 'required|in:present,excused,absent',
            'action_items' => 'nullable|array',
            'action_items.*.description' => 'required|string',
            'action_items.*.pic_id' => 'required|exists:users,id',
            'action_items.*.due_date' => 'required|date',
        ]);

        $meeting = DB::transaction(function () use ($validated) {
            $meeting = Meeting::create([
                'company_id' => auth()->user()->company_id ?? 1,
                'title' => $validated['title'],
                'meeting_date' => $validated['meeting_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'location' => $validated['location'],
                'type' => $validated['type'],
                'chairperson_id' => $validated['chairperson_id'],
                'secretary_id' => $validated['secretary_id'],
                'discussion_notes' => $validated['discussion_notes'] ?? null,
                'status' => $validated['status'],
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['attendees'] as $att) {
                $meeting->attendees()->create([
                    'user_id' => $att['user_id'] ?? null,
                    'guest_name' => $att['guest_name'] ?? null,
                    'status' => $att['status'],
                ]);
            }

            if (!empty($validated['action_items'])) {
                foreach ($validated['action_items'] as $item) {
                    $meeting->actionItems()->create([
                        'description' => $item['description'],
                        'pic_id' => $item['pic_id'],
                        'due_date' => $item['due_date'],
                        'status' => 'pending',
                    ]);
                }
            }

            return $meeting;
        });

        if ($meeting->status === 'published') {
            try {
                $notificationService = app(\App\Services\MeetingNotificationService::class);
                $notificationService->dispatchMeetingPublished($meeting);
                foreach ($meeting->actionItems as $item) {
                    $notificationService->dispatchActionItemAssigned($item);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to auto-dispatch notifications on store: " . $e->getMessage());
            }
        }

        return redirect()->route('meeting-command.index')
            ->with('success', 'Rapat berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): Response
    {
        $meeting = Meeting::with([
            'chairperson', 
            'secretary', 
            'creator', 
            'attendees.user', 
            'actionItems.pic'
        ])->findOrFail($id);

        return Inertia::render('Meeting/Show', [
            'meeting' => $meeting,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): Response
    {
        $meeting = Meeting::with(['attendees', 'actionItems'])->findOrFail($id);
        
        if ($meeting->status === 'locked') {
            return redirect()->route('meeting-command.show', $meeting->id)
                ->with('error', 'Meeting minutes yang terkunci tidak dapat diedit.');
        }

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        
        return Inertia::render('Meeting/Form', [
            'meeting' => $meeting,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        if ($meeting->status === 'locked') {
            return redirect()->route('meeting-command.show', $meeting->id)
                ->with('error', 'Meeting minutes yang terkunci tidak dapat diedit.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'meeting_date' => 'required|date',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'location' => 'required|string|max:255',
            'type' => 'required|in:internal,external,project',
            'chairperson_id' => 'required|exists:users,id',
            'secretary_id' => 'required|exists:users,id',
            'discussion_notes' => 'nullable|string',
            'status' => 'required|in:draft,published,locked',
            'attendees' => 'required|array|min:1',
            'attendees.*.user_id' => 'nullable|exists:users,id',
            'attendees.*.guest_name' => 'nullable|string|max:255',
            'attendees.*.status' => 'required|in:present,excused,absent',
            'action_items' => 'nullable|array',
            'action_items.*.id' => 'nullable|exists:meeting_action_items,id',
            'action_items.*.description' => 'required|string',
            'action_items.*.pic_id' => 'required|exists:users,id',
            'action_items.*.due_date' => 'required|date',
            'action_items.*.status' => 'required|in:pending,in_progress,completed',
        ]);

        $wasDraft = $meeting->status === 'draft';

        DB::transaction(function () use ($meeting, $validated) {
            $meeting->update([
                'title' => $validated['title'],
                'meeting_date' => $validated['meeting_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'location' => $validated['location'],
                'type' => $validated['type'],
                'chairperson_id' => $validated['chairperson_id'],
                'secretary_id' => $validated['secretary_id'],
                'discussion_notes' => $validated['discussion_notes'] ?? null,
                'status' => $validated['status'],
            ]);

            // Sync attendees
            $meeting->attendees()->delete();
            foreach ($validated['attendees'] as $att) {
                $meeting->attendees()->create([
                    'user_id' => $att['user_id'] ?? null,
                    'guest_name' => $att['guest_name'] ?? null,
                    'status' => $att['status'],
                ]);
            }

            // Sync action items
            $existingItemIds = array_filter(array_column($validated['action_items'] ?? [], 'id'));
            $meeting->actionItems()->whereNotIn('id', $existingItemIds)->delete();

            if (!empty($validated['action_items'])) {
                foreach ($validated['action_items'] as $item) {
                    if (isset($item['id'])) {
                        MeetingActionItem::where('id', $item['id'])->update([
                            'description' => $item['description'],
                            'pic_id' => $item['pic_id'],
                            'due_date' => $item['due_date'],
                            'status' => $item['status'],
                        ]);
                    } else {
                        $meeting->actionItems()->create([
                            'description' => $item['description'],
                            'pic_id' => $item['pic_id'],
                            'due_date' => $item['due_date'],
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        });

        // Trigger notification if transitioned from draft to published
        if ($wasDraft && $meeting->status === 'published') {
            try {
                $notificationService = app(\App\Services\MeetingNotificationService::class);
                $notificationService->dispatchMeetingPublished($meeting);
                foreach ($meeting->actionItems as $item) {
                    $notificationService->dispatchActionItemAssigned($item);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to auto-dispatch notifications on update: " . $e->getMessage());
            }
        }

        return redirect()->route('meeting-command.index')
            ->with('success', 'Rapat berhasil diubah.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        
        if ($meeting->status === 'locked') {
            return back()->with('error', 'Meeting minutes yang terkunci tidak dapat dihapus.');
        }

        $meeting->delete();

        return redirect()->route('meeting-command.index')
            ->with('success', 'Rapat berhasil dihapus.');
    }

    /**
     * Toggle action item completion status.
     */
    public function toggleActionItemStatus($id)
    {
        $item = MeetingActionItem::findOrFail($id);
        $newStatus = $item->status === 'completed' ? 'pending' : 'completed';
        $item->update(['status' => $newStatus]);

        return back()->with('success', 'Status tugas berhasil diperbarui.');
    }

    /**
     * Process raw notes with AI to extract meeting title, notes, and action items.
     */
    public function aiProcess(Request $request, \App\Services\GeminiService $geminiService)
    {
        $request->validate([
            'raw_notes' => 'required|string',
        ]);

        $result = $geminiService->generateMeetingMinutes($request->raw_notes);

        if (!$result) {
            return response()->json(['error' => 'Gagal memproses dengan AI. Pastikan AI driver/API key terkonfigurasi dengan benar.'], 422);
        }

        return response()->json($result);
    }

    /**
     * Process an uploaded audio file with AI to extract meeting title, notes, and action items.
     */
    public function aiProcessAudio(Request $request, \App\Services\GeminiService $geminiService)
    {
        $request->validate([
            'audio_file' => 'required|file|max:15360|mimes:mp3,wav,m4a,mpga,x-m4a,ogg,mp4', // 15MB limit
        ]);

        $file = $request->file('audio_file');
        $filePath = $file->getRealPath();
        $mimeType = $file->getMimeType();

        // Adjust mime types for m4a files
        if ($mimeType === 'audio/x-m4a' || $mimeType === 'video/mp4') {
            if ($file->getClientOriginalExtension() === 'm4a') {
                $mimeType = 'audio/m4a';
            }
        }

        $result = $geminiService->generateMeetingMinutesFromAudio($filePath, $mimeType);

        if (!$result) {
            return response()->json(['error' => 'Gagal memproses audio dengan AI. Pastikan berkas valid dan kunci API terkonfigurasi dengan benar.'], 422);
        }

        return response()->json($result);
    }

    /**
     * Display Kanban and Gantt Board for all meeting action items.
     */
    public function actionItemsBoard(Request $request): Response
    {
        $actionItems = MeetingActionItem::with(['pic', 'meeting'])
            ->when($request->pic_id, function($q, $picId) {
                $q->where('pic_id', $picId);
            })
            ->orderBy('due_date', 'asc')
            ->get();

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Meeting/Board', [
            'actionItems' => $actionItems,
            'users' => $users,
            'filters' => $request->only(['pic_id']),
        ]);
    }

    /**
     * Update the status of an action item.
     */
    public function updateActionItemStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $item = MeetingActionItem::findOrFail($id);
        $item->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status tugas berhasil diperbarui.',
            'item' => $item
        ]);
    }

    /**
     * Show the public self check-in page.
     */
    public function showCheckIn($id): Response
    {
        $meeting = Meeting::with(['chairperson', 'secretary'])->findOrFail($id);
        return Inertia::render('Meeting/CheckIn', [
            'meeting' => $meeting,
            'auth_user' => auth()->user() ? [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ] : null,
        ]);
    }

    /**
     * Process the check-in request (employee or guest).
     */
    public function submitCheckIn(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);
        
        $validated = $request->validate([
            'is_guest' => 'required|boolean',
            'guest_name' => 'nullable|required_if:is_guest,true|string|max:255',
        ]);

        if ($validated['is_guest']) {
            // Create attendee as guest
            $meeting->attendees()->create([
                'guest_name' => $validated['guest_name'],
                'status' => 'present',
            ]);
            
            return redirect()->back()->with('success', 'Check-in tamu berhasil dicatat.');
        } else {
            if (!auth()->check()) {
                return response()->json(['error' => 'Anda harus login untuk check-in sebagai karyawan.'], 401);
            }
            
            // Check if user is already an attendee
            $attendee = $meeting->attendees()->where('user_id', auth()->id())->first();
            if ($attendee) {
                $attendee->update(['status' => 'present']);
            } else {
                // If not registered but present, add them dynamically
                $meeting->attendees()->create([
                    'user_id' => auth()->id(),
                    'status' => 'present',
                ]);
            }
            
            return redirect()->back()->with('success', 'Check-in karyawan berhasil dicatat.');
        }
    }

    /**
     * Tanda tangani dan kunci dokumen notulensi rapat secara resmi (Approval & Sign Lock).
     */
    public function approveMeeting(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        // Security check: only the designated Chairperson of the meeting can sign off
        if (auth()->id() !== $meeting->chairperson_id) {
            return redirect()->back()->with('error', 'Hanya Pimpinan Rapat (Chairperson) yang berwenang menandatangani dokumen ini.');
        }

        $validated = $request->validate([
            'signature_image' => 'required|string', // Base64 signature image
        ]);

        // Generate cryptographic hash (SHA-256) of the meeting details to certify it
        $chairpersonName = $meeting->chairperson ? $meeting->chairperson->name : auth()->user()->name;
        $hashInput = $meeting->title . '|' . $meeting->discussion_notes . '|' . $chairpersonName . '|' . now()->toDateTimeString();
        $signatureHash = hash('sha256', $hashInput);

        $meeting->update([
            'status' => 'locked',
            'chairperson_signature' => $validated['signature_image'],
            'approved_at' => now(),
            'signature_hash' => $signatureHash,
        ]);

        return redirect()->route('meeting-command.show', $meeting->id)
            ->with('success', 'Rapat berhasil disetujui, ditandatangani, dan dikunci secara digital.');
    }

    /**
     * Dispatch WhatsApp & Email notifications manually for a published meeting.
     */
    public function dispatchNotifications(Request $request, $id)
    {
        $meeting = Meeting::with(['attendees.user.employee', 'actionItems.pic.employee'])->findOrFail($id);

        if ($meeting->status === 'draft') {
            return redirect()->back()->with('error', 'Dokumen rapat berstatus DRAFT tidak dapat dikirimkan notifikasinya. Silakan terbitkan (Publish) notulen terlebih dahulu.');
        }

        try {
            $notificationService = app(\App\Services\MeetingNotificationService::class);
            
            // 1. Dispatch meeting minutes summary to all attendees
            $notificationService->dispatchMeetingPublished($meeting);
            
            // 2. Dispatch tasks to respective PICs
            foreach ($meeting->actionItems as $item) {
                $notificationService->dispatchActionItemAssigned($item);
            }

            return redirect()->back()->with('success', 'Notifikasi WhatsApp & Email berhasil dikirim ke seluruh peserta dan PIC tugas.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to manually dispatch notifications: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengirimkan notifikasi: ' . $e->getMessage());
        }
    }
}




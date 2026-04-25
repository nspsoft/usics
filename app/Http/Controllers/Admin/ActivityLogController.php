<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use App\Exports\ActivityLogExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function dashboard(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->subDays(30);
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now();

        // 1. Activity Trend (Daily)
        $trend = Activity::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 1b. Activity Trend per User (Top 5 users)
        $topUserIds = Activity::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereNotNull('causer_id')
            ->selectRaw('causer_id, count(*) as total')
            ->groupBy('causer_id')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('causer_id');

        $trendByUser = Activity::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereIn('causer_id', $topUserIds)
            ->selectRaw('DATE(created_at) as date, causer_id, count(*) as count')
            ->groupBy('date', 'causer_id')
            ->get()
            ->groupBy('causer_id')
            ->map(function($items, $userId) {
                return [
                    'user_id' => $userId,
                    'user_name' => User::find($userId)?->name ?? 'Unknown',
                    'data' => $items->pluck('count', 'date')
                ];
            })->values();

        // 2. Action Distribution
        $events = Activity::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('event, count(*) as count')
            ->groupBy('event')
            ->get()
            ->map(function($item) {
                // Map Spatie events to readable names
                $event = $item->event ?: 'system';
                if ($item->log_name === 'auth') {
                    $event = 'access';
                }
                return [
                    'label' => ucfirst($event),
                    'count' => $item->count
                ];
            });

        // 3. Top Active Users
        $topUsers = Activity::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('causer_id, count(*) as count')
            ->whereNotNull('causer_id')
            ->groupBy('causer_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'name' => User::find($item->causer_id)?->name ?? 'Unknown',
                'count' => $item->count
            ]);

        // 4. Module Activity (Subject Types)
        $modules = Activity::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereNotNull('subject_type')
            ->selectRaw('subject_type, count(*) as count')
            ->groupBy('subject_type')
            ->orderByDesc('count')
            ->limit(6)
            ->get()
            ->map(fn($item) => [
                'name' => class_basename($item->subject_type),
                'count' => $item->count
            ]);

        // 5. Recent High-Impact Changes (Deletions or large updates)
        $recentHighImpact = Activity::with('causer')
            ->whereIn('event', ['deleted', 'restored'])
            ->orWhere(function($q) {
                $q->where('event', 'updated')->where('description', 'like', '%status%');
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'user' => $log->causer?->name ?? 'System',
                'description' => $log->description,
                'time' => $log->created_at->diffForHumans(),
                'subject' => class_basename($log->subject_type)
            ]);

        // 6. Action Breakdown by User (for drilldown)
        $actionByUser = Activity::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('event, causer_id, count(*) as count')
            ->whereNotNull('causer_id')
            ->groupBy('event', 'causer_id')
            ->get()
            ->map(function($item) {
                $event = $item->event ?: 'system';
                return [
                    'event' => ucfirst($event),
                    'user_name' => User::find($item->causer_id)?->name ?? 'Unknown',
                    'count' => $item->count
                ];
            })->groupBy('event');

        return Inertia::render('Admin/ActivityLogs/Dashboard', [
            'stats' => [
                'trend' => $trend,
                'trendByUser' => $trendByUser,
                'events' => $events,
                'actionByUser' => $actionByUser,
                'topUsers' => $topUsers,
                'modules' => $modules,
                'recent' => $recentHighImpact,
                'total' => Activity::count(),
                'today' => Activity::whereDate('created_at', today())->count(),
            ],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ]);
    }

    public function index(Request $request)
    {
        $query = Activity::with('causer')
            ->orderBy('created_at', 'desc');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%")
                  ->orWhereHas('causer', function ($sq) use ($request) {
                      $sq->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        if ($request->user_id) {
            $query->where('causer_id', $request->user_id);
        }

        if ($request->event) {
            $query->where('event', $request->event);
        }

        if ($request->subject_type) {
            $query->where('subject_type', 'like', "%{$request->subject_type}%");
        }

        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->paginate(20)
            ->withQueryString()
            ->through(function ($log) {
                $subject = class_basename($log->subject_type);
                if (!$subject && $log->log_name === 'auth' && isset($log->properties['ip'])) {
                    $subject = $log->properties['ip'];
                }

                $event = $log->event;
                if (!$event && $log->log_name === 'auth') {
                    $desc = strtolower($log->description);
                    if (str_contains($desc, 'login') || str_contains($desc, 'logged in')) {
                        $event = 'login';
                    } elseif (str_contains($desc, 'logout') || str_contains($desc, 'logged out')) {
                        $event = 'logout';
                    } elseif (str_contains($desc, 'failed')) {
                        $event = 'failed';
                    }
                }

                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'subject_type' => $subject,
                    'subject_id' => $log->subject_id,
                    'causer_name' => $log->causer ? $log->causer->name : 'System',
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'event' => $event,
                    'properties' => $log->properties,
                ];
            });

        $subjectTypes = Activity::select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->get()
            ->map(fn($item) => [
                'value' => class_basename($item->subject_type),
                'full_class' => $item->subject_type
            ])
            ->sortBy('value')
            ->values();

        $users = User::select('id', 'name')->orderBy('name')->get();
        
        $events = Activity::select('event')
            ->distinct()
            ->whereNotNull('event')
            ->get()
            ->pluck('event')
            ->sort()
            ->values();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'subject_type', 'start_date', 'end_date', 'user_id', 'event']),
            'subjectTypes' => $subjectTypes,
            'users' => $users,
            'events' => $events,
        ]);
    }

    public function show(Activity $activityLog)
    {
        $activityLog->load('causer');

        return Inertia::render('Admin/ActivityLogs/Show', [
            'log' => [
                'id' => $activityLog->id,
                'description' => $activityLog->description,
                'subject_type' => $activityLog->subject_type,
                'subject_id' => $activityLog->subject_id,
                'causer_name' => $activityLog->causer ? $activityLog->causer->name : 'System',
                'created_at' => $activityLog->created_at->format('Y-m-d H:i:s'),
                'properties' => $activityLog->properties,
                'event' => $activityLog->event,
            ]
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new ActivityLogExport($request->start_date, $request->end_date),
            'activity-logs-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function reset(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $count = Activity::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->delete();

        return back()->with('success', "Successfully deleted {$count} activity logs.");
    }
}

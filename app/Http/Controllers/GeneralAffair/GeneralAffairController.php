<?php

namespace App\Http\Controllers\GeneralAffair;

use App\Http\Controllers\Controller;
use App\Models\GaAsset;
use App\Models\GaLocation;
use App\Models\GaPmSchedule;
use App\Models\GaTicket;
use App\Models\GaVehicleBooking;
use App\Models\Vehicle;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GeneralAffairController extends Controller
{
    public function index(Request $request)
    {
        $section = $request->query('section', 'dashboard');
        
        if ($section === 'assets') {
            return redirect()->route('ga.assets.index');
        }
        
        if ($section === 'locations') {
            return redirect()->route('ga.locations.index');
        }

        if ($section === 'tickets') {
            return redirect()->route('ga.tickets.index');
        }

        if ($section === 'pm') {
            return redirect()->route('ga.pm-schedules.index');
        }

        if ($section === 'vehicles') {
            return redirect()->route('ga.vehicle-bookings.index');
        }

        if ($section === 'requests') {
            return redirect()->route('ga.requests.index');
        }

        // Live dashboard metrics
        $tickets_stats = [
            'total' => GaTicket::count(),
            'open' => GaTicket::where('status', 'open')->count(),
            'in_progress' => GaTicket::where('status', 'in_progress')->count(),
            'resolved' => GaTicket::where('status', 'resolved')->count(),
        ];

        $assets_stats = [
            'total' => GaAsset::count(),
            'good' => GaAsset::where('condition', 'Baik')->count(),
            'active' => GaAsset::where('status', 'active')->count(),
            'total_value' => (float) GaAsset::sum('price'),
        ];

        $pm_stats = [
            'total' => GaPmSchedule::count(),
            'active' => GaPmSchedule::where('status', 'active')->count(),
            'overdue' => GaPmSchedule::where('status', 'active')
                ->where('next_due_date', '<', now()->toDateString())
                ->count(),
        ];

        $fleet_stats = [
            'total' => Vehicle::whereIn('usage_type', ['passenger', 'both'])->count(),
            'available' => Vehicle::whereIn('usage_type', ['passenger', 'both'])->where('status', 'available')->count(),
            'busy' => Vehicle::whereIn('usage_type', ['passenger', 'both'])->where('status', 'busy')->count(),
            'in_use' => Vehicle::whereIn('usage_type', ['passenger', 'both'])->where('status', 'in_use')->count(),
            'maintenance' => Vehicle::whereIn('usage_type', ['passenger', 'both'])->where('status', 'maintenance')->count(),
            'active_bookings' => GaVehicleBooking::where('status', 'active')->count(),
            'pending_bookings' => GaVehicleBooking::where('status', 'pending')->count(),
        ];

        $requests_stats = [
            'total' => PurchaseRequest::where('department', 'HRGA')->count(),
            'draft' => PurchaseRequest::where('department', 'HRGA')->where('status', 'draft')->count(),
            'pending' => PurchaseRequest::where('department', 'HRGA')->whereIn('status', ['pending', 'submitted'])->count(),
            'approved' => PurchaseRequest::where('department', 'HRGA')->where('status', 'approved')->count(),
        ];

        $recent_tickets = GaTicket::with('gaLocation')->latest()->limit(5)->get();
        $recent_bookings = GaVehicleBooking::with(['user', 'vehicle'])->latest()->limit(5)->get();

        $asset_categories = GaAsset::selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->get()
            ->pluck('count', 'category')
            ->toArray();

        $ticket_statuses = [
            'Open' => $tickets_stats['open'],
            'In Progress' => $tickets_stats['in_progress'],
            'Resolved' => $tickets_stats['resolved'],
        ];

        return Inertia::render('GeneralAffair/Index', [
            'section' => $section,
            'tickets_stats' => $tickets_stats,
            'assets_stats' => $assets_stats,
            'pm_stats' => $pm_stats,
            'fleet_stats' => $fleet_stats,
            'requests_stats' => $requests_stats,
            'recent_tickets' => $recent_tickets,
            'recent_bookings' => $recent_bookings,
            'asset_categories' => $asset_categories,
            'ticket_statuses' => $ticket_statuses,
        ]);
    }
}


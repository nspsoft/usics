<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\SalesVisit;
use App\Models\Customer;
use App\Models\CRM\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;

class SalesVisitController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Cek permission: Jika admin atau manager (atau memiliki permission khusus), bisa melihat semua kunjungan.
        // Jika tidak, hanya melihat kunjungan miliknya sendiri.
        $canViewAll = $user->hasRole('admin') || $user->hasRole('manager') || $user->can('sales_crm.all_visits.view');
        
        $query = SalesVisit::with(['salesperson', 'customer', 'lead'])
            ->orderByDesc('planned_at');

        if (!$canViewAll) {
            $query->where('sales_id', $user->id);
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter sales
        if ($request->filled('sales_id') && $canViewAll) {
            $query->where('sales_id', $request->sales_id);
        }

        $visits = $query->paginate(15)->withQueryString()->map(fn ($visit) => [
            'id' => $visit->id,
            'sales_id' => $visit->sales_id,
            'sales_name' => $visit->salesperson ? $visit->salesperson->name : 'Unknown',
            'customer_id' => $visit->customer_id,
            'customer_name' => $visit->customer ? $visit->customer->name : null,
            'customer_address' => $visit->customer ? $visit->customer->full_address : null,
            'lead_id' => $visit->lead_id,
            'lead_name' => $visit->lead ? $visit->lead->name : null,
            'lead_company' => $visit->lead ? $visit->lead->company : null,
            'purpose' => $visit->purpose,
            'notes' => $visit->notes,
            'status' => $visit->status,
            'planned_at' => $visit->planned_at->format('Y-m-d H:i'),
            'check_in_at' => $visit->check_in_at ? $visit->check_in_at->format('Y-m-d H:i') : null,
            'check_out_at' => $visit->check_out_at ? $visit->check_out_at->format('Y-m-d H:i') : null,
            'check_in_lat' => $visit->check_in_lat,
            'check_in_lng' => $visit->check_in_lng,
            'check_in_address' => $visit->check_in_address,
            'check_out_lat' => $visit->check_out_lat,
            'check_out_lng' => $visit->check_out_lng,
            'check_out_address' => $visit->check_out_address,
            'summary' => $visit->summary,
        ]);

        // List customer dan lead untuk form penjadwalan
        $customers = Customer::active()->orderBy('name')->get(['id', 'name', 'address', 'city', 'latitude', 'longitude']);
        $leads = Lead::where('status', '!=', 'lost')->orderBy('name')->get(['id', 'name', 'company', 'latitude', 'longitude']);
        
        // List sales untuk filter (bagi admin/manager)
        $salesList = [];
        if ($canViewAll) {
            $salesList = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['sales', 'admin', 'manager']);
            })->orderBy('name')->get(['id', 'name']);
        }

        return Inertia::render('CRM/Visits/Index', [
            'visits' => $visits,
            'customers' => $customers,
            'leads' => $leads,
            'salesList' => $salesList,
            'canViewAll' => $canViewAll,
            'filters' => $request->only(['status', 'sales_id']),
            'title' => 'Sales Visits Tracking'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'lead_id' => 'nullable|exists:leads,id',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'planned_at' => 'required|date',
        ]);

        if (empty($validated['customer_id']) && empty($validated['lead_id'])) {
            return back()->withErrors(['customer_id' => 'Harus memilih Customer atau Lead.']);
        }

        $validated['sales_id'] = auth()->id();
        $validated['status'] = 'planned';

        SalesVisit::create($validated);

        return Redirect::back()->with('success', 'Kunjungan berhasil dijadwalkan.');
    }

    public function update(Request $request, SalesVisit $visit)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'lead_id' => 'nullable|exists:leads,id',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'planned_at' => 'required|date',
            'status' => 'required|in:planned,checked_in,completed,cancelled',
        ]);

        if (empty($validated['customer_id']) && empty($validated['lead_id'])) {
            return back()->withErrors(['customer_id' => 'Harus memilih Customer atau Lead.']);
        }

        $visit->update($validated);

        return Redirect::back()->with('success', 'Jadwal kunjungan berhasil diperbarui.');
    }

    public function destroy(SalesVisit $visit)
    {
        if ($visit->status === 'checked_in') {
            return back()->with('error', 'Kunjungan yang sedang aktif tidak dapat dihapus.');
        }

        $visit->delete();

        return Redirect::back()->with('success', 'Jadwal kunjungan berhasil dihapus.');
    }

    public function checkIn(Request $request, SalesVisit $visit)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'nullable|string',
        ]);

        $visit->update([
            'check_in_at' => now(),
            'check_in_lat' => $validated['latitude'],
            'check_in_lng' => $validated['longitude'],
            'check_in_address' => $validated['address'],
            'status' => 'checked_in',
        ]);

        // Opsional: Jika koordinat customer belum ada, otomatis update berdasarkan check-in pertama sales
        if ($visit->customer && is_null($visit->customer->latitude)) {
            $visit->customer->update([
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);
        } elseif ($visit->lead && is_null($visit->lead->latitude)) {
            $visit->lead->update([
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);
        }

        return Redirect::back()->with('success', 'Berhasil check-in kunjungan!');
    }

    public function checkOut(Request $request, SalesVisit $visit)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'nullable|string',
            'summary' => 'required|string|min:5',
        ]);

        $visit->update([
            'check_out_at' => now(),
            'check_out_lat' => $validated['latitude'],
            'check_out_lng' => $validated['longitude'],
            'check_out_address' => $validated['address'],
            'summary' => $validated['summary'],
            'status' => 'completed',
        ]);

        return Redirect::back()->with('success', 'Berhasil menyelesaikan kunjungan sales!');
    }

    public function mapView(Request $request)
    {
        $user = auth()->user();
        $canViewAll = $user->hasRole('admin') || $user->hasRole('manager') || $user->can('sales_crm.all_visits.view');

        // 1. Ambil koordinat Customer
        $customers = Customer::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'name', 'latitude', 'longitude', 'address', 'city', 'phone'])
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => 'customer',
                'latitude' => (float)$c->latitude,
                'longitude' => (float)$c->longitude,
                'address' => $c->address . ', ' . $c->city,
                'phone' => $c->phone,
            ]);

        // 2. Ambil koordinat Lead
        $leads = Lead::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'name', 'company', 'latitude', 'longitude', 'notes'])
            ->map(fn($l) => [
                'id' => $l->id,
                'name' => $l->name . ($l->company ? " ({$l->company})" : ""),
                'type' => 'lead',
                'latitude' => (float)$l->latitude,
                'longitude' => (float)$l->longitude,
                'address' => $l->notes ?? 'No details',
                'phone' => '',
            ]);

        // 3. Ambil log kunjungan aktif & selesai hari ini / belakangan ini
        $visitQuery = SalesVisit::with(['salesperson', 'customer', 'lead'])
            ->whereIn('status', ['checked_in', 'completed'])
            ->orderByDesc('updated_at');

        if (!$canViewAll) {
            $visitQuery->where('sales_id', $user->id);
        }

        // Filter Salesperson di map
        if ($request->filled('sales_id') && $canViewAll) {
            $visitQuery->where('sales_id', $request->sales_id);
        }

        $visits = $visitQuery->get()->map(fn($v) => [
            'id' => $v->id,
            'sales_name' => $v->salesperson ? $v->salesperson->name : 'Unknown',
            'client_name' => $v->customer ? $v->customer->name : ($v->lead ? $v->lead->name : 'Unknown'),
            'purpose' => $v->purpose,
            'status' => $v->status,
            'check_in_at' => $v->check_in_at ? $v->check_in_at->format('d M Y H:i') : null,
            'check_out_at' => $v->check_out_at ? $v->check_out_at->format('d M Y H:i') : null,
            'check_in_lat' => (float)$v->check_in_lat,
            'check_in_lng' => (float)$v->check_in_lng,
            'check_in_address' => $v->check_in_address,
            'check_out_lat' => (float)$v->check_out_lat,
            'check_out_lng' => (float)$v->check_out_lng,
            'check_out_address' => $v->check_out_address,
            'summary' => $v->summary,
        ]);

        $salesList = [];
        if ($canViewAll) {
            $salesList = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['sales', 'admin', 'manager']);
            })->orderBy('name')->get(['id', 'name']);
        }

        return Inertia::render('CRM/Visits/Map', [
            'customers' => $customers,
            'leads' => $leads,
            'visits' => $visits,
            'salesList' => $salesList,
            'canViewAll' => $canViewAll,
            'filters' => $request->only(['sales_id']),
            'title' => 'CRM Visits Map Dashboard'
        ]);
    }
}

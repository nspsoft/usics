<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CustomerExport;
use App\Imports\CustomerImport;
use App\Exports\Template\CustomerTemplateExport;
use App\Exports\CustomerContactExport;
use App\Imports\CustomerContactImport;
use App\Exports\Template\CustomerContactTemplateExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Customer::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%");
                });
            })
            ->when($request->type, function ($q, $type) {
                $q->where('customer_type', $type);
            });

        $customers = $query->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('Sales/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'type']),
            'customerTypes' => [
                ['value' => 'regular', 'label' => 'Regular'],
                ['value' => 'vip', 'label' => 'VIP'],
                ['value' => 'wholesale', 'label' => 'Wholesale'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Customers/Form', [
            'customer' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:customers,code',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:10240',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'payment_terms' => 'required|string|max:20',
            'payment_days' => 'required|integer|min:0',
            'customer_type' => 'required|in:regular,vip,wholesale',
            'is_active' => 'boolean',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'nullable|string|max:30',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.position' => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = hash_file('sha256', $file->getRealPath()) . '.jpg';
            $path = 'customer-photos/' . $filename;

            // Compress and Resize
            $sourceImage = imagecreatefromstring(file_get_contents($file));
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
            
            $maxWidth = 800;
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = floor($height * ($maxWidth / $width));
                $tempImage = imagescale($sourceImage, $newWidth, $newHeight);
                imagedestroy($sourceImage);
                $sourceImage = $tempImage;
            }

            ob_start();
            imagejpeg($sourceImage, null, 75);
            $imageContents = ob_get_clean();
            imagedestroy($sourceImage);

            Storage::disk('public')->put($path, $imageContents);
            $validated['profile_photo_path'] = $path;
        }

        $customer = Customer::create($validated);

        if ($request->has('contacts')) {
            $unique = [];
            foreach ((array) $request->contacts as $contact) {
                $name = trim((string) ($contact['name'] ?? ''));
                $email = strtolower(trim((string) ($contact['email'] ?? '')));
                $phone = preg_replace('/\D+/', '', (string) ($contact['phone'] ?? ''));
                $position = trim((string) ($contact['position'] ?? ''));

                if ($name === '' && $email === '' && $phone === '' && $position === '') {
                    continue;
                }

                $key = $email !== '' ? 'email:' . $email : ($phone !== '' ? 'phone:' . $phone : 'name:' . strtolower($name) . '|pos:' . strtolower($position));

                $unique[$key] = [
                    'name' => $name,
                    'email' => $email !== '' ? $email : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'position' => $position !== '' ? $position : null,
                ];
            }

            if (!empty($unique)) {
                $customer->contacts()->createMany(array_values($unique));
            }
        }

        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): Response
    {
        $customer->load(['salesOrders' => function ($q) {
            $q->latest()->limit(10);
        }]);

        return Inertia::render('Sales/Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function edit(Customer $customer): Response
    {
        $customer->load('contacts');
        
        return Inertia::render('Sales/Customers/Form', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:customers,code,' . $customer->id,
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:10240',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'tax_id' => 'nullable|string|max:50',
            'payment_terms' => 'required|string|max:20',
            'payment_days' => 'required|integer|min:0',
            'customer_type' => 'required|in:regular,vip,wholesale',
            'is_active' => 'boolean',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'nullable|string|max:30',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.position' => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($customer->profile_photo_path) {
                Storage::disk('public')->delete($customer->profile_photo_path);
            }

            $file = $request->file('photo');
            $filename = hash_file('sha256', $file->getRealPath()) . '.jpg';
            $path = 'customer-photos/' . $filename;

            // Compress and Resize
            $sourceImage = imagecreatefromstring(file_get_contents($file));
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
            
            $maxWidth = 800;
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = floor($height * ($maxWidth / $width));
                $tempImage = imagescale($sourceImage, $newWidth, $newHeight);
                imagedestroy($sourceImage);
                $sourceImage = $tempImage;
            }

            ob_start();
            imagejpeg($sourceImage, null, 75);
            $imageContents = ob_get_clean();
            imagedestroy($sourceImage);

            Storage::disk('public')->put($path, $imageContents);
            $validated['profile_photo_path'] = $path;
        }

        $customer->update($validated);

        if ($request->has('contacts')) {
            $contacts = collect((array) $request->contacts)
                ->map(function ($c) {
                    $contact = (array) $c;
                    $contact['name'] = trim((string) ($contact['name'] ?? ''));
                    $contact['email'] = strtolower(trim((string) ($contact['email'] ?? '')));
                    $contact['phone'] = preg_replace('/\D+/', '', (string) ($contact['phone'] ?? ''));
                    $contact['position'] = trim((string) ($contact['position'] ?? ''));
                    if ($contact['email'] === '') {
                        $contact['email'] = null;
                    }
                    if ($contact['phone'] === '') {
                        $contact['phone'] = null;
                    }
                    if ($contact['position'] === '') {
                        $contact['position'] = null;
                    }
                    return $contact;
                })
                ->filter(fn ($c) => ($c['name'] ?? '') !== '' || ($c['email'] ?? null) || ($c['phone'] ?? null) || ($c['position'] ?? null))
                ->values();

            $contactIds = $contacts->pluck('id')->filter()->toArray();
            $customer->contacts()->whereNotIn('id', $contactIds)->delete();

            $seen = [];
            foreach ($contacts as $contactData) {
                $key = ($contactData['email'] ?? null)
                    ? 'email:' . $contactData['email']
                    : (($contactData['phone'] ?? null)
                        ? 'phone:' . $contactData['phone']
                        : 'name:' . strtolower((string) ($contactData['name'] ?? '')) . '|pos:' . strtolower((string) ($contactData['position'] ?? '')));

                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                if (isset($contactData['id'])) {
                    $customer->contacts()->where('id', $contactData['id'])->update($contactData);
                    continue;
                }

                $match = [];
                if (!empty($contactData['email'])) {
                    $match['email'] = $contactData['email'];
                } elseif (!empty($contactData['phone'])) {
                    $match['phone'] = $contactData['phone'];
                } else {
                    $match['name'] = $contactData['name'] ?? '';
                    $match['position'] = $contactData['position'] ?? null;
                }

                $customer->contacts()->updateOrCreate($match, $contactData);
            }
        }

        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->salesOrders()->exists()) {
            return back()->with('error', 'Cannot delete customer with existing orders.');
        }

        $customer->delete();

        return redirect()->route('sales.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function export()
    {
        return Excel::download(new CustomerExport, 'customers_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        Excel::import(new CustomerImport($request->boolean('overwrite')), $request->file('file'));

        return back()->with('success', 'Customers imported successfully.');
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            return Excel::download(new \App\Exports\CustomerDataExport, 'customers_data_' . now()->format('Y-m-d') . '.xlsx');
        }
        return Excel::download(new CustomerTemplateExport, 'customers_template.xlsx');
    }

    public function exportContacts()
    {
        return Excel::download(new CustomerContactExport, 'customer_contacts_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function importContacts(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        Excel::import(new CustomerContactImport, $request->file('file'));

        return back()->with('success', 'Customer contacts imported successfully.');
    }

    public function templateContacts()
    {
        return Excel::download(new CustomerContactTemplateExport, 'customer_contacts_template.xlsx');
    }
}

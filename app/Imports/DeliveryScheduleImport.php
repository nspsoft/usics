<?php

namespace App\Imports;

use App\Models\DeliverySchedule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DeliveryScheduleImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures, Importable;

    protected $sales_name;
    protected int $importedCount = 0;
    protected int $skippedCount = 0;

    public function __construct($sales_name = null)
    {
        $this->sales_name = $sales_name;
    }

    public function model(array $row)
    {
        // Normalize keys: support both short keys and slugged-heading keys
        $row = $this->normalizeKeys($row);

        $customer = Customer::where('code', $row['customer_code'] ?? null)->first();
        $product = Product::where('sku', $row['product_sku'] ?? null)->first();

        if (!$customer || !$product) {
            $this->skippedCount++;
            return null; // Skip if not found
        }

        // Handle Date Format from Excel
        try {
            $dateValue = $row['delivery_date'] ?? null;
            if (!$dateValue) {
                $this->skippedCount++;
                return null;
            }
            if (is_numeric($dateValue)) {
                $date = Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } else {
                $date = Carbon::parse($dateValue)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            $this->skippedCount++;
            return null;
        }

        $this->importedCount++;

        return DeliverySchedule::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'delivery_date' => $date,
                'po_number' => $row['po_number'] ?? null,
            ],
            [
                'qty_scheduled' => $row['qty'] ?? 0,
                'reference_number' => $row['reference_number'] ?? null,
                'notes' => $row['notes'] ?? null,
                'created_by' => auth()->id(),
                'sales_name' => $this->sales_name,
            ]
        );
    }

    /**
     * Normalize column keys to handle different heading formats.
     * Maps slugged headings (e.g. 'po_number_optional') back to expected keys (e.g. 'po_number').
     */
    protected function normalizeKeys(array $row): array
    {
        $mapping = [
            // Slugged heading key => normalized key
            'customer_code'                   => 'customer_code',
            'customer_name_reference_only'    => 'customer_name',
            'customer_name'                   => 'customer_name',
            'po_number_optional'              => 'po_number',
            'po_number'                       => 'po_number',
            'product_sku'                     => 'product_sku',
            'product_name_reference_only'     => 'product_name',
            'product_name'                    => 'product_name',
            'qty'                             => 'qty',
            'reference_number_optional'       => 'reference_number',
            'reference_number'                => 'reference_number',
            'notes_optional'                  => 'notes',
            'notes'                           => 'notes',
            'delivery_date_yyyy_mm_dd'        => 'delivery_date',
            'delivery_date'                   => 'delivery_date',
        ];

        $normalized = [];
        foreach ($row as $key => $value) {
            $normalizedKey = $mapping[$key] ?? $key;
            // Don't overwrite if already set (prefer first matching)
            if (!isset($normalized[$normalizedKey])) {
                $normalized[$normalizedKey] = $value;
            }
        }

        return $normalized;
    }

    public function rules(): array
    {
        // Use loose validation here; actual matching is done in model()
        return [
            '*.customer_code' => 'nullable',
            '*.product_sku' => 'nullable',
            '*.delivery_date' => 'nullable',
            '*.delivery_date_yyyy_mm_dd' => 'nullable',
            '*.qty' => 'nullable',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }
}

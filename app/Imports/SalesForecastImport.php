<?php

namespace App\Imports;

use App\Models\SalesForecast;
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

class SalesForecastImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures, Importable;

    protected $salesName;
    protected int $importedCount = 0;
    protected int $skippedCount = 0;

    public function __construct($salesName = null)
    {
        $this->salesName = $salesName;
    }

    public function model(array $row)
    {
        $customer = Customer::where('code', $row['customer_code'] ?? null)->first();
        $product = Product::where('sku', $row['product_sku'] ?? null)->first();

        if (!$customer || !$product) {
            $this->skippedCount++;
            return null;
        }

        // Handle Date Format from Excel
        try {
            $periodValue = $row['period'] ?? null;
            if (!$periodValue) {
                $this->skippedCount++;
                return null;
            }
            if (is_numeric($periodValue)) {
                $period = Date::excelToDateTimeObject($periodValue)->format('Y-m-01');
            } else {
                $period = Carbon::parse($periodValue)->format('Y-m-01');
            }
        } catch (\Exception $e) {
            $this->skippedCount++;
            return null;
        }

        $this->importedCount++;

        $data = [
            'qty_forecast' => $row['qty'] ?? 0,
            'notes' => $row['notes'] ?? null,
            'sales_name' => $this->salesName,
        ];

        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return SalesForecast::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'period' => $period,
            ],
            $data
        );
    }

    public function rules(): array
    {
        return [
            '*.customer_code' => 'nullable',
            '*.product_sku' => 'nullable',
            '*.period' => 'nullable',
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

<?php

namespace App\Exports;

use App\Models\Inventory\ProductPartner;
use App\Models\Customer;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ProductAliasExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ProductPartner::with(['product', 'partner'])
            ->get()
            ->map(function ($alias) {
                $partnerType = '';
                if ($alias->partner_type === Customer::class || $alias->partner_type === 'App\Models\Customer' || $alias->partner_type === 'App\Models\CRM\Customer') {
                    $partnerType = 'customer';
                } elseif ($alias->partner_type === Supplier::class || $alias->partner_type === 'App\Models\Supplier') {
                    $partnerType = 'supplier';
                }

                return [
                    'product_sku' => $alias->product ? $alias->product->sku : '',
                    'partner_name' => $alias->partner ? $alias->partner->name : '',
                    'partner_type' => $partnerType,
                    'alias_sku' => $alias->alias_sku,
                    'alias_name' => $alias->alias_name,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'product_sku',
            'partner_name',
            'partner_type',
            'alias_sku',
            'alias_name',
        ];
    }

    public function title(): string
    {
        return 'Product Aliases Data';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_BLACK]],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Colors
                // Primary (Blue): product_sku
                $event->sheet->getDelegate()->getStyle('A1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFC9DAF8'); // Light Blue

                // Mandatory (Yellow): partner_name, partner_type
                $event->sheet->getDelegate()->getStyle('B1:C1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF2CC'); // Light Yellow

                // Optional (Green): alias_sku, alias_name
                $event->sheet->getDelegate()->getStyle('D1:E1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9EAD3'); // Light Green

                // Comments
                $event->sheet->getDelegate()->getComment('A1')->getText()->createTextRun('Primary Key: Product SKU must exist in the system.');
                $event->sheet->getDelegate()->getComment('B1')->getText()->createTextRun('Mandatory: Exact name of the Customer or Supplier as registered in Contacts.');
                $event->sheet->getDelegate()->getComment('C1')->getText()->createTextRun('Mandatory: Fill with "customer" or "supplier" (lowercase).');
                $event->sheet->getDelegate()->getComment('D1')->getText()->createTextRun('Optional: The SKU code used by the partner. Required if Alias Name is empty.');
                $event->sheet->getDelegate()->getComment('E1')->getText()->createTextRun('Optional: The Product Name used by the partner. Required if Alias SKU is empty.');
            },
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\SalesOrderItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class SalesOrderItemExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function query()
    {
        return SalesOrderItem::query()
            ->with(['salesOrder.customer', 'salesOrder.warehouse', 'product', 'unit'])
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->select('sales_order_items.*')
            ->when($this->filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('product', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('salesOrder', function ($so) use ($search) {
                        $so->where('so_number', 'like', "%{$search}%")
                           ->orWhere('customer_po_number', 'like', "%{$search}%")
                           ->orWhereHas('customer', function ($c) use ($search) {
                               $c->where('name', 'like', "%{$search}%");
                           });
                    });
                });
            })
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->where('sales_orders.status', $status);
            })
            ->when($this->filters['customer'] ?? null, function ($q, $customer) {
                $q->where('sales_orders.customer_id', $customer);
            })
            ->when($this->filters['date_range'] ?? null, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('sales_orders.order_date', $range);
                }
            })
            ->orderBy('sales_orders.order_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'SO Number',
            'Order Date',
            'Customer',
            'No PO',
            'Product SKU',
            'Product Name',
            'Qty Ordered',
            'Qty Delivered',
            'Qty Returned',
            'Balance (Outstanding)',
            'Qty Invoiced',
            'Unit',
            'Unit Price',
            'Total Price',
            'Status',
        ];
    }

    public function map($item): array
    {
        return [
            $item->salesOrder?->so_number ?? '-',
            $item->salesOrder?->order_date ? $item->salesOrder->order_date->format('Y-m-d') : '-',
            $item->salesOrder?->customer?->name ?? '-',
            $item->salesOrder?->customer_po_number ?? '-',
            $item->product?->sku ?? '-',
            $item->product?->name ?? '-',
            $item->qty,
            $item->qty_delivered,
            $item->qty_returned,
            $item->remaining_qty,
            $item->qty_invoiced,
            $item->unit?->name ?? '-',
            $item->unit_price,
            $item->subtotal,
            $item->salesOrder?->status ? ucfirst($item->salesOrder->status) : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            4 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFEFEFEF'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $company = Company::query()
                    ->where('is_active', true)
                    ->select(['name', 'legal_name', 'address', 'city', 'state', 'postal_code', 'country', 'phone', 'email', 'website'])
                    ->first();

                $companyName = $company?->legal_name ?: ($company?->name ?: 'JIDOKA');

                $companyAddress = trim(implode(', ', array_filter([
                    $company?->address,
                    $company?->city,
                    $company?->state,
                    $company?->postal_code,
                    $company?->country,
                ])));

                $companyContact = trim(implode(' | ', array_filter([
                    $company?->phone ? ('Tel: ' . $company->phone) : null,
                    $company?->email,
                    $company?->website,
                ])));

                $highestColumn = $sheet->getHighestColumn();

                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue('A1', $companyName);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A2:{$highestColumn}2");
                $sheet->setCellValue('A2', 'SO ITEMS REPORT');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A3:{$highestColumn}3");
                $sheet->setCellValue('A3', trim(implode('  ', array_filter([
                    'Export Date: ' . now()->format('d F Y H:i'),
                    $companyAddress,
                    $companyContact,
                ]))));
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $cellRange = 'A4:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("G5:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("M5:N{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}

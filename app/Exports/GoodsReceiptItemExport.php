<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\GoodsReceiptItem;
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

class GoodsReceiptItemExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
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
        return GoodsReceiptItem::query()
            ->with(['goodsReceipt.supplier', 'goodsReceipt.warehouse', 'goodsReceipt.purchaseOrder', 'product', 'unit'])
            ->leftJoin('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
            ->select('goods_receipt_items.*')
            ->when($this->filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('product', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('goodsReceipt', function ($gr) use ($search) {
                        $gr->where('grn_number', 'like', "%{$search}%")
                           ->orWhere('delivery_note_number', 'like', "%{$search}%")
                           ->orWhereHas('supplier', function ($s) use ($search) {
                               $s->where('name', 'like', "%{$search}%");
                           });
                    });
                });
            })
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->where('goods_receipts.status', $status);
            })
            ->when($this->filters['supplier'] ?? null, function ($q, $supplier) {
                $q->where('goods_receipts.supplier_id', $supplier);
            })
            ->when($this->filters['date_range'] ?? null, function ($q, $range) {
                if (is_array($range) && count($range) === 2) {
                    $q->whereBetween('goods_receipts.receipt_date', $range);
                }
            })
            ->orderBy('goods_receipts.receipt_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'GRN Number',
            'Receipt Date',
            'PO Number',
            'Supplier',
            'Delivery Note',
            'Product Code',
            'Product Name',
            'Qty Ordered',
            'Qty Received',
            'Qty Rejected',
            'Qty Accepted',
            'Unit',
            'Unit Cost',
            'Total Value',
            'Status',
        ];
    }

    public function map($item): array
    {
        $gr = $item->goodsReceipt;

        return [
            $gr?->grn_number ?? '-',
            $gr?->receipt_date ? $gr->receipt_date->format('Y-m-d') : '-',
            $gr?->purchaseOrder?->po_number ?? '-',
            $gr?->supplier?->name ?? '-',
            $gr?->delivery_note_number ?? '-',
            $item->product->code ?? $item->product->sku ?? '-',
            $item->product->name ?? '-',
            $item->qty_ordered,
            $item->qty_received,
            $item->qty_rejected,
            $item->qty_accepted,
            $item->unit->name ?? '-',
            $item->unit_cost,
            $item->total_value,
            $gr?->status ? ucfirst($gr->status) : '-',
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
                $sheet->setCellValue('A2', 'GR ITEMS REPORT');
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
                $sheet->getStyle("H5:K{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("M5:N{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}

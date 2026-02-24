<?php

namespace App\Exports;

use App\Models\Bom;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Maatwebsite\Excel\Events\AfterSheet;

class BomExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithCustomStartCell
{
    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        return Bom::with(['product', 'unit', 'components.product', 'components.unit'])
            ->orderBy('code')
            ->get()
            ->flatMap(function (Bom $bom) {
                if ($bom->components->isEmpty()) {
                    return [['bom' => $bom, 'component' => null]];
                }

                return $bom->components->map(function ($component) use ($bom) {
                    return ['bom' => $bom, 'component' => $component];
                });
            });
    }

    public function headings(): array
    {
        return [
            'BOM Code',
            'BOM Name',
            'Version',
            'Status',
            'Product Code',
            'Product Name',
            'Output Qty',
            'Output Unit',
            'Component Code',
            'Component Name',
            'Component Qty',
            'Component Unit',
            'Scrap %',
        ];
    }

    public function map($row): array
    {
        $bom = $row['bom'];
        $component = $row['component'];

        return [
            $bom->code,
            $bom->name,
            $bom->version,
            $bom->status,
            $bom->product?->sku,
            $bom->product?->name,
            $bom->qty,
            $bom->unit?->symbol,
            $component?->product?->sku,
            $component?->product?->name,
            $component?->qty,
            $component?->unit?->symbol,
            $component?->scrap_rate,
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

                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue('A1', 'BILL OF MATERIALS');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells('A2:M2');
                $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d F Y H:i'));
                $sheet->getStyle('A2')->applyFromArray([
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
            },
        ];
    }
}


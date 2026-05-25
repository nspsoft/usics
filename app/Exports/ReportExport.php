<?php

namespace App\Exports;

use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ReportExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithEvents, WithStyles
{
    protected $data;
    protected $headings;
    protected $mapCallback;
    protected $title;

    public function __construct($data, array $headings, callable $mapCallback, ?string $title = null)
    {
        $this->data = $data;
        $this->headings = $headings;
        $this->mapCallback = $mapCallback;
        $this->title = $title;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        return ($this->mapCallback)($row);
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
                $sheet->setCellValue('A2', $this->title ?: 'REPORT');
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
            },
        ];
    }
}

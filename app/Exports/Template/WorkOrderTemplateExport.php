<?php

namespace App\Exports\Template;

use App\Models\Bom;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WorkOrderTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $bom = Bom::orderBy('name')->first();
        $warehouse = Warehouse::orderBy('name')->first();
        $supplier = Supplier::orderBy('name')->first();

        $bomName = $bom?->name ?? 'BOM Sample';
        $warehouseName = $warehouse?->name ?? 'Main Warehouse';
        $supplierName = $supplier?->name ?? 'Supplier Sample';

        $today = now()->format('Y-m-d');
        $end = now()->addDays(7)->format('Y-m-d');

        return new Collection([
            [
                $bomName,
                $warehouseName,
                100,
                $today,
                $end,
                'normal',
                'internal',
                '',
                'Contoh WO internal (status akan langsung Confirmed saat import)',
            ],
            [
                $bomName,
                $warehouseName,
                200,
                $today,
                $end,
                'high',
                'subcontract',
                $supplierName,
                'Contoh WO subcontract (Supplier Name wajib)',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'BOM Name',
            'Warehouse Name',
            'Qty Planned',
            'Planned Start',
            'Planned End',
            'Priority',
            'Production Type',
            'Supplier Name',
            'Notes',
        ];
    }
}


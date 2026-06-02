<!DOCTYPE html>
<html>
<head>
    <title>Stock Opname Summary Print</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-logo-img {
            height: 55px;
            float: left;
            margin-right: 15px;
        }
        .company-header-text {
            color: #E21E26;
            font-weight: 900;
            font-style: italic;
            font-size: 24pt;
            letter-spacing: -1px;
            margin: 0;
            line-height: 1;
        }
        .company-header-sub {
            color: #003680;
            font-weight: 800;
            font-size: 11pt;
            margin: -2px 0 5px 0;
        }
        .company-address {
            font-size: 9pt;
            line-height: 1.3;
            clear: left;
            padding-top: 5px;
        }
        .doc-title {
            text-align: right;
            font-size: 18pt;
            font-weight: 900;
            font-style: italic;
            color: #008000;
        }
        .items-table {
            border: 1pt solid #000;
            margin-top: 15px;
        }
        .items-table th {
            border: 1pt solid #000;
            padding: 6px 4px;
            background-color: #f9f9f9;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
        }
        .items-table td {
            border: 1pt solid #000;
            padding: 6px 4px;
            vertical-align: top;
            font-size: 8.5pt;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #008000; color: white; border: none; cursor: pointer; border-radius: 5px; font-weight: bold;">PRINT SUMMARY</button>
    </div>

    <table class="header-table">
        <tr>
            <td width="60%">
                <img src="/images/jri-official-logo.png" alt="logo" class="company-logo-img">
                <div>
                    <div class="company-header-text">jidoka</div>
                    <div class="company-header-sub">PT. JIDOKA RESULT INDONESIA</div>
                </div>
                <div class="company-address">
                    Kawasan Industri JABABEKA I, Jl. Jababeka II Blok C No. 19 L<br>
                    Cikarang Utara, Bekasi 17530 Jawa Barat<br>
                    Telp : +62 21 89383915
                </div>
            </td>
            <td width="40%">
                <div style="float: right;">
                    <div class="doc-title" style="text-align: left; margin-bottom: 5px;">STO SUMMARY</div>
                    <table style="margin-top: 0; float: left;">
                        <tr>
                            <td class="font-bold">Tanggal</td>
                            <td width="15" class="text-center">:</td>
                            <td class="text-right font-bold">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</td>
                        </tr>
                        @if(!empty($filters['warehouse_id']))
                            <tr>
                                <td class="font-bold">Warehouse</td>
                                <td class="text-center">:</td>
                                <td class="text-right font-bold">{{ optional($opnames->first()?->warehouse)->name ?? '-' }}</td>
                            </tr>
                        @endif
                        @if(!empty($filters['status']))
                            <tr>
                                <td class="font-bold">Status</td>
                                <td class="text-center">:</td>
                                <td class="text-right font-bold uppercase">{{ str_replace('_', ' ', $filters['status']) }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="9%">No</th>
                <th width="18%">No. STO</th>
                <th width="20%">Warehouse</th>
                <th width="15%">Created By</th>
                <th width="8%">Items</th>
                <th width="10%">System (Rp)</th>
                <th width="10%">Physical (Rp)</th>
                <th width="10%">Variance (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($opnames as $i => $opname)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="font-bold">{{ $opname->opname_number }}</td>
                    <td>{{ $opname->warehouse->name ?? '-' }}</td>
                    <td>{{ $opname->createdBy->name ?? '-' }}</td>
                    <td class="text-center font-bold">{{ (int) ($opname->items_count ?? 0) }}</td>
                    <td class="text-right font-bold">{{ number_format((float) ($opname->system_value ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format((float) ($opname->physical_value ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format((float) ($opname->variance_value ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right font-bold">TOTAL</td>
                <td class="text-right font-bold">{{ number_format((float) ($totals['system_value'] ?? 0), 0, ',', '.') }}</td>
                <td class="text-right font-bold">{{ number_format((float) ($totals['physical_value'] ?? 0), 0, ',', '.') }}</td>
                <td class="text-right font-bold">{{ number_format((float) ($totals['variance_value'] ?? 0), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>


<!DOCTYPE html>
<html>
<head>
    <title>STO Summary Validation</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4 landscape;
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
        .items-table {
            border: 1pt solid #000;
            margin-top: 10px;
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
        .badge {
            display: inline-block;
            padding: 6px 10px;
            border: 1pt solid #008000;
            background: #eaffea;
            color: #008000;
            font-weight: bold;
            font-size: 9pt;
        }
        a { color: #003680; text-decoration: none; }
    </style>
</head>
<body>
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <div class="badge">VALIDATED SUMMARY</div>
            <div style="margin-top: 8px; font-size: 11pt; font-weight: bold;">Stock Opname Summary</div>
            <div style="margin-top: 2px;">Tanggal: <span class="font-bold">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span></div>
        </div>
        <div style="text-align: right; font-size: 8pt; color: #555;">
            Dicetak: {{ date('d/m/Y H:i') }}
        </div>
    </div>

    <div style="margin-top: 10px; font-size: 8pt; color: #555;">
        Scan QR dari dokumen untuk membuka halaman ini. Klik nomor STO untuk validasi masing-masing sesi.
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="14%">No. STO</th>
                <th width="18%">Warehouse</th>
                <th width="14%">Created By</th>
                <th width="7%">Items</th>
                <th width="8%">Status</th>
                <th width="34%">Link Validasi</th>
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
                    <td class="text-center font-bold">{{ strtoupper(str_replace('_', ' ', $opname->status)) }}</td>
                    <td style="font-size: 8pt;">
                        <a href="{{ route('inventory.opname.public-validate', $opname->public_uuid ?: $opname->id) }}" target="_blank">
                            {{ route('inventory.opname.public-validate', $opname->public_uuid ?: $opname->id) }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>


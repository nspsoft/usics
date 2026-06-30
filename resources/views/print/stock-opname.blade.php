<!DOCTYPE html>
<html>
<head>
    <title>Stock Opname - {{ $opname->opname_number }}</title>
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
            font-size: 20pt;
            font-weight: 900;
            font-style: italic;
            color: #008000;
        }
        .details-section {
            margin-top: 20px;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 5px 10px;
            font-weight: bold;
            border: 1pt solid #000;
            margin-top: 15px;
            text-transform: uppercase;
            font-size: 8pt;
        }
        .items-table {
            border: 1pt solid #000;
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
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .uppercase { text-transform: uppercase; }

        .verification-row {
            margin-top: 30px;
        }
        .qr-box {
            text-align: center;
            width: 100px;
        }
        .qr-image {
            width: 80px;
            height: 80px;
            display: block;
            margin: 0 auto 5px;
        }
        .qr-text {
            font-size: 7pt;
            color: #555;
            font-weight: bold;
        }
        .sig-table {
            margin-top: 30px;
        }
        .sig-table td {
            text-align: center;
            vertical-align: top;
        }
        .sig-box {
            border: 0.5pt solid #000;
            height: 70px;
            margin: 5px 20px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #008000; color: white; border: none; cursor: pointer; border-radius: 5px; font-weight: bold;">PRINT STOCK OPNAME</button>
    </div>

    <table class="header-table">
        <tr>
            <td width="60%">
                <img src="{{ \App\Models\AppSetting::get('company_logo_path', '/images/jri-official-logo.png') }}" alt="logo" class="company-logo-img">
                <div>
                    <div class="company-header-text">{{ \App\Models\AppSetting::get('company_logo_text', 'jidoka') }}</div>
                    <div class="company-header-sub">{{ \App\Models\AppSetting::get('company_full_name', 'PT. JIDOKA RESULT INDONESIA') }}</div>
                </div>
                <div class="company-address">
                    {!! nl2br(e(\App\Models\AppSetting::get('company_address', 'Kawasan Industri JABABEKA I, Jl. Jababeka II Blok C No. 19 L
Cikarang Utara, Bekasi 17530 Jawa Barat
Telp : +62 21 89383915'))) !!}
                </div>
            </td>
            <td width="40%">
                <div style="float: right;">
                    <div class="doc-title" style="font-size: 18pt; text-align: left; margin-bottom: 5px;">STOCK OPNAME</div>
                    <div class="text-left font-bold" style="color: #666; font-size: 8pt; margin-bottom: 10px;">(STOCK OPNAME / STOCK COUNT)</div>
                    <table style="margin-top: 0; float: left;">
                        <tr>
                            <td class="font-bold">No. STO</td>
                            <td width="15" class="text-center">:</td>
                            <td class="text-right font-bold">{{ $opname->opname_number }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tanggal</td>
                            <td class="text-center">:</td>
                            <td class="text-right font-bold">{{ \Carbon\Carbon::parse($opname->opname_date)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Status</td>
                            <td class="text-center">:</td>
                            <td class="text-right font-bold uppercase">{{ str_replace('_', ' ', $opname->status) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="details-section">
        <table width="100%" style="border: 1pt solid #000; padding: 10px;">
            <tr>
                <td width="55%">
                    <div class="font-bold uppercase" style="font-size: 8pt; text-decoration: underline; margin-bottom: 5px;">Informasi Stock Opname:</div>
                    <table style="font-size: 9pt;">
                        <tr>
                            <td width="120" class="font-bold">Gudang</td>
                            <td width="10">:</td>
                            <td class="font-bold">{{ $opname->warehouse->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Lokasi (Header)</td>
                            <td>:</td>
                            <td>{{ $opname->location ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Count Mode</td>
                            <td>:</td>
                            <td class="uppercase">{{ str_replace('_', ' ', $opname->count_mode ?? '-') }}</td>
                        </tr>
                    </table>
                </td>
                <td width="45%" style="text-align: right; border-left: 0.5pt solid #ccc; padding-left: 10px;">
                    <div class="font-bold uppercase" style="font-size: 8pt; text-decoration: underline; margin-bottom: 5px;">Informasi Dokumen:</div>
                    <table style="font-size: 9pt; float: right;">
                        <tr>
                            <td class="font-bold">Created By</td>
                            <td width="10">:</td>
                            <td>{{ $opname->createdBy->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Created At</td>
                            <td>:</td>
                            <td>{{ $opname->created_at ? \Carbon\Carbon::parse($opname->created_at)->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                        <tr style="font-size: 10pt;">
                            <td class="font-bold">TOTAL ITEM</td>
                            <td>:</td>
                            <td class="font-bold">{{ number_format($opname->items->count(), 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Detail Stock Opname</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="90">Kode Item</th>
                <th>Nama Produk</th>
                <th width="65">Qty System</th>
                <th width="65">Qty Fisik</th>
                <th width="65">Selisih</th>
                <th width="45">Unit</th>
                <th width="80">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalSystem = 0;
                $totalPhysic = 0;
                $totalDiff = 0;
            @endphp
            @foreach($opname->items as $index => $item)
                @php
                    $totalSystem += (float) $item->qty_system;
                    $totalPhysic += (float) $item->qty_physic;
                    $totalDiff += (float) $item->qty_difference;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center font-mono">{{ $item->product->sku ?? '-' }}</td>
                    <td>{{ $item->product->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float) $item->qty_system, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) $item->qty_physic, 2, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format((float) $item->qty_difference, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $item->product->unit->symbol ?? $item->product->unit->code ?? 'EA' }}</td>
                    <td style="font-size: 7pt;">{{ $item->notes }}</td>
                </tr>
            @endforeach
            @if($opname->items->count() === 0)
                <tr>
                    <td colspan="8" class="text-center italic" style="padding: 15px;">Belum ada item.</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="font-bold">
                <td colspan="3" class="text-right uppercase">Total:</td>
                <td class="text-right">{{ number_format($totalSystem, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalPhysic, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalDiff, 2, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <table width="100%" class="verification-row">
        <tr>
            <td width="75%" style="vertical-align: top;">
                <div style="border: 0.5pt solid #000; padding: 10px;">
                    <div class="font-bold underline" style="font-size: 8pt; margin-bottom: 5px;">CATATAN / KETERANGAN:</div>
                    <div style="height: 60px; font-style: italic; color: #555;">
                        {{ $opname->notes ?? 'Dokumen ini sah sebagai hasil Stock Opname yang tercatat di sistem.' }}
                    </div>
                </div>
            </td>
            <td width="25%" style="text-align: right; vertical-align: top;">
                <div class="qr-box" style="float: right;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('inventory.opname.public-validate', $opname->public_uuid ?: $opname->id)) }}" class="qr-image">
                    <div class="qr-text italic">VERIFIED STO</div>
                    <div class="qr-text" style="font-size: 5pt; margin-top: 3px; color: #003680;">DOKUMEN INVENTORY SAH</div>
                </div>
            </td>
        </tr>
    </table>

    <table width="100%" class="sig-table">
        <tr>
            <td width="33%">
                Petugas Opname,
                <div class="sig-box">
                    @if($opname->createdBy && $opname->createdBy->signature_path)
                        <img src="{{ asset('storage/' . $opname->createdBy->signature_path) }}" style="max-height: 60px; max-width: 90%; margin: 5px auto; display: block; object-fit: contain;">
                    @endif
                </div>
                <div class="font-bold">( {{ $opname->createdBy->name ?? '________________' }} )</div>
                <div style="font-size: 7pt; color: #888;">{{ $opname->created_at ? \Carbon\Carbon::parse($opname->created_at)->format('d/m/Y H:i') : '' }}</div>
            </td>
            <td width="34%">
                Diperiksa (Supervisor),
                <div class="sig-box">
                    @if($opname->checkedBy && $opname->checkedBy->signature_path)
                        <img src="{{ asset('storage/' . $opname->checkedBy->signature_path) }}" style="max-height: 60px; max-width: 90%; margin: 5px auto; display: block; object-fit: contain;">
                    @endif
                </div>
                <div class="font-bold">( {{ $opname->checkedBy->name ?? '________________' }} )</div>
                @if($opname->checked_at)
                    <div style="font-size: 7pt; color: #008000;">Diverifikasi: {{ \Carbon\Carbon::parse($opname->checked_at)->format('d/m/Y H:i') }}</div>
                @endif
            </td>
            <td width="33%">
                Disetujui (Mgr),
                <div class="sig-box">
                    @if($opname->approvedBy && $opname->approvedBy->signature_path)
                        <img src="{{ asset('storage/' . $opname->approvedBy->signature_path) }}" style="max-height: 60px; max-width: 90%; margin: 5px auto; display: block; object-fit: contain;">
                    @endif
                </div>
                <div class="font-bold">( {{ $opname->approvedBy->name ?? '________________' }} )</div>
                @if($opname->approved_at)
                    <div style="font-size: 7pt; color: #008000;">Disetujui: {{ \Carbon\Carbon::parse($opname->approved_at)->format('d/m/Y H:i') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; font-size: 7.5pt; color: #aaa; text-align: center; border-top: 0.3pt solid #eee; padding-top: 5px;">
        ERP JIDOKA SYSTEM | Doc ID: {{ $opname->id }} | Dicetak: {{ date('d/m/Y H:i') }} | Status: {{ strtoupper($opname->status) }}
    </div>
</body>
</html>

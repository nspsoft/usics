<!DOCTYPE html>
<html>
<head>
    <title>Subcontract Order - {{ $order->order_number }}</title>
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
            padding: 30px;
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
            color: #E21E26;
        }
        .details-section {
            margin-top: 25px;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 5px 10px;
            font-weight: bold;
            border: 1pt solid #000;
            margin-top: 20px;
            text-transform: uppercase;
            font-size: 8pt;
        }
        .items-table {
            border: 1pt solid #000;
        }
        .items-table th {
            border: 1pt solid #000;
            padding: 10px 5px;
            background-color: #f9f9f9;
            font-weight: bold;
            text-align: center;
        }
        .items-table td {
            border: 1pt solid #000;
            padding: 10px 5px;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        .verification-row {
            margin-top: 40px;
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

        .signatures-section {
            margin-top: 50px;
        }
        .sig-table td {
            text-align: center;
            vertical-align: top;
        }
        .sig-box {
            border: 0.5pt solid #000;
            height: 80px;
            margin: 5px 20px;
        }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    @php
        $subcontractStocks = $subcontractStocks ?? [];
        $grReceipts = $grReceipts ?? [];
        $decimals = 2;
    @endphp
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #E21E26; color: white; border: none; cursor: pointer; border-radius: 5px; font-weight: bold;">PRINT ORDER</button>
    </div>

    <!-- Header -->
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
                <div class="doc-title" style="font-size: 15pt;">ORDER JASA SUBKONTRAK</div>
                <div class="text-right font-bold" style="color: #666;">(SUBCONTRACT ORDER)</div>
                <table style="margin-top: 15px; float: right;">
                    <tr>
                        <td class="font-bold">No. Order</td>
                        <td width="15" class="text-center">:</td>
                        <td class="text-right font-bold">{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Tanggal</td>
                        <td class="text-center">:</td>
                        <td class="text-right">{{ date('d F Y', strtotime($order->created_at)) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Ref. WO</td>
                        <td class="text-center">:</td>
                        <td class="text-right font-bold">{{ $order->workOrder->wo_number }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="details-section">
        <table width="100%">
            <tr>
                <td width="55%">
                    <div class="font-bold" style="margin-bottom: 5px; text-decoration: underline;">KEPADA (VENDOR/SUBKONTRAKTOR):</div>
                    <div style="padding-left: 10px; border-left: 3pt solid #E21E26;">
                        <span class="font-bold" style="font-size: 12pt;">{{ $order->supplier->name }}</span><br>
                        {!! nl2br(e($order->supplier->address)) !!}<br>
                        Telp: {{ $order->supplier->phone ?? '-' }}
                    </div>
                </td>
                <td width="45%" style="vertical-align: bottom; text-align: right;">
                    <div class="font-bold" style="margin-top: 5px;">ITEM JASA / HASIL JADI:</div>
                    <div class="font-bold" style="font-size: 11pt;">{{ $order->workOrder->product->name }}</div>
                    <div class="font-mono text-slate-500">{{ $order->workOrder->product->sku }}</div>
                    <div class="font-bold" style="font-size: 13pt; margin-top: 8px;">
                        TOTAL TARGET: {{ number_format($order->workOrder->qty_planned, 0, ',', '.') }} {{ $order->workOrder->product->unit->symbol ?? 'PCs' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Instruksi Kerja & Material yang Disediakan (JIDOKA Supplied)</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="150">Kode Material</th>
                <th>Nama Material / Komponen</th>
                <th width="100">Qty Per Unit</th>
                <th width="100">Total Kirim</th>
                <th width="60">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->workOrder->components as $index => $comp)
            @php
                $qtyPerUnit = (float) ($comp->bomComponent?->required_qty ?? $comp->bomComponent?->qty ?? 0);
                $totalKirim = (float) ($comp->qty_required ?? 0);
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center font-mono">{{ $comp->product->sku }}</td>
                <td>{{ $comp->product->name }}</td>
                <td class="text-right">{{ number_format($qtyPerUnit, $decimals, ',', '.') }}</td>
                <td class="text-right font-bold">{{ number_format($totalKirim, $decimals, ',', '.') }}</td>
                <td class="text-center">{{ $comp->product->unit->symbol ?? 'PCs' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Materials Dispatch Plan</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="130">Kode Material</th>
                <th>Nama Material / Komponen</th>
                <th width="75">Qty Required</th>
                <th width="75">Qty Dispatched</th>
                <th width="75">Qty Remaining</th>
                <th width="60">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->workOrder->components as $index => $comp)
            @php
                $qtyRequired = (float) ($comp->qty_required ?? 0);
                $qtyDispatched = (float) ($comp->qty_consumed ?? 0);
                $qtyRemaining = (float) ($comp->remaining_qty ?? ($qtyRequired - $qtyDispatched));
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center font-mono">{{ $comp->product->sku }}</td>
                <td>{{ $comp->product->name }}</td>
                <td class="text-right font-bold">{{ number_format($qtyRequired, $decimals, ',', '.') }}</td>
                <td class="text-right font-bold">{{ number_format($qtyDispatched, $decimals, ',', '.') }}</td>
                <td class="text-right font-bold">{{ number_format($qtyRemaining, $decimals, ',', '.') }}</td>
                <td class="text-center">{{ $comp->product->unit->symbol ?? 'PCs' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Subcont Stock On Hand</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="150">Kode Material</th>
                <th>Nama Material / Komponen</th>
                <th width="100">Qty Sent</th>
                <th width="100">Stock On Hand</th>
                <th width="60">Unit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subcontractStocks as $i => $row)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td class="text-center font-mono">{{ $row['product']?->sku ?? '-' }}</td>
                <td>{{ $row['product']?->name ?? '-' }}</td>
                <td class="text-right font-bold">{{ number_format((float) ($row['qty_sent'] ?? 0), $decimals, ',', '.') }}</td>
                <td class="text-right font-bold">{{ number_format((float) ($row['qty_on_hand'] ?? 0), $decimals, ',', '.') }}</td>
                <td class="text-center">{{ $row['product']?->unit?->symbol ?? 'PCs' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Subcontract Warehouse belum diset / tidak ada data stock.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">GRN Sesuai Kedatangan</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="150">No. GRN</th>
                <th>No. Surat Jalan / Penerima</th>
                <th width="100">Tanggal</th>
                <th width="80">Qty</th>
                <th width="80">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grReceipts as $i => $gr)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td class="text-center font-bold">{{ $gr['grn_number'] ?? '-' }}</td>
                <td>
                    <div class="font-bold">{{ $gr['delivery_note_number'] ?? '-' }}</div>
                    <div style="font-size: 8pt; color: #333;">Penerima/Input: {{ $gr['received_by'] ?? '-' }}</div>
                </td>
                <td class="text-center">{{ !empty($gr['receipt_date']) ? \Carbon\Carbon::parse($gr['receipt_date'])->format('d/m/Y') : '-' }}</td>
                <td class="text-right font-bold">{{ number_format((float) ($gr['qty'] ?? 0), $decimals, ',', '.') }}</td>
                <td class="text-center">{{ strtoupper($gr['status'] ?? '-') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Belum ada GRN.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px; border: 1pt solid #000; padding: 15px;">
        <div class="font-bold" style="text-decoration: underline; margin-bottom: 8px;">SYARAT & KETENTUAN (REMARKS):</div>
        <div style="font-style: italic; color: #333; line-height: 1.5; font-size: 8.5pt;">
            1. Pengerjaan harus sesuai dengan spesifikasi teknis dan standar kualitas JIDOKA.<br>
            2. Kelebihan material (scrap/waste) harus dilaporkan dan dikembalikan jika diminta.<br>
            3. Pengiriman hasil jadi harus menyertakan Surat Jalan (SJ) yang mencantumkan Nomor SCO ini.<br>
            {{ $order->notes ?? 'Mohon segera diproses sesuai jadwal yang telah disepakati.' }}
        </div>
    </div>

    <table width="100%" class="verification-row">
        <tr>
            <td width="33%" style="text-align: center;">
                PPC / Admin,
                <div class="sig-box"></div>
                <div class="font-bold">( ________________ )</div>
            </td>
            <td width="33%" style="text-align: center;">
                Disetujui Oleh,
                <div class="sig-box"></div>
                <div class="font-bold">( ________________ )</div>
            </td>
            <td width="34%" style="text-align: right; vertical-align: top;">
                <div class="qr-box" style="float: right;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('manufacturing.subcontract-orders.public-validate', $order->id)) }}" class="qr-image">
                    <div class="qr-text italic">VERIFIED SCO</div>
                    <div class="qr-text" style="font-size: 5pt; margin-top: 3px; color: #E21E26;">DOKUMEN PESANAN SAH</div>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; font-size: 7.5pt; color: #aaa; text-align: center; border-top: 0.3pt solid #eee; padding-top: 10px;">
        Dokumen ini adalah Perintah Kerja Subkontrak resmi dari sistem ERP JIDOKA. <br>
        Dicetak pada: {{ date('d/m/Y H:i') }} | Order ID: {{ $order->id }}
    </div>
</body>
</html>

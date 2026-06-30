<!DOCTYPE html>
<html>
<head>
    <title>LPB Subcont - {{ $movement->external_reference ?? $movement->id }}</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 20px;
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
            margin-top: 20px;
        }
        .details-section {
            margin-top: 25px;
        }
        .items-table {
            margin-top: 15px;
            border: 1pt solid #000;
        }
        .items-table th {
            border: 1pt solid #000;
            padding: 8px 5px;
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .items-table td {
            border: 1pt solid #000;
            padding: 8px 5px;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
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

        .signatures-section {
            margin-top: 30px;
        }
        .sig-table {
            width: 100%;
            table-layout: fixed;
        }
        .sig-table td {
            text-align: center;
            vertical-align: top;
        }
        .sig-box {
            border: 1pt solid #000;
            height: 100px;
            margin: 5px;
            position: relative;
        }
        .sig-label {
            position: absolute;
            bottom: 5px;
            width: 100%;
            text-align: center;
            font-weight: bold;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #008000; color: white; border: none; cursor: pointer; border-radius: 5px; font-weight: bold;">PRINT LPB / GRN</button>
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
                <div class="doc-title" style="font-size: 16pt;">LAPORAN PENERIMAAN BARANG</div>
                <div class="text-right font-bold" style="color: #666;">(GOODS RECEIPT NOTE)</div>
                <table style="margin-top: 10px; float: right;">
                    <tr>
                        <td class="font-bold">No. LPB</td>
                        <td width="15" class="text-center">:</td>
                        <td>LPB-{{ str_pad($movement->id, 6, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Tanggal</td>
                        <td class="text-center">:</td>
                        <td>{{ date('d F Y', strtotime($movement->created_at)) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">No. SJ Sub</td>
                        <td class="text-center">:</td>
                        <td class="font-bold">{{ $movement->external_reference ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="details-section">
        <table width="100%">
            <tr>
                <td width="50%">
                    @if($movement->type === 'production_in')
                        <div class="font-bold" style="margin-bottom: 5px;">Diterima Dari (Subcontractor):</div>
                    @else
                        <div class="font-bold" style="margin-bottom: 5px;">Diterima Oleh (Subcontractor):</div>
                    @endif
                    <div style="padding-left: 10px; border-left: 2pt solid #008000;">
                        <span class="font-bold" style="font-size: 11pt;">{{ $movement->reference->supplier->name ?? 'Unknown Supplier' }}</span><br>
                        {!! nl2br(e($movement->reference->supplier->address ?? '-')) !!}<br>
                        Telp: {{ $movement->reference->supplier->phone ?? '-' }}
                    </div>
                </td>
                <td width="50%" style="vertical-align: bottom; text-align: right;">
                    <div class="font-bold">Referensi Work Order:</div>
                    <div class="font-mono">{{ $movement->reference->workOrder->wo_number ?? '-' }}</div>
                    <div class="font-bold" style="margin-top: 5px;">
                        {{ $movement->type === 'production_in' ? 'Gudang Penerima (Internal):' : 'Gudang Penerima (Subcont):' }}
                    </div>
                    <div>{{ $movement->warehouse->name }} ({{ $movement->warehouse->code }})</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="150">Kode Barang</th>
                <th>Nama Barang / Hasil Produksi</th>
                <th width="100">Qty Diterima</th>
                <th width="80">Satuan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td class="text-center font-mono">{{ $movement->product->sku }}</td>
                <td>
                    <div class="font-bold">{{ $movement->product->name }}</div>
                    <div style="font-size: 8pt; color: #555; margin-top: 2px;">{{ $movement->notes }}</div>
                </td>
                <td class="text-right font-bold" style="font-size: 12pt;">{{ number_format($movement->qty, 0, ',', '.') }}</td>
                <td class="text-center">{{ $movement->product->unit->symbol ?? 'PCs' }}</td>
            </tr>
        </tbody>
    </table>

    <table width="100%" class="verification-row">
        <tr>
            <td width="75%" style="vertical-align: top;">
                <div style="border: 0.5pt solid #000; padding: 10px;">
                    <div class="font-bold underline" style="font-size: 8pt; margin-bottom: 5px;">KETERANGAN / CATATAN PENERIMAAN:</div>
                    <div style="height: 60px; font-style: italic; color: #555;">
                        {{ $movement->notes ?? 'Barang telah diterima dalam kondisi baik dan sesuai dengan rincian di atas.' }}
                    </div>
                </div>
            </td>
            <td width="25%" style="text-align: right; vertical-align: top;">
                <div class="qr-box" style="float: right;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('manufacturing.subcontract-orders.public-validate-grn', $movement->id)) }}" class="qr-image">
                    <div class="qr-text italic">VERIFIED LPB</div>
                    <div class="qr-text" style="font-size: 5pt; margin-top: 3px; color: #008000;">DOKUMEN PENERIMAAN SAH</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="signatures-section">
        <table class="sig-table">
            <tr>
                <td>
                    QC Inspector,
                    <div class="sig-box"><div class="sig-label">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div></div>
                </td>
                <td>
                    @if($movement->type === 'production_in')
                        Penerima (Gudang),
                    @else
                        Pengirim (Gudang),
                    @endif
                    <div class="sig-box"><div class="sig-label">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div></div>
                </td>
                <td>
                    Mengetahui,
                    <div class="sig-box"><div class="sig-label">( Produksi / PPC )</div></div>
                </td>
                <td>
                    @if($movement->type === 'production_in')
                        Pengirim (Subcont),
                    @else
                        Penerima (Subcont),
                    @endif
                    <div class="sig-box"><div class="sig-label">( {{ $movement->type === 'production_in' ? ($movement->reference->supplier->name ?? 'Subcontractor') : '____________________' }} )</div></div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 20px; font-size: 8pt; color: #888; text-align: center; border-top: 0.5pt solid #eee; padding-top: 5px;">
        Dokumen ini adalah Bukti Penerimaan Barang resmi dari sistem ERP JIDOKA. Dicetak pada {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>

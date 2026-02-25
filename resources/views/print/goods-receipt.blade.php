<!DOCTYPE html>
<html>
<head>
    <title>Goods Receipt Note - {{ $receipt->grn_number }}</title>
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
            font-size: 20pt;
            font-weight: 900;
            font-style: italic;
            color: #008000;
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
            padding: 10px 5px;
            background-color: #f0f0f0;
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
            margin: 0 auto 8px;
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
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #008000; color: white; border: none; cursor: pointer; border-radius: 5px; font-weight: bold;">PRINT GRN</button>
    </div>

    <!-- Header -->
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
                <div class="doc-title" style="font-size: 16pt;">LAPORAN PENERIMAAN BARANG</div>
                <div class="text-right font-bold" style="color: #666;">(GOODS RECEIPT NOTE)</div>
                <table style="margin-top: 15px; float: right;">
                    <tr>
                        <td class="font-bold">No. LPB</td>
                        <td width="15" class="text-center">:</td>
                        <td class="text-right font-bold">{{ $receipt->grn_number }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Tanggal</td>
                        <td class="text-center">:</td>
                        <td class="text-right">{{ date('d F Y', strtotime($receipt->receipt_date)) }}</td>
                    </tr>
                    @if($receipt->delivery_note_number)
                    <tr>
                        <td class="font-bold">No. SJ Supplier</td>
                        <td class="text-center">:</td>
                        <td class="text-right font-bold">{{ $receipt->delivery_note_number }}</td>
                    </tr>
                    @endif
                    @if($receipt->purchaseOrder)
                    <tr>
                        <td class="font-bold">No. PO</td>
                        <td class="text-center">:</td>
                        <td class="text-right font-bold">{{ $receipt->purchaseOrder->po_number }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="details-section">
        <table width="100%">
            <tr>
                <td width="50%">
                    <div class="font-bold" style="margin-bottom: 5px; text-decoration: underline;">DITERIMA DARI (SUPPLIER):</div>
                    <div style="padding-left: 10px; border-left: 3pt solid #008000;">
                        <span class="font-bold" style="font-size: 12pt;">{{ $receipt->supplier->name }}</span><br>
                        {!! nl2br(e($receipt->supplier->address)) !!}<br>
                        Telp: {{ $receipt->supplier->phone ?? '-' }}
                    </div>
                </td>
                <td width="50%" style="vertical-align: bottom; text-align: right;">
                    <div class="font-bold" style="margin-top: 5px;">GUDANG PENERIMA:</div>
                    <div class="font-bold">{{ $receipt->warehouse->name }} ({{ $receipt->warehouse->code }})</div>
                    <div style="font-size: 8pt; color: #555; margin-top: 5px;">
                        Petugas: {{ $receipt->receivedBy->name ?? '-' }}<br>
                        Status: <span class="font-bold uppercase">{{ $receipt->status }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="150">Kode Barang</th>
                <th>Nama Barang / Deskripsi</th>
                <th width="100">Qty Diterima</th>
                <th width="80">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receipt->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center font-mono">{{ $item->product->sku }}</td>
                <td>
                    <div class="font-bold">{{ $item->product->name }}</div>
                </td>
                <td class="text-right font-bold" style="font-size: 11pt;">{{ number_format($item->qty_received, 0, ',', '.') }}</td>
                <td class="text-center">{{ $item->product->unit->symbol ?? 'PCs' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; border: 1pt solid #000; padding: 15px;">
        <div class="font-bold" style="text-decoration: underline; margin-bottom: 8px;">KETERANGAN / CATATAN PENERIMAAN:</div>
        <div style="font-style: italic; color: #333; line-height: 1.5;">
            {{ $receipt->notes ?? 'Seluruh barang telah diperiksa dan diterima dalam kondisi baik sesuai dengan spesifikasi yang dipesan.' }}
        </div>
    </div>

    <table width="100%" class="verification-row">
        <tr>
            <td width="25%" style="text-align: center;">
                Gudang,
                <div class="sig-box"></div>
                <div class="font-bold">( ________________ )</div>
            </td>
            <td width="25%" style="text-align: center;">
                Penerima,
                <div class="sig-box"></div>
                <div class="font-bold">( {{ $receipt->receivedBy->name ?? '________________' }} )</div>
            </td>
            <td width="25%" style="text-align: center;">
                Pengirim,
                <div class="sig-box"></div>
                <div class="font-bold">( ________________ )</div>
            </td>
            <td width="25%" style="text-align: right; vertical-align: top;">
                <div class="qr-box" style="float: right;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('purchasing.receipts.public-validate', $receipt->id)) }}" class="qr-image">
                    <div class="qr-text italic">VERIFIED GRN</div>
                    <div class="qr-text" style="font-size: 5.5pt; margin-top: 3px; color: #008000;">DOKUMEN SAH SISTEM</div>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; font-size: 8pt; color: #888; text-align: center; border-top: 0.5pt solid #eee; padding-top: 10px;">
        Dokumen ini adalah Bukti Penerimaan Barang resmi dari sistem ERP JIDOKA. <br>
        Dicetak pada: {{ date('d/m/Y H:i') }} | ID: {{ $receipt->id }}
    </div>
</body>
</html>

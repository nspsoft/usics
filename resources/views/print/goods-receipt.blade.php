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
            padding: 8px 5px;
            vertical-align: top;
        }
        .items-table tr.spacer-row td {
            border-top: none;
            border-bottom: none;
            height: 250px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .uppercase { text-transform: uppercase; }

        /* Footer Boxes */
        .footer-boxes {
            margin-top: 15px;
            border: 1pt solid #000;
        }
        .box-cell {
            border-right: 1pt solid #000;
            padding: 10px;
            vertical-align: top;
            height: 70px;
            font-size: 8.5pt;
        }

        .verification-row {
            margin-top: 15px;
        }
        .sig-table {
            width: 100%;
            border: 1pt solid #000;
            table-layout: fixed;
        }
        .sig-table td {
            border: 1pt solid #000;
            text-align: center;
            padding: 5px;
            font-size: 8.5pt;
        }
        .sig-space {
            height: 70px;
            vertical-align: bottom;
            font-weight: bold;
        }

        .qr-box {
            text-align: right;
        }
        .qr-image {
            width: 75px;
            height: 75px;
        }
        .qr-text {
            font-size: 6.5pt;
            color: #555;
            font-weight: bold;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
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
                <td width="60%">
                    <div class="font-bold" style="margin-bottom: 5px; text-decoration: underline;">DITERIMA DARI (SUPPLIER):</div>
                    <div style="padding-left: 10px; border-left: 3pt solid #E21E26;">
                        <span class="font-bold" style="font-size: 11pt;">{{ $receipt->supplier->name }}</span><br>
                        {!! nl2br(e($receipt->supplier->address)) !!}<br>
                        Telp: {{ $receipt->supplier->phone ?? '-' }}
                    </div>
                </td>
                <td width="40%" style="vertical-align: top; text-align: right;">
                    <div style="margin-top: 5px;">
                        <span class="font-bold">STATUS:</span> 
                        <span class="uppercase font-bold" style="color: {{ $receipt->status === 'completed' ? '#008000' : '#666' }};">
                            {{ $receipt->status }}
                        </span>
                    </div>
                    <div style="font-size: 9pt; color: #333; margin-top: 5px;">
                        Petugas: {{ $receipt->receivedBy->name ?? '-' }}
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

            <!-- Spacer Row to push footer down -->
            @php $remainingRows = max(0, 10 - count($receipt->items)); @endphp
            <tr class="spacer-row">
                <td style="height: {{ $remainingRows * 25 + 50 }}px;"></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Footer Section -->
    <table class="footer-boxes">
        <tr>
            <td class="box-cell" width="50%">
                <div class="font-bold">PLEASE DELIVERY TO :</div>
                <div style="margin-top: 5px;">
                    <div class="font-bold">{{ $receipt->warehouse->name }}</div>
                    <div>{!! nl2br(e($receipt->warehouse->address ?? 'Kawasan Industri JABABEKA I, Cikarang Utara')) !!}</div>
                </div>
            </td>
            <td class="box-cell" style="border-right: none;">
                <div class="font-bold">NOTES / KETERANGAN:</div>
                <div style="margin-top: 5px; font-style: italic;">
                    {{ $receipt->notes ?? 'Barang diterima sesuai dengan pesanan dan dalam kondisi baik.' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="verification-row">
        <table width="100%">
            <tr>
                <td width="75%">
                    <table class="sig-table">
                        <tr>
                            <td width="33%">Gudang Penerima</td>
                            <td width="33%">Diperiksa Oleh</td>
                            <td width="33%">Dikirim Oleh</td>
                        </tr>
                        <tr>
                            <td class="sig-space">{{ $receipt->receivedBy->name ?? '________________' }}</td>
                            <td class="sig-space">________________</td>
                            <td class="sig-space">________________</td>
                        </tr>
                        <tr class="font-bold">
                            <td>Warehouse Dept</td>
                            <td>Quality Control</td>
                            <td>Driver / Expedisi</td>
                        </tr>
                    </table>
                </td>
                <td width="25%" style="vertical-align: top; padding-left: 15px;">
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('purchasing.receipts.public-validate', $receipt->id)) }}" class="qr-image">
                        <div class="qr-text">VERIFIED SYSTEM</div>
                        <div class="qr-text" style="color: #008000; font-size: 5pt;">DOKUMEN SAH DIGITAL</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 20px; font-size: 7.5pt; color: #888; text-align: center;">
        Dokumen ini adalah Bukti Penerimaan Barang resmi. <br>
        Dicetak pada: {{ date('d/m/Y H:i') }} | Ref: {{ $receipt->grn_number }}
    </div>

</body>
</html>

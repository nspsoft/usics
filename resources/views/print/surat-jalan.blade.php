<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $order->do_number }}</title>
    @php
        // A4 = 210mm x 297mm (Portrait)
        // Continuous Form (Standard Half Letter) = 9.5in x 5.5in / A5 landscape
        $paperSize = $format === 'continuous' ? '9.5in 5.5in' : 'A4 portrait';
    @endphp
    <style>
        @page {
            margin: 0.3cm;
            size: {{ $paperSize }};
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.15;
            color: #000;
            margin: 0;
            padding: 10px 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-section {
            margin-bottom: 10px;
        }
        .company-logo-text {
            font-size: 22pt;
            font-weight: 900;
            font-style: italic;
            color: #E21E26;
            letter-spacing: -1px;
            margin: 0;
        }
        .company-full-name {
            font-size: 9pt;
            font-weight: 800;
            color: #003680;
            margin: -4px 0 3px 0;
        }
        .company-address {
            font-size: 7.5pt;
            line-height: 1.25;
        }
        .doc-title {
            text-align: left;
            font-size: 18pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            padding-bottom: 5px;
            padding-left: 5px;
        }
        .meta-container {
            width: 300px;
            float: right;
            text-align: left;
        }
        .meta-table {
            width: 100%;
            margin-top: 2px;
        }
        .meta-label {
            width: 80px;
            text-align: left;
            padding-left: 5px;
        }
        .meta-separator {
            width: 20px;
            text-align: center;
        }
        .meta-value {
            border-bottom: 0.25pt solid #000;
            width: 100%;
            text-align: left;
            padding-left: 12px;
            font-weight: bold;
        }

        .sold-to-section {
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .sold-to-box {
            border: 1.2px solid #0055A5;
            border-radius: 10px;
            padding: 5px 12px;
            width: 320px;
            min-height: 60px;
        }
        .box-title {
            font-weight: 800;
            text-decoration: underline;
            margin-bottom: 4px;
        }

        .items-table {
            border: 1px solid #0055A5;
            margin-top: 10px;
        }
        .items-table th {
            border: 1px solid #0055A5;
            padding: 4px;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
        }
        .items-table td {
            border-left: 1px solid #0055A5;
            border-right: 1px solid #0055A5;
            padding: 2px 6px;
            vertical-align: top;
        }
        
        .clear { clear: both; }

        .footer-section {
            margin-top: 15px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature-name {
            margin-top: 60px;
            font-weight: 500;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #003680; color: white; border: none; border-radius: 5px; font-weight: bold;">PRINT SELECTION</button>
    </div>

    <!-- Header -->
    <table class="header-section">
        <tr>
            <td width="55%">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                    <img src="/images/jri-official-logo.png" alt="logo" style="height: 50px;">
                    <div>
                        <div class="company-logo-text">jidoka</div>
                        <div class="company-full-name">PT. JIDOKA RESULT INDONESIA</div>
                    </div>
                </div>
                <div class="company-address">
                    Kawasan Industri JABABEKA I<br>
                    Jl. Jababeka II Blok C No. 19 L, Pasir gombong, Cikarang Utara<br>
                    Bekasi 17530 Jawa Barat. Telp : 021 8938 3915<br>
                    e_mail : jidoka.pt@yahoo.com
                </div>
            </td>
            <td width="45%" style="vertical-align: top;">
                <div class="meta-container">
                    <div class="doc-title" style="font-size: 19pt; font-weight: 900; color: #1a1a1a; padding-left: 0; line-height: 1;">DELIVERY ORDER</div>
                    <table class="meta-table">
                        <tr>
                            <td width="90" style="padding: 1px 0; white-space: nowrap; text-align: left; font-size: 9pt;">No</td>
                            <td width="15" style="text-align: center; font-size: 9pt;">:</td>
                            <td class="meta-value" style="font-size: 9pt;">{{ $order->do_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap; text-align: left; font-size: 9pt;">Date</td>
                            <td style="text-align: center; font-size: 9pt;">:</td>
                            <td class="meta-value" style="font-size: 9pt;">{{ $order->delivery_date->format('F d, Y') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap; text-align: left; font-size: 9pt;">PO No</td>
                            <td style="text-align: center; font-size: 9pt;">:</td>
                            <td class="meta-value" style="font-size: 9pt;">{{ $order->salesOrder->customer_po_number ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; white-space: nowrap; text-align: left; font-size: 9pt;">Vehicle No</td>
                            <td style="text-align: center; font-size: 9pt;">:</td>
                            <td class="meta-value" style="font-size: 9pt;">{{ $order->vehicle_number ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <hr style="border: 0; border-top: 2px double #000; margin: 5px 0;">

    <table class="sold-to-section">
        <tr>
            <td width="55%">
                <div style="font-weight: 800; margin-bottom: 5px;">Sold To :</div>
                <div class="sold-to-box">
                    <strong>{{ $order->customer->name }}</strong><br>
                    {{ $order->customer->full_address }}<br>
                    No Telp. : {{ $order->customer->phone ?? '-' }}
                </div>
            </td>
            <td width="45%" style="padding-left: 40px;">
                <div style="font-weight: 800; margin-bottom: 5px;">Delivered To :</div>
                <strong>{{ $order->shipping_name ?? $order->customer->name }}</strong><br>
                {{ $order->shipping_address ?? $order->customer->full_address }}
            </td>
        </tr>
    </table>

    <!-- Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th>Description</th>
                <th width="80">Quantity</th>
                <th width="80">UOM</th>
                <th width="200">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($order->items as $index => $item)
            @if($item->qty_delivered > 0)
            <tr>
                <td style="text-align: center; border-bottom: none;">{{ $no++ }}</td>
                <td style="border-bottom: none;">
                    <strong>{{ $item->product->name }}</strong><br>
                    <span style="font-size: 8pt; color: #444;">{{ $item->product->description }}</span>
                </td>
                <td style="text-align: center; border-bottom: none;">{{ number_format($item->qty_delivered, 0, ',', '.') }}</td>
                <td style="text-align: center; border-bottom: none;">{{ $item->unit->code ?? 'PCs' }}</td>
                <td style="border-bottom: none; font-size: 8pt; color: #444;">{{ $item->notes }}</td>
            </tr>
            @endif
            @endforeach
            <!-- Dynamic Spacer Row -->
            @php
                $itemCount = max(0, $no - 1);
                $targetRows = 7;
                $rowHeight = 10;
                $remainingRows = max(0, $targetRows - $itemCount);
            @endphp
            <tr class="item-row">
                <td style="height: {{ $remainingRows * $rowHeight }}px; border-left: 1px solid #0055A5; border-right: 1px solid #0055A5;"></td>
                <td style="border-left: 1px solid #0055A5; border-right: 1px solid #0055A5;"></td>
                <td style="border-left: 1px solid #0055A5; border-right: 1px solid #0055A5;"></td>
                <td style="border-left: 1px solid #0055A5; border-right: 1px solid #0055A5;"></td>
                <td style="border-left: 1px solid #0055A5; border-right: 1px solid #0055A5;"></td>
            </tr>
            <tr>
                <td colspan="5" style="border-top: 1px solid #0055A5; padding: 0;"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer-section">
        <table style="width: 100%; border: none;">
            <tr>
                <td width="30%" style="vertical-align: top; border: none;">
                    <div style="font-size: 8pt; line-height: 1.4; color: #444;">
                        Goods received in good Order and Condition<br>
                        Goods sold are not returnable
                    </div>
                    <div style="margin-top: 45px; border-top: 1px solid #000; width: 180px; text-align: center; font-size: 8pt;">
                        Recipient Signature & Stamp
                    </div>
                </td>
                <td width="40%" style="vertical-align: top; text-align: center; border: none;">
                    <div style="font-weight: bold; font-size: 9pt;">PT. Jidoka Result Indonesia</div>
                    <div style="margin-top: 60px; border-top: 1px solid #000; width: 180px; margin-left: auto; margin-right: auto; text-align: center; font-size: 8pt;">
                        Authorized Signature
                    </div>
                </td>
                <td width="30%" style="vertical-align: top; text-align: right; border: none;">
                    <div style="display: inline-block; text-align: center;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('sales.deliveries.public-validate', $order->public_uuid ?: $order->id)) }}" style="width: 65px; height: 65px; padding: 2px; border: 1px solid #eee;">
                        <div style="font-size: 7.5pt; font-weight: bold; margin-top: 4px; color: #003680;">VERIFIED DO</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>

</body>
</html>

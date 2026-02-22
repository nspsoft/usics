<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $order->do_number }}</title>
    @php
        $activeItemsCount = $order->items->where('qty_delivered', '>', 0)->count();
        $paperSize = $activeItemsCount > 3 ? 'A4 portrait' : 'A5 landscape';
    @endphp
    <style>
        @page {
            margin: 0.3cm;
            size: {{ $paperSize }};
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.25;
            color: #000;
            margin: 0;
            padding: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-section {
            margin-bottom: 10px;
        }
        .company-logo-text {
            font-size: 26pt;
            font-weight: 900;
            font-style: italic;
            color: #E21E26;
            letter-spacing: -1px;
            margin: 0;
        }
        .company-full-name {
            font-size: 10pt;
            font-weight: 800;
            color: #003680;
            margin: -5px 0 5px 0;
        }
        .company-address {
            font-size: 8pt;
            line-height: 1.3;
        }
        .doc-title {
            text-align: left;
            font-size: 18pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            padding-bottom: 5px;
            padding-left: 5px;
        }
        .meta-table {
            width: 320px;
            float: left;
            margin-top: 0;
        }
        .meta-table td {
            padding: 3px 0;
            font-size: 10pt;
            vertical-align: bottom;
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
            border-bottom: 1px dotted #000;
            min-width: 160px;
            text-align: left;
            padding-left: 10px;
            font-weight: bold;
        }

        .sold-to-section {
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .sold-to-box {
            border: 1.2px solid #0055A5;
            border-radius: 12px;
            padding: 10px 15px;
            width: 320px;
            min-height: 70px;
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
            padding: 6px;
            font-weight: bold;
            text-align: center;
        }
        .items-table td {
            border-left: 1px solid #0055A5;
            border-right: 1px solid #0055A5;
            padding: 4px 6px;
            vertical-align: top;
        }
        .items-table tr:last-child td {
            padding-bottom: 40px; /* Space for empty area */
        }
        
        .clear { clear: both; }

        .footer-section {
            margin-top: 15px;
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
                    Jl. Jababeka II Blok C No. 19 L<br>
                    Pasir gombong, Cikarang Utara, Bekasi 17530 Jawa Barat<br>
                    Telp : 021 8938 3915, Fax. : 021 - 8938 3915<br>
                    e_mail : jidoka.pt@yahoo.com
                </div>
            </td>
            <td width="45%" style="vertical-align: top;">
                <div class="doc-title" style="font-size: 20pt; font-weight: 900; color: #1a1a1a;">DELIVERY ORDER</div>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">No</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value">{{ $order->do_number }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Date</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value">{{ $order->delivery_date->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">PO No</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value">{{ $order->salesOrder->customer_po_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Verhicle No</td>
                        <td class="meta-separator">:</td>
                        <td class="meta-value">{{ $order->vehicle_number ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr style="border: 0; border-top: 2px double #000; margin: 10px 0;">

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
            <!-- Placeholder for border bottom -->
            <tr>
                <td colspan="5" style="border-top: none; border-bottom: 1px solid #0055A5; height: 1px; padding: 0;"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer-section">
        <table style="width: 100%; border: none;">
            <tr>
                <td width="33%" style="vertical-align: top; border: none;">
                    <div style="margin-bottom: 10px;">
                        Goods received in good Order and Condition<br>
                        Goods sold are not returnable
                    </div>
                    <div class="signature-name" style="margin-top: 45px;">Company stamp and Signature</div>
                </td>
                <td width="34%" style="vertical-align: top; text-align: center; border: none;">
                    <div style="font-weight: 600;">PT. Jidoka Result Indonesia</div>
                    <div class="signature-name" style="margin-top: 60px;">Authorized Signature</div>
                </td>
                <td width="33%" style="vertical-align: top; text-align: right; border: none;">
                    <div style="display: inline-block; text-align: center;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('sales.deliveries.public-validate', $order->public_uuid ?: $order->id)) }}" style="width: 70px; height: 70px; border: 1px solid #f1f5f9; padding: 2px;">
                        <div style="font-size: 8pt; font-weight: bold; margin-top: 4px; color: #003680; line-height: 1;">SCAN FOR VALIDATION</div>
                        <div style="font-size: 7pt; color: #666; margin-top: 3px;">Official JIDOKA Form</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>

</body>
</html>

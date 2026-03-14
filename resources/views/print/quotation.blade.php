<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->number }}</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
            font-size: 24pt;
            font-weight: 900;
            font-style: italic;
            color: #000080;
            margin-top: 10px;
        }
        .meta-table {
            float: right;
            margin-top: 10px;
        }
        .meta-table td {
            padding: 2px 0;
            font-size: 10pt;
        }
        .meta-label {
            font-weight: bold;
            text-align: left;
            padding-right: 10px;
        }
        .meta-separator {
            width: 15px;
            text-align: center;
        }
        .meta-value {
            min-width: 150px;
        }

        .customer-section {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .customer-table td {
            vertical-align: top;
            padding: 2px 0;
        }
        .customer-label {
            width: 40px;
            font-weight: bold;
        }

        .items-table {
            margin-top: 10px;
            border: 1px solid #000;
        }
        .items-table th {
            border: 1px solid #000;
            background-color: #fff;
            padding: 8px 4px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 6px 5px;
            vertical-align: top;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .totals-section {
            margin-top: -1px; /* Align with items table border */
        }
        .totals-section td {
            border: 1px solid #000;
            padding: 5px;
        }
        .totals-label {
            width: 75%;
            text-align: right;
            font-weight: bold;
        }
        .totals-value {
            width: 25%;
            text-align: right;
            font-weight: bold;
        }

        .footer-section {
            margin-top: 20px;
        }
        .footer-boxes {
            width: 100%;
        }
        .terms-box {
            width: 55%;
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
        }
        .signature-box {
            width: 45%;
            border: 1px solid #000;
            border-left: none;
            padding: 0;
            vertical-align: top;
        }
        .signature-table {
            height: 100%;
        }
        .signature-table td {
            border: 1px solid #000;
            text-align: center;
            height: 20px;
            font-size: 9px;
            padding: 3px;
        }
        .signature-space {
            height: 80px !important;
        }
        .signature-name {
            font-weight: bold;
            font-style: italic;
            border-top: 1px solid #000;
        }
        
        .clear { clear: both; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #0055A5; color: white; border: none; border-radius: 5px;">Print Document</button>
    </div>

    <!-- Header Section -->
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
                    <div class="doc-title" style="text-align: left; margin-top: 10px; margin-bottom: 5px;">QUOTATION</div>
                    <table class="meta-table" style="float: left; margin-top: 0;">
                        <tr>
                            <td class="meta-label">NO</td>
                            <td class="meta-separator">:</td>
                            <td class="meta-value font-bold">{{ $quotation->number }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date</td>
                            <td class="meta-separator">:</td>
                            <td class="meta-value">{{ $quotation->quotation_date->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Valid Until</td>
                            <td class="meta-separator">:</td>
                            <td class="meta-value font-bold" style="color: #E21E26;">{{ $quotation->valid_until->format('d F Y') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Customer Section -->
    <div class="customer-section">
        <table class="customer-table">
            <tr>
                <td class="customer-label">TO</td>
                <td style="width: 15px;">:</td>
                <td>
                    <span class="font-bold" style="font-size: 11px;">{{ $quotation->customer->name }}</span><br>
                    {{ $quotation->customer->full_address }}<br>
                    Phone : {{ $quotation->customer->phone ?? '-' }}<br>
                    Email : {{ $quotation->customer->email ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="30">NO</th>
                <th>DESCRIPTION</th>
                <th width="50">QTY</th>
                <th width="50">UOM</th>
                <th width="120">PRICE PER UNIT</th>
                <th width="120">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($quotation->items as $index => $item)
            @php $totalQty += $item->qty; @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <span class="font-bold" style="font-size: 11pt;">{{ $item->product->name }}</span><br>
                    <span style="font-size: 8.5pt; color: #333;">{{ $item->product->description }}</span>
                </td>
                <td class="text-center" style="font-size: 11pt;">{{ number_format($item->qty, 0, ',', '.') }}</td>
                <td class="text-center">{{ $item->product->unit->code ?? 'PCS' }}</td>
                <td class="text-right">IDR {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">IDR {{ number_format($item->total_price, 0, ',', '.') }}</td>
            </tr>
            @endforeach

            <!-- Total Quantity & Subtotal -->
            <tr class="font-bold">
                <td colspan="2" class="text-right" style="padding: 8px;">Total</td>
                <td class="text-center">{{ number_format($totalQty, 0, ',', '.') }}</td>
                <td colspan="2"></td>
                <td class="text-right">IDR {{ number_format($quotation->subtotal, 0, ',', '.') }}</td>
            </tr>

            <!-- Discount -->
            <tr class="font-bold">
                <td colspan="5" class="text-right" style="padding: 8px;">Discount</td>
                <td class="text-right">IDR {{ number_format($quotation->discount, 0, ',', '.') }}</td>
            </tr>

            <!-- Grand Total -->
            <tr class="font-bold" style="background-color: #f9f9f9;">
                <td colspan="5" class="text-right" style="padding: 10px 8px;">GRAND TOTAL</td>
                <td class="text-right" style="font-size: 11pt;">IDR {{ number_format($quotation->subtotal - $quotation->discount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>


    <!-- Footer / Bottom Section -->
    <div class="footer-section">
        <table width="100%">
            <tr>
                <td width="70%" style="vertical-align: top;">
                    <div style="font-size: 10pt; line-height: 1.6;">
                        <span class="font-bold">Note :</span><br>
                        <table cellpadding="0" cellspacing="0" style="width: auto; margin-top: 2px;">
                            <tr>
                                <td width="110">Term of Payment</td>
                                <td width="15">:</td>
                                <td>1 Month after Invoice received</td>
                            </tr>
                            <tr>
                                <td>Lead time</td>
                                <td>:</td>
                                <td>14 days after PO received</td>
                            </tr>
                            <tr>
                                <td>Expired</td>
                                <td>:</td>
                                <td>This quotation will up date 2 weeks after send to Customer</td>
                            </tr>
                        </table>
                        
                        <div style="margin-top: 15px;">
                            Looking forwards to hearing from you soon<br>
                            With kind regards
                        </div>

                        <div style="margin-top: 40px;">
                            <span class="font-bold" style="text-decoration: underline; font-size: 11pt;">Nanang Mulyana</span><br>
                            Marketing Departement
                        </div>
                    </div>
                </td>
                <td width="30%" style="vertical-align: bottom; text-align: right;">
                    <div style="text-align: center; float: right; margin-bottom: 10px;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('sales.quotations.public-validate', $quotation->public_uuid ?: $quotation->id)) }}" style="width: 100px; height: 100px;">
                        <div style="font-size: 9pt; font-weight: bold; margin-top: 5px; color: #0055A5;">SCAN FOR VALIDATION</div>
                        <div style="font-size: 8pt; color: #555;">Official JIDOKA Document</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

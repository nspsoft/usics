<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return GRN - {{ $return->number }}</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .content-wrapper { padding: 20px; }
        
        /* Header Styling */
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
            font-size: 8pt;
            line-height: 1.2;
            clear: left;
            padding-top: 5px;
        }
        .doc-title {
            text-align: right;
            font-size: 20pt;
            font-weight: 900;
            font-style: italic;
            color: #000080;
            margin-top: 5px;
        }
        
        .meta-table {
            float: right;
            margin-top: 5px;
        }
        .meta-table td { padding: 1px 0; font-size: 9pt; }
        .meta-label { font-weight: bold; width: 80px; }
        .meta-separator { width: 15px; text-align: center; }
        .meta-value { min-width: 140px; }

        /* Document Sections */
        .box-section { margin-top: 20px; margin-bottom: 20px; }
        .info-box {
            border: 1pt solid #003680;
            padding: 8px 12px;
            min-height: 70px;
        }
        .box-title {
            font-weight: bold;
            text-decoration: underline;
            color: #003680;
            margin-bottom: 5px;
            font-size: 9pt;
        }

        /* Table Styling (Loss Format) */
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
            border-left: 1pt solid #000;
            border-right: 1pt solid #000;
            border-top: none;
            border-bottom: none;
            padding: 8px 5px;
            vertical-align: top;
        }
        .items-table tr.last-row td {
            border-bottom: 1pt solid #000;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* Footer Positioning */
        .footer-wrapper {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            padding: 0 20px 20px 20px;
        }
        .signature-box { text-align: center; width: 220px; }
        .qr-box { text-align: center; width: 100px; float: right; }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin: 10px 20px;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #E21E26; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">PRINT GRN</button>
    </div>

    <div class="content-wrapper">
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
                    <div class="doc-title">SALES RETURN GRN</div>
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">GRN NO</td>
                            <td class="meta-separator">:</td>
                            <td class="meta-value font-bold">{{ $return->number }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date</td>
                            <td class="meta-separator">:</td>
                            <td class="meta-value">{{ date('d F Y', strtotime($return->return_date)) }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Ref No.</td>
                            <td class="meta-separator">:</td>
                            <td class="meta-value font-bold">
                                {{ $return->salesInvoice->invoice_number ?? $return->salesOrder->number ?? '-' }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Info Section -->
        <div class="box-section">
            <table width="100%">
                <tr>
                    <td width="55%">
                        <div class="info-box">
                            <div class="box-title">RECEIVED FROM:</div>
                            <div class="font-bold" style="font-size: 11pt;">{{ $return->customer->name }}</div>
                            <div style="font-size: 8pt; color: #333; margin-top: 2px;">
                                {{ $return->customer->full_address }}
                            </div>
                        </div>
                    </td>
                    <td width="45%" style="padding-left: 20px; vertical-align: top;">
                        <div class="info-box" style="border-color: #666;">
                            <div class="box-title" style="color: #666;">WAREHOUSE:</div>
                            <div class="font-bold">{{ $return->warehouse->name }}</div>
                            <div style="font-size: 8pt; color: #333; margin-top: 2px;">
                                Status: <span class="uppercase font-bold" style="color: #003680;">{{ $return->status }}</span>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Product Description</th>
                    <th width="80">Qty</th>
                    <th width="80">UOM</th>
                    <th>Reason / Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="font-bold">{{ $item->product->name }}</div>
                        <div style="font-size: 8pt; color: #555;">{{ $item->product->sku }}</div>
                    </td>
                    <td class="text-center font-bold">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="text-center uppercase">{{ $item->unit->code ?? 'PCS' }}</td>
                    <td style="font-size: 8pt;">{{ $return->reason ?? '-' }}</td>
                </tr>
                @endforeach

                <!-- Spacing rows for loss format -->
                @for ($i = count($return->items); $i < 8; $i++)
                <tr>
                    <td style="height: 25px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @endfor
                <tr class="last-row">
                    <td style="height: 0px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Fixed Footer -->
    <div class="footer-wrapper">
        <table width="100%">
            <tr>
                <td width="33%" style="vertical-align: top;">
                    <div class="signature-box">
                        <div>Received By,</div>
                        <div style="height: 60px;"></div>
                        <div style="border-top: 1pt solid #000; margin: 0 20px;">Warehouse Staff</div>
                    </div>
                </td>
                <td width="33%" style="vertical-align: top;">
                    <div class="signature-box" style="margin: 0 auto;">
                        <div>Cikarang, {{ date('d F Y', strtotime($return->return_date)) }}</div>
                        <div style="height: 60px;"></div>
                        <div class="font-bold" style="text-decoration: underline;">JAHRUDIN</div>
                        <div style="font-size: 8pt;">Authorized Signature</div>
                    </div>
                </td>
                <td width="33%" style="vertical-align: top;">
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('sales.returns.public-validate', $return->public_uuid ?: $return->id)) }}" style="width: 80px; height: 80px;">
                        <div style="font-size: 8pt; font-weight: bold; margin-top: 5px; color: #003680; line-height: 1;">SCAN FOR VALIDATION</div>
                        <div style="font-size: 7pt; color: #666; margin-top: 3px;">Official JIDOKA Form</div>
                    </div>
                </td>
            </tr>
        </table>
        <div style="margin-top: 10px; font-size: 7pt; color: #555; border-top: 0.5pt solid #eee; padding-top: 5px;">
            <i>* This Good Receipt Note (GRN) is a valid document for Sales Return generated by JICOS ERP System.</i>
        </div>
    </div>
</body>
</html>

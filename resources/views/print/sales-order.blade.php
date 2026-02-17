<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Order - {{ $salesOrder->so_number }}</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
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
            font-size: 9pt;
            line-height: 1.3;
            clear: left;
            padding-top: 5px;
        }
        
        /* Title Styling */
        .doc-title {
            text-align: right;
            font-size: 20pt;
            font-weight: 900;
            font-style: italic;
            color: #000080;
            margin-top: 20px;
            font-family: Arial, sans-serif;
        }
        
        .details-section {
            margin-top: 25px;
        }
        .details-table td {
            vertical-align: top;
            padding: 2px 0;
            font-size: 9pt;
        }
        
        /* Items Table */
        .items-table {
            margin-top: 15px;
            border: 1pt solid #000;
            border-bottom: none;
        }
        .items-table th {
            border: 1pt solid #000;
            padding: 5px;
            font-size: 9pt;
            font-weight: normal;
            color: #555;
            text-align: center;
        }
        .items-table td {
            border-right: 1pt solid #000;
            border-left: 1pt solid #000;
            padding: 5px;
            vertical-align: top;
            font-size: 9pt;
        }
        .items-table tr.item-row td {
            border-top: none;
            border-bottom: none;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals-row td {
            border: 1pt solid #000;
            padding: 2px 5px;
            font-size: 9pt;
        }
        
        /* Footer */
        .footer-boxes {
            margin-top: 10px;
            border: 1pt solid #000;
        }
        .box-cell {
            border-right: 1pt solid #000;
            padding: 5px;
            vertical-align: top;
            height: 60px;
            font-size: 8pt;
        }

        /* Signatures */
        .signatures-section {
            margin-top: 15px;
            width: 100%;
        }
        .sig-table {
            border: 1pt solid #000;
            width: 100%;
            table-layout: fixed;
        }
        .sig-table td {
            border: 1pt solid #000;
            text-align: center;
            padding: 2px;
            font-size: 8pt;
            overflow: hidden;
            white-space: nowrap;
        }
        .sig-space { height: 80px; vertical-align: bottom; font-weight: bold; font-style: italic; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>


    <!-- Header -->
    <table class="header-table">
        <tr>
            <td width="55%">
                <div style="margin-bottom: 10px;">
                    <img src="/images/jri-official-logo.png" alt="logo" class="company-logo-img">
                    <div>
                        <div class="company-header-text">jidoka</div>
                        <div class="company-header-sub">PT. JIDOKA RESULT INDONESIA</div>
                    </div>
                    <div style="clear: both;"></div>
                </div>
                <div class="company-address">
                    Kawasan Industri JABABEKA I<br>
                    Jl. Jababeka II Blok C No. 19 L<br>
                    Pasirgombong, Cikarang Utara, Bekasi 17530 Jawa Barat<br>
                    Telp : +62 21 89383915, Fax. : +62 21 89383915<br>
                    e_mail : sales@jidoka.co.id
                </div>
            </td>
            <td width="45%" style="vertical-align: top; padding-top: 30px;">
                <div class="no-print" style="text-align: right; margin-bottom: 5px;">
                    <button onclick="window.print()" style="padding: 8px 15px; cursor: pointer; background: #00008B; color: white; border: none; font-weight: bold; font-size: 10pt;">PRINT DOCUMENT</button>
                </div>
                <div style="float: right;">
                    <div class="doc-title" style="text-align: left; margin-top: 10px; margin-bottom: 5px;">SALES ORDER</div>
                    <table style="margin-top: 0; float: left; width: auto;">
                        <tr>
                            <td class="font-bold" width="100">SO NO</td>
                            <td width="15" class="text-center">:</td>
                            <td class="font-bold">{{ $salesOrder->so_number }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Date</td>
                            <td class="text-center">:</td>
                            <td class="font-bold">{{ \Carbon\Carbon::parse($salesOrder->order_date)->translatedFormat('F d, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">PO Number</td>
                            <td class="text-center">:</td>
                            <td class="font-bold">{{ $salesOrder->customer_po_number ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Details -->
    <div class="details-section">
        <table width="100%" class="details-table">
            <tr>
                <td width="60%">
                    <table>
                        <tr>
                            <td width="30" class="font-bold">TO</td>
                            <td width="10" class="text-center">:</td>
                            <td class="font-bold uppercase">{{ $salesOrder->customer->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td>
                                <div style="margin-left: 0;">
                                    {!! nl2br(e($salesOrder->customer->full_address ?? '-')) !!}<br>
                                    <table style="width: auto; margin-top: 2px;">
                                        <tr><td width="60">Phone</td><td>: {{ $salesOrder->customer->phone ?? '-' }}</td></tr>
                                        <tr><td>Email</td><td>: {{ $salesOrder->customer->email ?? '-' }}</td></tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="40%" style="vertical-align: top; text-align: right;">
                    <div style="display: inline-block; text-align: center; width: 100px;">
                       <!-- QR Code Placeholder if needed, logic for public validation might differ -->
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Items & Totals merged for alignment -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>DESCRIPTION</th>
                <th width="40">QTY</th>
                <th width="40">UOM</th>
                <th width="100">PRICE PER UNIT</th>
                <th width="120">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($salesOrder->items as $index => $item)
            @php $totalQty += $item->qty; @endphp
            <tr class="item-row">
                <td class="text-center" style="padding-top: 10px;">{{ $index + 1 }}</td>
                <td style="padding-top: 10px;">
                    <div class="font-bold">{{ $item->product_alias_name ?? $item->product->name ?? '-' }}</div>
                    @if($item->product_alias_sku)
                        <div style="font-size: 8pt; font-family: monospace;">{{ $item->product_alias_sku }}</div>
                    @endif
                    <div style="font-size: 8pt; color: #333;">{{ $item->product->description ?? ' ' }}</div>
                </td>
                <td class="text-center" style="padding-top: 10px;">{{ number_format($item->qty, 0, ',', '.') }}</td>
                <td class="text-center" style="padding-top: 10px;">{{ $item->unit->name ?? 'PCs' }}</td>
                <td class="text-right" style="padding-top: 10px;">
                    <div style="float: left; padding-left: 5px;">IDR</div>
                    <div style="float: right; padding-right: 5px;">{{ number_format($item->unit_price, 0, ',', '.') }}</div>
                </td>
                <td class="text-right" style="padding-top: 10px;">
                     <div style="float: left; padding-left: 5px;">IDR</div>
                     <div style="float: right; padding-right: 5px;">{{ number_format($item->qty * $item->unit_price, 0, ',', '.') }}</div>
                </td>
            </tr>
            @endforeach
            
            <!-- Spacer Row -->
            <tr class="item-row">
                <td style="height: 300px;"></td> 
                <td></td><td></td><td></td><td></td><td></td>
            </tr>

            <!-- Totals Rows -->
            <tr class="totals-row">
                <td colspan="2" class="text-center" style="border-right: 1pt solid #000;">Total</td>
                <td class="text-center font-bold">{{ number_format($totalQty, 0, ',', '.') }}</td>
                <td style="border-right: 1pt solid #000;"></td>
                <td style="border-right: 1pt solid #000;"></td>
                <td class="text-right font-bold">
                     <div style="float: left; padding-left: 5px;">IDR</div>
                     <div style="float: right; padding-right: 5px;">{{ number_format($salesOrder->subtotal, 0, ',', '.') }}</div>
                </td>
            </tr>
            <tr class="totals-row">
                <td colspan="5" class="text-right" style="border-right: 1pt solid #000;">Tax PPn {{ $salesOrder->tax_percent }}%</td>
                <td class="text-right font-bold">
                    <div style="float: left; padding-left: 5px;">IDR</div>
                    <div style="float: right; padding-right: 5px;">{{ number_format($salesOrder->tax_amount, 0, ',', '.') }}</div>
                </td>
            </tr>
            <tr class="totals-row">
                <td colspan="5" class="text-right uppercase font-bold" style="border-right: 1pt solid #000;">GRAND TOTAL</td>
                <td class="text-right font-bold">
                    <div style="float: left; padding-left: 5px;">IDR</div>
                    <div style="float: right; padding-right: 5px;">{{ number_format($salesOrder->total, 0, ',', '.') }}</div>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Footer Boxes -->
    <table class="footer-boxes">
        <tr>
            <td class="box-cell" width="55%">
                <div class="font-bold">Ship To :</div>
                <div style="margin-top: 5px;">
                    <div>{{ $salesOrder->shipping_name || $salesOrder->customer->name }}</div>
                    <div>{{ $salesOrder->shipping_address || $salesOrder->customer->full_address }}</div>
                </div>
            </td>
            <td class="box-cell" style="border-right: none;">
                <div class="font-bold italic">Notes :</div>
                <div class="italic" style="margin-top: 5px;">
                    {{ $salesOrder->notes }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Signatures -->
    <div class="signatures-section">
        <table width="100%">
            <tr>
                <td width="20%" style="vertical-align: top;">
                    <table class="sig-table">
                        <tr><td class="sig-header">Customer</td></tr>
                        <tr><td class="italic" style="border-bottom: none; text-align: left; padding-left: 5px;">Date :</td></tr>
                        <tr><td class="sig-space"></td></tr>
                    </table>
                </td>
                <td width="20%"></td>
                <td width="60%" style="vertical-align: top;">
                    <table class="sig-table">
                        <tr>
                            <td width="33%">Approved</td>
                            <td width="33%">Checked</td>
                            <td width="33%">Prepared</td>
                        </tr>
                        <tr>
                            <td class="italic">Date : {{ $salesOrder->confirmed_at ? \Carbon\Carbon::parse($salesOrder->confirmed_at)->format('d-m-Y') : '-' }}</td>
                            <td class="italic">Date : {{ \Carbon\Carbon::parse($salesOrder->order_date)->format('d-m-Y') }}</td>
                            <td class="italic">Date : {{ \Carbon\Carbon::parse($salesOrder->order_date)->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <td style="height: 60px;"></td>
                            <td style="height: 60px;"></td>
                            <td style="height: 60px;"></td>
                        </tr>
                        <tr class="font-bold italic">
                            <td style="padding: 5px;">
                                @if($salesOrder->confirmedBy) {{ $salesOrder->confirmedBy->name }} @else &nbsp; @endif
                            </td>
                            <td style="padding: 5px;">-</td>
                            <td style="padding: 5px;">
                                {{ $salesOrder->createdBy->name ?? 'Admin' }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

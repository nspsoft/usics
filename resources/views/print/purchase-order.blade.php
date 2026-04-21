    <style>
        @page {
            margin: 0.5cm 0.5cm 0.2cm 0.5cm;
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
            table-layout: fixed; /* Fix alignments */
        }
        .header-table td {
            vertical-align: top;
        }
        /* Header Styling */
        .company-logo-img {
            height: 55px; /* Slightly larger as per image */
            float: left;
            margin-right: 15px;
        }
        .company-header-text {
            color: #E21E26; /* Red for jidoka */
            font-weight: 900;
            font-style: italic;
            font-size: 24pt;
            letter-spacing: -1px;
            margin: 0;
            line-height: 1;
        }
        .company-header-sub {
            color: #003680; /* Blue for PT */
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
        
        /* Title Styling - Reduced Size */
        .doc-title {
            text-align: right;
            font-size: 20pt; /* Reduced from 24pt */
            font-weight: 900;
            font-style: italic;
            color: #000080; /* Darker Blue */
            margin-top: 20px;
            font-family: Arial, sans-serif; /* Ensure standard sans */
        }
        
        .details-section {
            margin-top: 25px;
        }
        .details-table td {
            vertical-align: top;
            padding: 2px 0;
            font-size: 9pt;
        }
        .label-cell {
            width: 100px;
        }
        
        /* Items Table */
        .items-table {
            margin-top: 15px;
            border: 1pt solid #000;
            border-bottom: none; /* Bottom border handled by last row or totals */
        }
        .items-table th {
            border: 1pt solid #000;
            padding: 5px;
            font-size: 9pt;
            font-weight: normal;
            color: #555;
            text-align: center;
        }
        /* Vertical borders for all cells */
        .items-table td {
            border-right: 1pt solid #000;
            border-left: 1pt solid #000;
            padding: 5px;
            vertical-align: top;
            font-size: 9pt;
        }
        /* No internal horizontal lines */
        .items-table tr.item-row td {
            border-top: none;
            border-bottom: none;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Totals Rows inside main table or matching widths */
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
            table-layout: fixed; /* Fix cutoff */
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
                    e_mail : purchasing@jidoka.co.id
                </div>
            </td>
            <td width="45%" style="vertical-align: top; padding-top: 30px;">
                <div class="no-print" style="text-align: right; margin-bottom: 5px;">
                    <button onclick="window.print()" style="padding: 8px 15px; cursor: pointer; background: #00008B; color: white; border: none; font-weight: bold; font-size: 10pt;">PRINT DOCUMENT</button>
                </div>
                <div style="float: right;">
                    <div class="doc-title" style="text-align: left; margin-top: 10px; margin-bottom: 5px;">PURCHASE ORDER</div>
                    <table style="margin-top: 0; float: left; width: auto;">
                        <tr>
                            <td class="font-bold" width="100">PO NO</td>
                            <td width="15" class="text-center">:</td>
                            <td class="font-bold">{{ $purchaseOrder->po_number }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Date</td>
                            <td class="text-center">:</td>
                            <td class="font-bold">{{ \Carbon\Carbon::parse($purchaseOrder->order_date)->translatedFormat('F d, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Delivery Date</td>
                            <td class="text-center">:</td>
                            <td class="font-bold italic">{{ \Carbon\Carbon::parse($purchaseOrder->expected_date)->translatedFormat('F d, Y') }}</td>
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
                            <td class="font-bold uppercase">{{ $purchaseOrder->supplier->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td>
                                <div style="margin-left: 0;">
                                    {!! nl2br(e($purchaseOrder->supplier->address ?? '-')) !!}<br>
                                    <table style="width: auto; margin-top: 2px;">
                                        <tr><td width="60">Phone</td><td>: {{ $purchaseOrder->supplier->phone ?? '-' }}</td></tr>
                                        <tr><td>Fax.</td><td>: {{ $purchaseOrder->supplier->fax ?? '-' }}</td></tr>
                                        <tr><td>HP</td><td>: {{ $purchaseOrder->supplier->mobile ?? '-' }}</td></tr>
                                        <tr><td>e_mail</td><td>: {{ $purchaseOrder->supplier->email ?? '-' }}</td></tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-bold" style="padding-top: 10px;">Attn</td>
                            <td class="text-center" style="padding-top: 10px;">:</td>
                            <td class="font-bold italic" style="padding-top: 10px;">{{ $purchaseOrder->supplier->contact_person ?? 'Sales Dept' }},</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="italic" style="font-size: 8pt;">Marketing Executive</td>
                        </tr>
                    </table>
                </td>
                <td width="40%" style="vertical-align: top; text-align: right;">
                    <div style="display: inline-block; text-align: center; width: 100px;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('purchasing.orders.public-validate', $purchaseOrder->id)) }}" style="width: 85px; height: 85px; display: block; margin: 0 auto 5px;">
                        <div style="font-size: 7pt; font-weight: bold; color: #003680; margin-bottom: 2px;">VERIFIED PO</div>
                        <div style="font-size: 6pt; font-weight: bold; color: #008000; text-transform: uppercase;">DOKUMEN SAH SISTEM</div>
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
                <th width="100">REMARKs</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($purchaseOrder->items as $index => $item)
            @php $totalQty += $item->qty; @endphp
            <tr class="item-row">
                <td class="text-center" style="padding-top: 10px;">{{ $index + 1 }}</td>
                <td style="padding-top: 10px;">
                    <div class="font-bold">{{ $item->product_alias_name ?? $item->product->name ?? '-' }}</div>
                    @if($item->product_alias_sku)
                        <div style="font-size: 8pt; font-family: monospace;">{{ $item->product_alias_sku }}</div>
                    @endif
                    <div style="font-size: 8pt; color: #333;">{{ $item->description ?? $item->product->description ?? ' ' }}</div>
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
                <td class="italic" style="font-size: 8pt; padding-top: 10px;">{{ $item->notes ?? '' }}</td>
            </tr>
            @endforeach
            
            <!-- Spacer Row (Adjusted for better bottom spacing) -->
            <tr class="item-row">
                <td style="height: 220px;"></td>
                <td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>

            <!-- Totals Rows (part of same table to align borders) -->
            <tr class="totals-row">
                <td colspan="2" class="text-center" style="border-right: 1pt solid #000;">Total</td>
                <td class="text-center font-bold">{{ number_format($totalQty, 0, ',', '.') }}</td>
                <td style="border-right: 1pt solid #000;"></td>
                <td style="border-right: 1pt solid #000;"></td>
                <td class="text-right font-bold">
                     <div style="float: left; padding-left: 5px;">IDR</div>
                     <div style="float: right; padding-right: 5px;">{{ number_format($purchaseOrder->total / (1 + ($purchaseOrder->tax_percent/100)), 0, ',', '.') }}</div>
                </td>
                <td></td>
            </tr>
            <tr class="totals-row">
                <td colspan="5" class="text-right" style="border-right: 1pt solid #000;">Tax PPn {{ $purchaseOrder->tax_percent }}%</td>
                <td class="text-right font-bold">
                    <div style="float: left; padding-left: 5px;">IDR</div>
                    <div style="float: right; padding-right: 5px;">{{ number_format($purchaseOrder->total * ($purchaseOrder->tax_percent/100) / (1 + ($purchaseOrder->tax_percent/100)), 0, ',', '.') }}</div>
                </td>
                <td></td>
            </tr>
            <tr class="totals-row">
                <td colspan="5" class="text-right uppercase font-bold" style="border-right: 1pt solid #000;">GRAND TOTAL</td>
                <td class="text-right font-bold">
                    <div style="float: left; padding-left: 5px;">IDR</div>
                    <div style="float: right; padding-right: 5px;">{{ number_format($purchaseOrder->total, 0, ',', '.') }}</div>
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Footer Boxes -->
    <table class="footer-boxes">
        <tr>
            <td class="box-cell" width="55%">
                <div class="font-bold">Please deliver to :</div>
                <div style="margin-top: 5px; font-weight: bold;">PT. JIDOKA RESULT INDONESIA</div>
                <div>Jl. Pinang Blok F16 - Nomor 18C Delta Silicon 3</div>
                <div>Kawasan Industri Lippo - Cikarang, Desa Cicau, Kecamatan Cikarang Pusat</div>
                <div>Kabupaten Bekasi, Provinsi Jawa Barat</div>
            </td>
            <td class="box-cell" style="border-right: none;">
                <div class="font-bold italic">Remarks :</div>
                <div class="italic" style="margin-top: 5px;">
                    {!! nl2br(e($purchaseOrder->notes ?? '-')) !!}
                </div>
                <div style="height: 10px;"></div>
                <div class="font-bold italic">Terms of payment :</div>
                <div class="italic" style="margin-top: 5px;">
                    - Term of Payment {{ $purchaseOrder->payment_terms ?? '30' }} Days after invoice Received
                </div>
            </td>
        </tr>
    </table>

    <!-- Signatures -->
    <div class="signatures-section">
        <!-- 
           Image 4: Top row: Supplier (Left Box) --- Authorized | Checked | Prepared (Right Box Combined)
        -->
        <table width="100%">
            <tr>
                <td width="20%" style="vertical-align: top;">
                    <table class="sig-table">
                        <tr><td class="sig-header">Supplier</td></tr>
                        <tr><td class="italic" style="border-bottom: none; text-align: left; padding-left: 5px;">Date :</td></tr>
                        <tr><td class="sig-space"></td></tr>
                    </table>
                </td>
                <td width="20%"></td>
                <td width="60%" style="vertical-align: top;">
                    <table class="sig-table">
                        <tr>
                            <td width="33%">Authorized</td>
                            <td width="33%">Checked</td>
                            <td width="33%">Prepared</td>
                        </tr>
                        <tr>
                            <td class="italic">Date : {{ $purchaseOrder->approved_at ? \Carbon\Carbon::parse($purchaseOrder->approved_at)->format('d-m-Y') : \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d-m-Y') }}</td>
                            <td class="italic">Date : {{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d-m-Y') }}</td>
                            <td class="italic">Date : {{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <td style="height: 60px;"></td>
                            <td style="height: 60px;"></td>
                            <td style="height: 60px;"></td>
                        </tr>
                        <tr class="font-bold italic">
                            <td style="padding: 5px;">Jahrudin</td>
                            <td style="padding: 5px;">Ely Susanti</td>
                            <td style="padding: 5px;">
                                Agus Suprianto
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

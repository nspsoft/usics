<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0.5cm 1cm;
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
        .content-wrapper {
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-section {
            margin-bottom: 5px;
        }
        .company-logo-text {
            font-size: 24pt;
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
            margin: -5px 0 3px 0;
        }
        .company-address {
            font-size: 8pt;
            line-height: 1.2;
        }

        .billing-section {
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .billing-table td {
            vertical-align: top;
        }
        .bill-to-box {
            border: 0.8pt solid #000;
            padding: 5px 10px;
            width: 380px;
            min-height: 80px;
        }
        .info-table {
            width: 280px;
            float: right;
        }
        .info-table td {
            padding: 2px 0;
            font-size: 10pt;
        }
        .info-label {
            width: 90px;
            text-transform: uppercase;
        }
        .info-separator {
            width: 15px;
            text-align: center;
        }
        .info-value {
            font-weight: bold;
        }

        .items-table {
            margin-top: 10px;
            border: 1.2pt solid #000;
        }
        .items-table th {
            border: 0.5pt solid #000;
            padding: 4px;
            font-weight: bold;
            text-align: center;
            background-color: #fff;
        }
        .items-table td {
            border-right: 0.5pt solid #000;
            padding: 4px;
            vertical-align: top;
        }
        .items-table td:last-child {
            border-right: none;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .summary-section {
            margin-top: 10px;
        }
        .summary-table {
            width: 300px;
            float: right;
        }
        .summary-table td {
            padding: 2px 5px;
            font-weight: bold;
        }
        .summary-label {
            width: 120px;
            text-align: right;
        }
        .summary-separator {
            width: 15px;
            text-align: center;
        }
        .summary-value {
            text-align: right;
            border-bottom: 0.5pt solid #000;
        }

        .footer-section {
            margin-top: 40px;
            clear: both;
            page-break-inside: avoid;
        }
        .bank-info-box {
            border: 0.8pt solid #000;
            padding: 8px 12px;
            width: 350px;
            float: left;
        }
        .signature-box {
            width: 250px;
            float: right;
            text-align: center;
        }
        .signature-space {
            height: 90px;
        }
        .signature-line {
            border-top: 0.8pt solid #000;
            width: 180px;
            margin: 0 auto;
            font-weight: bold;
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #E21E26; color: white; border: none; border-radius: 5px; font-weight: bold;">PRINT INVOICE</button>
    </div>

    <div class="content-wrapper">
        <!-- Header -->
        <table class="header-section">
            <tr>
                <td width="60%">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <img src="/images/jri-official-logo.png" alt="logo" style="height: 60px;">
                        <div>
                            <div class="company-logo-text">jidoka</div>
                            <div class="company-full-name">PT. JIDOKA RESULT INDONESIA</div>
                        </div>
                    </div>
                    <div class="company-address" style="margin-top: 10px;">
                        Kawasan Industri JABABEKA I<br>
                        Jl. Jababeka II Blok C No. 19 L<br>
                        Pasir Gombong, Cikarang Utara, Bekasi 17530 Jawa Barat<br>
                        Telp : +62 21 89383915, Fax. : +62 21 89383915<br>
                        e_mail : accounting@jidoka.co.id
                    </div>
                </td>
                <td width="40%" style="text-align: right; vertical-align: top;">
                    <div style="display: inline-block; text-align: center;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('sales.invoices.public-validate', $invoice->public_uuid ?: $invoice->id)) }}" style="width: 80px; height: 80px;">
                        <div style="font-size: 8pt; font-weight: bold; margin-top: 5px; color: #003680; line-height: 1;">SCAN FOR VALIDATION</div>
                        <div style="font-size: 7pt; color: #666; margin-top: 3px;">Official JIDOKA Document</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Billing Info -->
        <div class="billing-section">
            <table class="billing-table">
                <tr>
                    <td width="60%">
                        <div class="bill-to-box">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="70" class="font-bold">Bill To</td>
                                    <td width="20" class="text-center">:</td>
                                    <td class="font-bold" style="font-size: 11pt;">{{ $invoice->customer->name }}</td>
                                </tr>
                                <tr>
                                    <td class="font-bold">Address</td>
                                    <td class="text-center">:</td>
                                    <td>
                                        {{ $invoice->customer->full_address }}<br>
                                        Phone : {{ $invoice->customer->phone ?? '-' }}@if($invoice->customer->fax), Fax : {{ $invoice->customer->fax }}@endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-bold">Attn</td>
                                    <td class="text-center">:</td>
                                    <td class="font-bold">Accounting Dept</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td width="40%" style="padding-left: 40px;">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Invoice No.</td>
                                <td class="info-separator">:</td>
                                <td class="info-value">{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Date</td>
                                <td class="info-separator">:</td>
                                <td class="info-value">{{ $invoice->invoice_date->locale('id')->translatedFormat('d F Y') }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">PO No.</td>
                                <td class="info-separator">:</td>
                                <td class="info-value">{{ $invoice->salesOrder->customer_po_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">Currency</td>
                                <td class="info-separator">:</td>
                                <td class="info-value">IDR</td>
                            </tr>
                            <tr>
                                <td class="info-label">Terms</td>
                                <td class="info-separator">:</td>
                                <td class="info-value">30 Days</td>
                            </tr>
                            <tr>
                                <td class="info-label">DO No.</td>
                                <td class="info-separator">:</td>
                                <td class="info-value">{{ $invoice->formatted_do_numbers }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="40" style="border-right: 0.5pt solid #000;">No</th>
                    <th style="border-right: 0.5pt solid #000;">Description</th>
                    <th width="50" style="border-right: 0.5pt solid #000;">Qty</th>
                    <th width="50" style="border-right: 0.5pt solid #000;">UOM</th>
                    <th width="100" style="border-right: 0.5pt solid #000;">Price</th>
                    <th width="100">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Group items by Sales Order Item ID to consolidate partial deliveries
                    $groupedItems = $invoice->items->groupBy(function ($item) {
                         return $item->sales_order_item_id ?? 'manual-' . $item->id; 
                    });
                    $counter = 1;
                @endphp

                @foreach($groupedItems as $groupId => $items)
                    @php
                        $firstItem = $items->first();
                        $totalQty = $items->sum('qty');
                        $totalSubtotal = $items->sum('subtotal');
                    @endphp
                <tr>
                    <td class="text-center">{{ $counter++ }}</td>
                    <td>
                        <div class="font-bold">{{ $firstItem->product_alias_name ?? $firstItem->product->name }}</div>
                        @if($firstItem->product_alias_sku)
                            <div style="font-size: 8pt; font-family: monospace;">{{ $firstItem->product_alias_sku }}</div>
                        @endif
                        <div style="font-size: 8.5pt; color: #444;">{{ $firstItem->product->description }}</div>
                    </td>
                    <td class="text-center">{{ number_format($totalQty, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $firstItem->unit->code ?? 'PCs' }}</td>
                    <td class="text-right">{{ number_format($firstItem->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($totalSubtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <!-- Placeholder rows for spacing consistency -->
                @for ($i = count($invoice->items); $i < 6; $i++)
                <tr>
                    <td style="height: 15px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @endfor
                <!-- Last border bottom -->
                <tr>
                    <td colspan="6" style="border-top: 1.2pt solid #000; height: 0; padding: 0;"></td>
                </tr>
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary-section">
            <table class="summary-table">
                <tr>
                    <td class="summary-label">Subtotal</td>
                    <td class="summary-separator">:</td>
                    <td width="30">IDR</td>
                    <td class="summary-value">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="summary-label">Discount</td>
                    <td class="summary-separator">:</td>
                    <td>IDR</td>
                    <td class="summary-value">{{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="summary-label" style="border-bottom: 0.8pt solid #000;">Subtotal</td>
                    <td class="summary-separator" style="border-bottom: 0.8pt solid #000;">:</td>
                    <td style="border-bottom: 0.8pt solid #000;">IDR</td>
                    <td class="summary-value" style="border-bottom: 0.8pt solid #000;">{{ number_format($invoice->subtotal - $invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="summary-label">VAT (11%)</td>
                    <td class="summary-separator">:</td>
                    <td>IDR</td>
                    <td class="summary-value">{{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="summary-label">Grand Total</td>
                    <td class="summary-separator">:</td>
                    <td style="border-bottom: 1.5pt double #000;">IDR</td>
                    <td class="summary-value" style="border-bottom: 1.5pt double #000; font-size: 11pt;">{{ number_format($invoice->total, 0, ',', '.') }}</td>
                </tr>
            </table>
            <div style="clear: both;"></div>
        </div>

        <!-- Final Footer inside content for flow -->
        <div class="footer-section">
            <div class="bank-info-box">
                <div class="font-bold">Kindly T/T The Payment To Our Bank :</div>
                <div style="margin-top: 10px;">
                    <div class="font-bold" style="color: #003680;">Bank MANDIRI</div>
                    <div class="font-bold">KK Karawang Galuh Mas</div>
                    <div class="font-bold" style="font-size: 11pt; letter-spacing: 1px;">173-00-0777778-3</div>
                    <div class="font-bold text-uppercase">PT JIDOKA RESULT INDONESIA</div>
                </div>
            </div>

            <div class="signature-box">
                <div style="margin-bottom: 15px;">Cikarang, {{ $invoice->invoice_date->locale('id')->translatedFormat('d F Y') }}</div>
                <div class="signature-space" style="position: relative;">
                    @if($invoice->emeterai_serial)
                    <div style="position: absolute; top: 5px; left: 50%; transform: translateX(-50%); text-align: center;">
                        <div style="width: 80px; height: 80px; border: 2.5px solid #003680; border-radius: 8px; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, #f0f4ff 0%, #e8edff 100%); padding: 4px;">
                            <div style="font-size: 5pt; font-weight: 900; color: #003680; letter-spacing: 1px; text-transform: uppercase;">e-METERAI</div>
                            <div style="font-size: 14pt; font-weight: 900; color: #E21E26; margin: 2px 0;">Rp10.000</div>
                            <div style="font-size: 4.5pt; color: #003680; font-weight: 700;">PERURI</div>
                            <div style="font-size: 4pt; color: #666; margin-top: 2px; font-family: monospace;">{{ $invoice->emeterai_serial }}</div>
                        </div>
                        <div style="font-size: 6pt; color: #888; margin-top: 2px;">{{ $invoice->emeterai_stamped_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @endif
                </div>
                <div class="signature-line">Jahrudin</div>
                <div style="font-size: 9pt;">Direktur</div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>

    @php
        // Group items by DO Number for merging
        $groupedAppendix = $invoice->items->sortBy(function($item) {
            return $item->deliveryOrder?->do_number ?? 'ZZZZ';
        })->groupBy(function($item) {
            return $item->deliveryOrder?->do_number ?? 'No Reference';
        });
        
        $counter = 1;
    @endphp

    @if($groupedAppendix->count() > 0)
    <div style="page-break-before: always; padding: 20px;">
        <div style="border-bottom: 2pt solid #003680; margin-bottom: 15px; padding-bottom: 5px;">
            <div style="font-size: 14pt; font-weight: bold; color: #003680;">APPENDIX: DELIVERY DETAILS</div>
            <div style="font-size: 8pt; color: #666;">Detailed breakdown for Invoice #{{ $invoice->invoice_number }}</div>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
            <thead style="background-color: #f0f4f8;">
                <tr>
                    <th style="padding: 6px; border: 1pt solid #ddd; text-align: center; width: 30px;">No</th>
                    <th style="padding: 6px; border: 1pt solid #ddd; text-align: center; width: 80px;">Date</th>
                    <th style="padding: 6px; border: 1pt solid #ddd; text-align: left; width: 150px;">DO Number</th>
                    <th style="padding: 6px; border: 1pt solid #ddd; text-align: left;">Product Description</th>
                    <th style="padding: 6px; border: 1pt solid #ddd; text-align: center; width: 50px;">Qty</th>
                    <th style="padding: 6px; border: 1pt solid #ddd; text-align: center; width: 40px;">UOM</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedAppendix as $doNumber => $items)
                    @foreach($items as $index => $item)
                    <tr>
                        <td style="padding: 5px; border: 1pt solid #ddd; text-align: center;">{{ $counter++ }}</td>
                        
                        {{-- Merge Date and DO Number cells --}}
                        @if($index === 0)
                            <td rowspan="{{ $items->count() }}" style="padding: 5px; border: 1pt solid #ddd; text-align: center; vertical-align: middle; background-color: #fff;">
                                {{ $item->deliveryOrder?->delivery_date ? $item->deliveryOrder->delivery_date->locale('id')->translatedFormat('d F Y') : '-' }}
                            </td>
                            <td rowspan="{{ $items->count() }}" style="padding: 5px; border: 1pt solid #ddd; vertical-align: middle; background-color: #fff;">
                                {{ $doNumber }}
                            </td>
                        @endif

                        <td style="padding: 5px; border: 1pt solid #ddd;">
                            <div style="font-weight: bold;">{{ $item->product_alias_name ?? $item->product->name }}</div>
                            @if($item->product_alias_sku)
                                <div style="font-size: 8pt; font-family: monospace;">{{ $item->product_alias_sku }}</div>
                            @endif
                        </td>
                        <td style="padding: 5px; border: 1pt solid #ddd; text-align: center;">
                            {{ number_format($item->qty, 0, ',', '.') }}
                        </td>
                        <td style="padding: 5px; border: 1pt solid #ddd; text-align: center;">
                            {{ $item->unit->code ?? 'PC' }}
                        </td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px; font-size: 7pt; color: #888; text-align: center; font-style: italic;">
            End of Appendix. All items have been received in good condition.
        </div>
    </div>
    @endif

</body>
</html>

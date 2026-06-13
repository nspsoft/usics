<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Schedule Monitoring - {{ $period }}</title>
    <style>
        @page {
            margin: 0.5cm 0.8cm;
            size: A4 landscape;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Header */
        .header-table td { vertical-align: top; }
        .company-logo-text {
            font-size: 20pt;
            font-weight: 900;
            font-style: italic;
            color: #E21E26;
            letter-spacing: -1px;
            margin: 0;
            line-height: 1;
        }
        .company-full-name {
            font-size: 9pt;
            font-weight: 800;
            color: #003680;
            margin: -3px 0 3px 0;
        }
        .company-address {
            font-size: 7pt;
            line-height: 1.2;
        }
        .doc-title {
            font-size: 14pt;
            font-weight: 900;
            font-style: italic;
            color: #003680;
            text-align: right;
            margin-top: 5px;
        }
        .doc-subtitle {
            font-size: 8pt;
            text-align: right;
            color: #444;
            margin-top: 3px;
        }
        .header-line {
            border-bottom: 2pt solid #003680;
            margin: 8px 0 12px 0;
        }

        /* Matrix Table */
        .matrix-table {
            border: 1pt solid #000;
            margin-top: 5px;
        }
        .matrix-table th {
            border: 0.5pt solid #000;
            padding: 4px 3px;
            font-weight: bold;
            text-align: center;
            background-color: #e8ecf0;
            font-size: 7pt;
        }
        .matrix-table td {
            border: 0.5pt solid #000;
            padding: 3px 2px;
            vertical-align: top;
            font-size: 7pt;
        }
        .matrix-table .customer-cell {
            font-weight: 900;
            color: #003680;
            font-size: 8pt;
            padding: 5px;
        }
        .matrix-table .product-cell {
            font-weight: bold;
            font-size: 7.5pt;
            padding: 4px;
        }
        .matrix-table .label-cell {
            font-weight: bold;
            text-align: center;
            font-size: 6.5pt;
            text-transform: uppercase;
            background-color: #f5f5f5;
            width: 55px;
        }
        .label-sch { color: #333; }
        .label-del { color: #003680; }
        .label-bal { color: #000; background-color: #e0e4e8 !important; }
        .cell-delivery { color: #003680; font-weight: bold; }
        .cell-balance-neg { color: #E21E26; font-weight: 900; background-color: #fef2f2; }
        .cell-balance-pos { color: #003680; font-weight: bold; }
        .total-cell { background-color: #f0f2f5; font-weight: 900; }
        .total-neg { background-color: #fde8e8; color: #E21E26; font-weight: 900; }
        .balance-row { background-color: #f8f9fb; }
        .today-header { background-color: #dbeafe !important; color: #003680 !important; font-weight: 900; }

        /* Signatures */
        .signatures-section { margin-top: 30px; }
        .sig-table { width: 100%; table-layout: fixed; }
        .sig-table td { text-align: center; vertical-align: top; font-size: 8pt; }
        .sig-box {
            border: 0.5pt solid #000;
            height: 70px;
            margin: 5px 10px;
            position: relative;
        }
        .sig-label {
            position: absolute;
            bottom: 5px;
            width: 100%;
            text-align: center;
            font-weight: bold;
            font-size: 7pt;
        }

        /* Footer */
        .doc-footer {
            margin-top: 15px;
            font-size: 7pt;
            color: #888;
            text-align: center;
            border-top: 0.5pt solid #ddd;
            padding-top: 5px;
        }

        /* Print Controls */
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 24px; cursor: pointer; background: #003680; color: white; border: none; border-radius: 5px; font-weight: bold; font-size: 14px;">
            🖨️ PRINT SCHEDULE
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; background: #666; color: white; border: none; border-radius: 5px; font-weight: bold; font-size: 14px;">
            ✕ CLOSE
        </button>
    </div>

    <!-- Official Header -->
    <table class="header-table">
        <tr>
            <td width="55%">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="/images/jri-official-logo.png" alt="logo" style="height: 50px;">
                    <div>
                        <div class="company-logo-text">jidoka</div>
                        <div class="company-full-name">PT. JIDOKA RESULT INDONESIA</div>
                    </div>
                </div>
                <div class="company-address" style="margin-top: 5px;">
                    Kawasan Industri JABABEKA I, Jl. Jababeka II Blok C No. 19 L<br>
                    Cikarang Utara, Bekasi 17530 Jawa Barat | Telp : +62 21 89383915
                </div>
            </td>
            <td width="45%">
                <div class="doc-title">DELIVERY SCHEDULE MONITORING</div>
                <div class="doc-subtitle">
                    Period: {{ $period }}<br>
                    Printed: {{ $printDate }}
                </div>
            </td>
        </tr>
    </table>
    <div class="header-line"></div>

    <!-- Matrix Table -->
    <table class="matrix-table">
        <thead>
            <tr>
                <th style="width: 140px;" rowspan="2">CUSTOMER</th>
                <th style="width: 170px;" rowspan="2">PRODUCT</th>
                <th style="width: 55px;" rowspan="2">DATA</th>
                @foreach($headers as $header)
                    <th class="{{ ($mode === 'daily' && $header === $today) ? 'today-header' : '' }}">
                        @if($mode === 'weekly')
                            {!! nl2br(e($header['label'])) !!}
                        @else
                            {{ \Carbon\Carbon::parse($header)->format('d-M') }}
                        @endif
                    </th>
                @endforeach
                <th style="width: 50px; background-color: #d0d5dd;" rowspan="2">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matrix as $customer)
                @foreach($customer['products'] as $pIdx => $product)
                    {{-- Schedule Row --}}
                    <tr style="border-top: 1.5pt solid #000;">
                        @if($loop->first)
                        <td class="customer-cell" rowspan="{{ count($customer['products']) * 3 }}">
                            {{ $customer['customer_name'] }}
                            <div style="font-size: 6pt; color: #777; font-weight: normal; margin-top: 3px; font-family: monospace;">{{ $customer['customer_code'] }}</div>
                        </td>
                        @endif
                        <td class="product-cell" rowspan="3">
                            {{ $product['product_name'] }}
                            <div style="font-size: 6pt; color: #666; font-family: monospace;">{{ $product['sku'] }} ({{ $product['unit'] }})</div>
                            @if($product['po_number'])
                            <div style="font-size: 6pt; color: #888;">PO: {{ $product['po_number'] }}</div>
                            @endif
                        </td>
                        <td class="label-cell label-sch">SCHEDULE</td>
                        @foreach($headers as $header)
                            @php   $key = $mode === 'weekly' ? $header['key'] : $header;   @endphp
                            <td class="text-right">
                                {{ ($product['daily'][$key]['sch'] ?? 0) > 0 ? number_format($product['daily'][$key]['sch'], 1, ',', '.') : '-' }}
                            </td>
                        @endforeach
                        <td class="text-right total-cell">{{ number_format($product['totals']['sch'], 1, ',', '.') }}</td>
                    </tr>
                    {{-- Delivery Row --}}
                    <tr>
                        <td class="label-cell label-del">DELIVERY</td>
                        @foreach($headers as $header)
                            @php   $key = $mode === 'weekly' ? $header['key'] : $header;   @endphp
                            <td class="text-right cell-delivery">
                                {{ ($product['daily'][$key]['act'] ?? 0) > 0 ? number_format($product['daily'][$key]['act'], 1, ',', '.') : '-' }}
                            </td>
                        @endforeach
                        <td class="text-right total-cell cell-delivery">{{ number_format($product['totals']['act'], 1, ',', '.') }}</td>
                    </tr>
                    {{-- Balance Row --}}
                    <tr class="balance-row">
                        <td class="label-cell label-bal">BALANCE</td>
                        @php
                            $cumSch = 0;
                            $cumAct = 0;
                        @endphp
                        @foreach($headers as $header)
                            @php
                                $key = $mode === 'weekly' ? $header['key'] : $header;
                                $sch = $product['daily'][$key]['sch'] ?? 0;
                                $act = $product['daily'][$key]['act'] ?? 0;
                                if ($accumulate) {
                                    $cumSch += $sch;
                                    $cumAct += $act;
                                    $bal = $cumAct - $cumSch;
                                } else {
                                    $bal = $product['daily'][$key]['bal'] ?? 0;
                                }
                            @endphp
                            <td class="text-right {{ $bal < 0 ? 'cell-balance-neg' : ($bal > 0 ? 'cell-balance-pos' : '') }}">
                                {{ $bal != 0 ? number_format($bal, 1, ',', '.') : '-' }}
                            </td>
                        @endforeach
                        <td class="text-right {{ $product['totals']['bal'] < 0 ? 'total-neg' : 'total-cell' }}">
                            {{ number_format($product['totals']['bal'], 1, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <!-- Signatures -->
    <div class="signatures-section">
        <table class="sig-table">
            <tr>
                <td>
                    Dibuat Oleh,
                    <div class="sig-box"><div class="sig-label">( PPIC / Planning )</div></div>
                </td>
                <td>
                    Diperiksa Oleh,
                    <div class="sig-box"><div class="sig-label">( Sales Manager )</div></div>
                </td>
                <td>
                    Diketahui Oleh,
                    <div class="sig-box"><div class="sig-label">( Direktur )</div></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="doc-footer">
        Dokumen ini dicetak otomatis oleh sistem ERP JICOS pada {{ $printDate }} — PT JIDOKA RESULT INDONESIA — Confidential
    </div>
</body>
</html>

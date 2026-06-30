<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Product Labels</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0.5cm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #000;
        }
        .no-print-bar {
            background: #f1f5f9;
            padding: 10px 20px;
            text-align: right;
            border-bottom: 1px solid #cbd5e1;
        }
        .print-btn {
            padding: 8px 16px;
            background: #003680;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        .labels-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            padding: 10px;
        }
        .label-card {
            border: 3px double #000;
            box-sizing: border-box;
            background: #fff;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .label-header {
            display: flex;
            align-items: stretch;
            width: 100%;
            border-bottom: 3px double #000;
            background: #fff;
            height: 54px;
            box-sizing: border-box;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 4px 10px;
            flex-grow: 1;
        }
        .header-left img {
            height: 32px;
            width: auto;
            display: block;
        }
        .company-title {
            font-size: 10pt;
            font-weight: 800;
            color: #000;
            font-family: Arial, sans-serif;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .header-right {
            width: 18%;
            display: flex;
            align-items: center;
            justify-content: center;
            border-left: 3px double #000;
            padding: 4px;
            box-sizing: border-box;
        }
        .label-table {
            width: 100%;
            border-collapse: collapse;
        }
        .label-table td {
            padding: 4px 8px;
            font-size: 8.5pt;
            line-height: 1.2;
            border-bottom: 1px solid #000;
            vertical-align: middle;
        }
        .label-table tr:last-child td {
            border-bottom: none;
        }
        .field-label {
            width: 28%;
            font-weight: bold;
        }
        .field-separator {
            width: 2%;
            text-align: center;
            padding: 4px 0;
        }
        .field-value {
            width: 70%;
            font-weight: bold;
        }
        @media print {
            .no-print-bar {
                display: none;
            }
            body {
                padding: 0;
            }
            .labels-grid {
                padding: 0;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <button class="print-btn" onclick="window.print()">PRINT LABELS</button>
    </div>

    <div class="labels-grid">
        @foreach($labels as $label)
        <div class="label-card">
            <div class="label-header">
                <div class="header-left">
                    <img src="{{ \App\Models\AppSetting::get('company_logo_path', '/images/jri-official-logo.png') }}" alt="Logo">
                    <span class="company-title">{{ \App\Models\AppSetting::get('company_full_name', 'PT. JIDOKA RESULT INDONESIA') }}</span>
                </div>
                <div class="header-right">
                    @php
                        $qrData = "SKU: " . $label['sku'] . " | Qty: " . $label['qty'] . " | Lot: " . ($label['lot_number'] ?: '-') . " | SPK: " . ($label['spk'] ?: '-');
                    @endphp
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qrData) }}" style="height: 38px; width: 38px; display: block;" alt="QR Code">
                </div>
            </div>
            <table class="label-table">
                <tr>
                    <td class="field-label">Customer</td>
                    <td class="field-separator">:</td>
                    <td class="field-value">{{ $label['customer_name'] }}</td>
                </tr>
                <tr>
                    <td class="field-label">Product Name</td>
                    <td class="field-separator">:</td>
                    <td class="field-value">{{ $label['product_name'] }}</td>
                </tr>
                <tr>
                    <td class="field-label">Specification</td>
                    <td class="field-separator">:</td>
                    <td class="field-value">{{ $label['specification'] }}</td>
                </tr>
                <tr>
                    <td class="field-label">Size</td>
                    <td class="field-separator">:</td>
                    <td class="field-value">{{ $label['size'] }}</td>
                </tr>
                <tr>
                    <td class="field-label">Quantity</td>
                    <td class="field-separator">:</td>
                    <td class="field-value">{{ $label['qty'] }}</td>
                </tr>
                <tr>
                    <td class="field-label">Lot Number</td>
                    <td class="field-separator">:</td>
                    <td class="field-value">{{ $label['lot_number'] }}</td>
                </tr>
                <tr>
                    <td class="field-label">SPK</td>
                    <td class="field-separator">:</td>
                    <td class="field-value">{{ $label['spk'] }}</td>
                </tr>
                <tr>
                    <td class="field-label">Note</td>
                    <td class="field-separator">:</td>
                    <td class="field-value">{{ $label['note'] }}</td>
                </tr>
            </table>
        </div>
        @endforeach
    </div>
</body>
</html>

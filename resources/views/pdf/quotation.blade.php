<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #d32f2f; /* SPINDO Red */
        }
        .company-info {
            float: right;
            text-align: right;
        }
        .customer-info {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 50px;
            font-size: 10px;
            color: #777;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">PT SPINDO</div>
        <div class="company-info">
            <strong>PT Steel Pipe Industry of Indonesia, Tbk</strong><br>
            Jl. Kalibutuh No. 189-191<br>
            Surabaya, Indonesia<br>
            Phone: (031) 532-0921
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="customer-info">
        <h3>PENAWARAN HARGA (DRAFT)</h3>
        <p>
            <strong>Kepada Yth:</strong><br>
            {{ $customer['name'] ?? 'Pelanggan Terhormat' }}<br>
            {{ $customer['phone'] ?? '-' }}
        </p>
        <p>
            Tanggal: {{ now()->format('d F Y') }}<br>
            Nomor: DRAFT/WA/{{ now()->timestamp }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Produk</th>
                <th style="width: 80px;">Qty</th>
                <th style="width: 120px;">Harga Satuan</th>
                <th style="width: 120px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item['name'] }}</strong><br>
                    <small>{{ $item['sku'] }}</small>
                </td>
                <td style="text-align: center;">{{ $item['qty'] }}</td>
                <td class="text-right">
                    {{ $item['price'] > 0 ? 'Rp ' . number_format($item['price'], 0, ',', '.') : 'Call for Price' }}
                </td>
                <td class="text-right">
                    {{ $item['price'] > 0 ? 'Rp ' . number_format($item['price'] * $item['qty'], 0, ',', '.') : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>Subtotal</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($total_amount, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td colspan="4" class="text-right">PPN (11%)</td>
                <td class="text-right">Rp {{ number_format($total_amount * 0.11, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="4" class="text-right" style="font-size: 14px;"><strong>Grand Total</strong></td>
                <td class="text-right" style="font-size: 14px;"><strong>Rp {{ number_format($total_amount * 1.11, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px;">
        <p><strong>Catatan:</strong></p>
        <ul>
            <li>Harga di atas adalah estimasi dan dapat berubah sewaktu-waktu.</li>
            <li>Dokumen ini adalah <strong>Draft Otomatis</strong> dari WhatsApp Bot.</li>
            <li>Silakan hubungi Sales kami untuk validasi dan penerbitan penawaran resmi.</li>
        </ul>
    </div>

    <div class="footer">
        Dicetak otomatis oleh Sistem AI WhatsApp PT JIDOKA pada {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>

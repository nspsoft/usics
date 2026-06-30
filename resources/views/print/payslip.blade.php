<!DOCTYPE html>
<html>
<head>
    <title>Slip Gaji - {{ $payroll->employee->full_name }} - {{ $payroll->period_month }}/{{ $payroll->period_year }}</title>
    <style>
        @page {
            margin: 0.3cm;
            size: A5 landscape;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.1;
            color: #000;
            margin: 0;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-logo-img {
            height: 40px;
            float: left;
            margin-right: 12px;
        }
        .company-header-text {
            color: #E21E26;
            font-weight: 900;
            font-style: italic;
            font-size: 18pt;
            letter-spacing: -1px;
            margin: 0;
            line-height: 1;
        }
        .company-header-sub {
            color: #003680;
            font-weight: 800;
            font-size: 11pt;
            margin: -2px 0 3px 0;
        }
        .company-address {
            font-size: 7pt;
            line-height: 1.2;
            clear: left;
            padding-top: 3px;
        }
        .doc-title {
            text-align: right;
            font-size: 14pt;
            font-weight: 900;
            font-style: italic;
            color: #008000;
        }
        .details-section {
            margin-top: 15px;
        }
        .items-table {
            margin-top: 10px;
            border: 0.5pt solid #000;
        }
        .items-table th {
            border: 0.5pt solid #000;
            padding: 5px;
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 7pt;
        }
        .items-table td {
            border: 0.5pt solid #000;
            padding: 5px;
            vertical-align: top;
            font-size: 7.5pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        .verification-section {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .qr-box {
            text-align: center;
            width: 80px;
        }
        .qr-image {
            width: 60px;
            height: 60px;
            display: block;
            margin: 0 auto 5px;
        }
        .qr-text {
            font-size: 6pt;
            color: #555;
            font-weight: bold;
        }

        @media print {
            .no-print { display: none; }
        }
        .summary-box {
            margin-top: 10px; 
            border: 1pt solid #000; 
            padding: 8px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 8px 15px; background: #008000; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 8pt;">CETAK SLIP (A5)</button>
    </div>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td width="65%">
                <img src="{{ \App\Models\AppSetting::get('company_logo_path', '/images/jri-official-logo.png') }}" alt="logo" class="company-logo-img">
                <div>
                    <div class="company-header-text">{{ \App\Models\AppSetting::get('company_logo_text', 'jidoka') }}</div>
                    <div class="company-header-sub">{{ \App\Models\AppSetting::get('company_full_name', 'PT. JIDOKA RESULT INDONESIA') }}</div>
                </div>
                <div class="company-address">
                    {!! nl2br(e(\App\Models\AppSetting::get('company_address', 'Kawasan Industri JABABEKA I, Jl. Jababeka II Blok C No. 19 L
Cikarang Utara, Bekasi 17530 Jawa Barat'))) !!}
                </div>
            </td>
            <td width="35%">
                <div class="doc-title" style="margin-top: 0;">SLIP GAJI</div>
                <table style="float: right; font-size: 7pt;">
                    <tr>
                        <td class="font-bold">No. Slip</td>
                        <td width="10" class="text-center">:</td>
                        <td class="text-right">SG-{{ str_pad($payroll->id, 6, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold">Periode</td>
                        <td class="text-center">:</td>
                        <td class="font-bold text-right">{{ \Carbon\Carbon::create(null, $payroll->period_month)->format('F') }} {{ $payroll->period_year }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="details-section">
        <table width="100%">
            <tr>
                <td width="60%">
                    <div style="padding-left: 8px; border-left: 1.5pt solid #008000;">
                        <span class="font-bold" style="font-size: 10pt;">{{ $payroll->employee->full_name }}</span><br>
                        NIK: <span class="font-mono">{{ $payroll->employee->nik }}</span> | Dept: {{ $payroll->employee->department->name }}
                    </div>
                </td>
                <td width="40%" style="text-align: right;">
                    <div class="font-bold">{{ $payroll->employee->position->name }}</div>
                    <div style="font-size: 7pt;">Status: {{ ucfirst($payroll->employee->employment_status) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Keterangan Komponen Gaji</th>
                <th width="100">Pendapatan</th>
                <th width="100">Potongan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td>Gaji Pokok</td>
                <td class="text-right">{{ number_format($payroll->basic_salary, 0, ',', '.') }}</td>
                <td class="text-right">0</td>
            </tr>
            @php $i = 2; @endphp
            @foreach($payroll->items as $item)
            <tr>
                <td class="text-center">{{ $i++ }}</td>
                <td>{{ $item->name }}</td>
                <td class="text-right {{ $item->type == 'allowance' ? 'font-bold' : '' }}">
                    {{ $item->type == 'allowance' ? number_format($item->amount, 0, ',', '.') : '0' }}
                </td>
                <td class="text-right {{ $item->type == 'deduction' ? 'font-bold' : '' }}">
                    {{ $item->type == 'deduction' ? number_format($item->amount, 0, ',', '.') : '0' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <table width="100%">
            <tr>
                <td class="font-bold" style="font-size: 10pt;">GAJI BERSIH (TAKE HOME PAY)</td>
                <td class="text-right font-bold" style="font-size: 12pt; color: #008000;">
                    Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <table width="100%" style="margin-top: 15px;">
        <tr>
            <td width="75%" style="vertical-align: top;">
                <div style="border: 0.5pt solid #ccc; padding: 5px; font-size: 6.5pt; font-style: italic; color: #444;">
                    <span class="font-bold" style="text-decoration: underline;">Catatan:</span><br>
                    {{ $payroll->notes ?? 'Dokumen ini sah dan diterbitkan secara elektronik oleh sistem ERP Jidoka. Segala bentuk manipulasi adalah tindakan ilegal.' }}
                </div>
            </td>
            <td width="25%" style="text-align: right; vertical-align: top;">
                <div class="qr-box" style="float: right;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('payroll.public-validate', $payroll->id)) }}" class="qr-image">
                    <div class="qr-text">VERIFIED DOCUMENT</div>
                    <div class="qr-text" style="font-size: 5pt; margin-top: 2px;">SCAN TO VALIDATE</div>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 10px; font-size: 6.5pt; color: #aaa; text-align: center; border-top: 0.3pt solid #eee; padding-top: 3px;">
        Dicetak otomatis pada {{ date('d/m/Y H:i') }} | ERP JIDOKA SYSTEM
    </div>
</body>
</html>

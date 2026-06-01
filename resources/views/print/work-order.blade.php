<!DOCTYPE html>
<html>
<head>
    <title>Work Order - {{ $workOrder->wo_number }}</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 25px;
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
            font-size: 20pt;
            font-weight: 900;
            font-style: italic;
            color: #008000;
        }
        .details-section {
            margin-top: 20px;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 5px 10px;
            font-weight: bold;
            border: 1pt solid #000;
            margin-top: 15px;
            text-transform: uppercase;
            font-size: 8pt;
        }
        .items-table {
            border: 1pt solid #000;
        }
        .items-table th {
            border: 1pt solid #000;
            padding: 8px 5px;
            background-color: #f9f9f9;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
        }
        .items-table td {
            border: 1pt solid #000;
            padding: 8px 5px;
            vertical-align: top;
            font-size: 8.5pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        .verification-row {
            margin-top: 30px;
        }
        .qr-box {
            text-align: center;
            width: 100px;
        }
        .qr-image {
            width: 80px;
            height: 80px;
            display: block;
            margin: 0 auto 5px;
        }
        .qr-text {
            font-size: 7pt;
            color: #555;
            font-weight: bold;
        }

        .sig-table {
            margin-top: 30px;
        }
        .sig-table td {
            text-align: center;
            vertical-align: top;
        }
        .sig-box {
            border: 0.5pt solid #000;
            height: 70px;
            margin: 5px 20px;
        }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #008000; color: white; border: none; cursor: pointer; border-radius: 5px; font-weight: bold;">PRINT WORK ORDER</button>
    </div>

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
                <div style="float: right;">
                    <div class="doc-title" style="font-size: 18pt; text-align: left; margin-bottom: 5px;">WORK ORDER</div>
                    <div class="text-left font-bold" style="color: #666; font-size: 8pt; margin-bottom: 10px;">(PERINTAH KERJA PRODUKSI)</div>
                    <table style="margin-top: 0; float: left;">
                        <tr>
                            <td class="font-bold">No. WO</td>
                            <td width="15" class="text-center">:</td>
                            <td class="text-right font-bold">{{ $workOrder->wo_number }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Priority</td>
                            <td class="text-center">:</td>
                            <td class="text-right font-bold uppercase" style="color: {{ $workOrder->priority === 'urgent' ? '#E21E26' : ($workOrder->priority === 'high' ? '#D97706' : '#000') }}">{{ $workOrder->priority }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Type</td>
                            <td class="text-center">:</td>
                            <td class="text-right uppercase">{{ $workOrder->production_type }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="details-section">
        <table width="100%" style="border: 1pt solid #000; padding: 10px;">
            <tr>
                <td width="55%">
                    <div class="font-bold uppercase" style="font-size: 8pt; text-decoration: underline; margin-bottom: 5px;">Informasi Target Produksi:</div>
                    <table style="font-size: 9pt;">
                        <tr>
                            <td width="100" class="font-bold">Produk Jadi</td>
                            <td width="10">:</td>
                            <td class="font-bold">{{ $workOrder->product->name }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">SKU / Item Code</td>
                            <td>:</td>
                            <td class="font-mono">{{ $workOrder->product->sku }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">BOM Ref</td>
                            <td>:</td>
                            <td>{{ $workOrder->bom->name }} (v{{ $workOrder->bom->version }})</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Gudang Finish</td>
                            <td>:</td>
                            <td>{{ $workOrder->warehouse->name }}</td>
                        </tr>
                    </table>
                </td>
                <td width="45%" style="text-align: right; border-left: 0.5pt solid #ccc; padding-left: 10px;">
                    <div class="font-bold uppercase" style="font-size: 8pt; text-decoration: underline; margin-bottom: 5px;">Rencana Jadwal:</div>
                    <table style="font-size: 9pt; float: right;">
                        <tr>
                            <td class="font-bold">Tgl Mulai</td>
                            <td width="10">:</td>
                            <td>{{ date('d/m/Y', strtotime($workOrder->planned_start)) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tgl Selesai</td>
                            <td>:</td>
                            <td>{{ date('d/m/Y', strtotime($workOrder->planned_end)) }}</td>
                        </tr>
                        <tr style="font-size: 11pt;">
                            <td class="font-bold">JUMLAH TARGET</td>
                            <td>:</td>
                            <td class="font-bold">{{ number_format($workOrder->qty_planned, 0, ',', '.') }} {{ $workOrder->product->unit->symbol ?? 'PCs' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if($workOrder->production_type === 'subcontract')
    <div style="margin-top: 10px; padding: 10px; border: 1pt dashed #003680; background-color: #f0f7ff;">
        <span class="font-bold uppercase text-blue-800" style="font-size: 8pt;">Informasi Subkontraktor:</span><br>
        <span class="font-bold">{{ $workOrder->supplier->name ?? '-' }}</span> | Telp: {{ $workOrder->supplier->phone ?? '-' }}
    </div>
    @endif

    <div class="section-title">Daftar Komponen / Bahan Baku (Bill of Materials)</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="120">Kode Item</th>
                <th>Nama Komponen / Bahan</th>
                <th width="80">Qty/Unit</th>
                <th width="80">Total Target</th>
                <th width="60">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($workOrder->components as $index => $comp)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center font-mono">{{ $comp->product->sku }}</td>
                <td>{{ $comp->product->name }}</td>
                <td class="text-right">
                    @php
                        $qtyPerUnit = ((float) ($workOrder->qty_planned ?? 0)) > 0
                            ? ((float) $comp->qty_required / (float) $workOrder->qty_planned)
                            : 0;
                    @endphp
                    {{ number_format($qtyPerUnit, 4, ',', '.') }}
                </td>
                <td class="text-right font-bold">{{ number_format($comp->qty_required, 4, ',', '.') }}</td>
                <td class="text-center">{{ $comp->unit->symbol ?? $comp->product->unit->symbol ?? 'EA' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Riwayat Produksi (Production Status)</div>
    <table class="items-table">
        <thead>
            <tr>
                <th width="60">Tanggal</th>
                <th width="35">Shift</th>
                <th width="160">Operator / Pelaksana</th>
                <th width="80">Jam</th>
                <th width="55">Proses (mnt)</th>
                <th width="55">Speed (/jam)</th>
                <th width="50">Good</th>
                <th width="50">Reject</th>
                <th width="90">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workOrder->productionEntries as $entry)
            <tr>
                <td class="text-center">{{ date('d/m/y', strtotime($entry->production_date)) }}</td>
                <td class="text-center">{{ $entry->shift }}</td>
                <td>{{ $entry->operatorEmployee->full_name ?? $entry->producedBy->name ?? '-' }}</td>
                @php
                    $startDt = $entry->start_time ? \Carbon\Carbon::parse($entry->production_date . ' ' . $entry->start_time) : null;
                    $endDt = $entry->end_time ? \Carbon\Carbon::parse($entry->production_date . ' ' . $entry->end_time) : null;
                    $grossMin = ($startDt && $endDt) ? $startDt->diffInMinutes($endDt) : null;
                    $downMin = (int) ($entry->downtime_minutes ?? 0);
                    $netMin = ($grossMin !== null) ? max(0, $grossMin - $downMin) : null;
                    $goodQty = (float) ($entry->qty_produced ?? 0);
                    $speed = ($netMin && $netMin > 0) ? ($goodQty / ($netMin / 60)) : null;
                @endphp
                <td class="text-center">
                    {{ $entry->start_time && $entry->end_time ? substr($entry->start_time, 0, 5) . ' - ' . substr($entry->end_time, 0, 5) : '-' }}
                </td>
                <td class="text-right">{{ $netMin !== null ? number_format($netMin, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $speed !== null ? number_format($speed, 2, ',', '.') : '-' }}</td>
                <td class="text-right font-bold">{{ number_format($entry->qty_produced, 0, ',', '.') }}</td>
                <td class="text-right font-bold text-red-600">{{ number_format($entry->qty_rejected, 0, ',', '.') }}</td>
                <td style="font-size: 7pt;">{{ $entry->notes }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center italic" style="padding: 15px;">Belum ada catatan produksi terekam.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="font-bold bg-slate-100">
                <td colspan="6" class="text-right uppercase">Total Akumulasi Produksi:</td>
                <td class="text-right">{{ number_format($workOrder->qty_produced, 0, ',', '.') }}</td>
                <td class="text-right text-red-600">{{ number_format($workOrder->qty_rejected, 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format($workOrder->progress_percent, 0, ',', '.') }} %</td>
            </tr>
        </tfoot>
    </table>

    <table width="100%" class="verification-row">
        <tr>
            <td width="75%" style="vertical-align: top;">
                <div style="border: 0.5pt solid #000; padding: 10px;">
                    <div class="font-bold underline" style="font-size: 8pt; margin-bottom: 5px;">CATATAN PRODUKSI / KENDALA LAPANGAN:</div>
                    <div style="height: 60px; font-style: italic; color: #555;">
                        {{ $workOrder->notes ?? 'Dokumen ini sah sebagai instruksi kerja resmi bagian produksi JIDOKA.' }}
                    </div>
                </div>
            </td>
            <td width="25%" style="text-align: right; vertical-align: top;">
                <div class="qr-box" style="float: right;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(route('manufacturing.work-orders.public-validate', $workOrder->id)) }}" class="qr-image">
                    <div class="qr-text italic">VERIFIED WO</div>
                    <div class="qr-text" style="font-size: 5pt; margin-top: 3px; color: #003680;">DOKUMEN PRODUKSI SAH</div>
                </div>
            </td>
        </tr>
    </table>

    <table width="100%" class="sig-table">
        <tr>
            <td width="33%">
                Direncanakan (PPC/Admin),
                <div class="sig-box"></div>
                <div class="font-bold">( {{ str_replace('(PPC)', '', $workOrder->createdBy->name ?? '________________') }} )</div>
            </td>
            <td width="34%">
                Pelaksana (Produksi),
                <div class="sig-box"></div>
                <div class="font-bold">( ________________ )</div>
            </td>
            <td width="33%">
                Disetujui (Supt/Mgr),
                <div class="sig-box"></div>
                <div class="font-bold">( ________________ )</div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; font-size: 7.5pt; color: #aaa; text-align: center; border-top: 0.3pt solid #eee; padding-top: 5px;">
        ERP JIDOKA SYSTEM | Doc ID: {{ $workOrder->id }} | Dicetak: {{ date('d/m/Y H:i') }} | Status: {{ strtoupper($workOrder->status) }}
    </div>
</body>
</html>

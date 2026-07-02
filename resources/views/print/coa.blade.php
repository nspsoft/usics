<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COA - {{ $coa->coa_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .meta-table td {
            vertical-align: top;
            padding: 5px;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .results-table th, .results-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .results-table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 10px;
            font-weight: bold;
        }
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h1>Certificate of Analysis</h1>
        <p>This is to certify that the goods listed below have been inspected and found to conform to the specifications.</p>
    </div>

    <table class="meta-table">
        <tr>
            <td width="50%">
                <strong>Customer:</strong><br>
                {{ $coa->customer->name }}<br>
                {{ $coa->customer->address }}
            </td>
            <td width="50%" style="text-align: right;">
                <strong>date Issued:</strong> {{ $coa->issued_date->format('d M Y') }}<br>
                <strong>COA Number:</strong> {{ $coa->coa_number }}<br>
                <strong>Sales Order:</strong> {{ $coa->salesOrder->so_number }}<br>
                <strong>Your PO:</strong> {{ $coa->salesOrder->customer_po_number }}
            </td>
        </tr>
    </table>

    <table class="results-table">
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Product Code</th>
                <th>Batch / Lot No.</th>
                <th>Quantity</th>
                <th>Result</th>
            </tr>
        </thead>
        <tbody>
            @foreach($coa->salesOrder->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->product->code }}</td>
                <td>{{ $coa->batch_number }}</td> <!-- Dynamic Batch tracking linking -->
                <td>{{ $item->qty }} {{ $item->unit }}</td>
                <td><strong>PASSED</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer" style="display: block; overflow: hidden;">
        <div class="signature-box" style="float: right;">
            <div class="signature-line">
                Quality Control Manager
            </div>
            <p>PT. SPINDO</p>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>

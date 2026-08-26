<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill #{{ $bill->id }}</title>
    <style>
        {{-- DejaVu Sans (bundled with dompdf) rather than Helvetica — Helvetica
             is a base-14 PDF font with no ₹ glyph, so it silently renders "?". --}}
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .muted { color: #666; }
        .header-table, .info-table, .breakdown-table, .payments-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .header-table td { vertical-align: top; }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 11px;
            color: #fff;
        }
        .status-paid { background: #2AB930; }
        .status-partially_paid { background: #F5A623; }
        .status-overdue { background: #E0245E; }
        .status-unpaid { background: #E0245E; }
        .info-table td { padding: 4px 0; }
        .info-table td.label { color: #666; width: 40%; }
        .breakdown-table th, .breakdown-table td, .payments-table th, .payments-table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        .breakdown-table th, .payments-table th { background: #f2f2f2; }
        .amount-col { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; }
        .footer { margin-top: 24px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <h1>{{ $society->name ?? 'FlatCare' }}</h1>
                @if ($society?->address)
                    <div class="muted">{{ $society->address }}@if($society->city), {{ $society->city }}@endif</div>
                @endif
                @if ($society?->phone)
                    <div class="muted">{{ $society->phone }}</div>
                @endif
            </td>
            <td style="text-align: right;">
                <h1>{{ $bill->isPending ? 'Invoice' : 'Payment Receipt' }}</h1>
                <div class="muted">Bill #{{ $bill->id }}</div>
                <div>
                    <span class="status-badge status-{{ $bill->status }}">{{ strtoupper(str_replace('_', ' ', $bill->status)) }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td class="label">Title</td>
            <td>{{ $bill->title }}</td>
            <td class="label">Bill Date</td>
            <td>{{ $bill->created_at?->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Flat No.</td>
            <td>{{ $bill->flat?->flat_number }}</td>
            <td class="label">Due Date</td>
            <td>{{ $bill->due_date?->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Tower / Building</td>
            <td>{{ $bill->flat?->block?->name ?? '-' }}</td>
            <td class="label">Owner</td>
            <td>{{ $bill->flat?->owner_name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Mobile Number</td>
            <td>{{ $mobileNumber ?? '-' }}</td>
            <td class="label"></td>
            <td></td>
        </tr>
    </table>

    <table class="breakdown-table">
        <thead>
            <tr>
                <th>Particulars</th>
                <th class="amount-col">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($breakdown as $line)
                <tr>
                    <td>{{ $line['label'] }}</td>
                    <td class="amount-col">{{ number_format($line['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td>Total Amount</td>
                <td class="amount-col">{{ number_format((float) $bill->amount, 2) }}</td>
            </tr>
            <tr>
                <td>Paid Amount</td>
                <td class="amount-col">{{ number_format($bill->paid_amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Balance Due</td>
                <td class="amount-col">{{ number_format($bill->balance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($bill->payments->isNotEmpty())
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th class="amount-col">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bill->payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td>{{ $payment->reference_number ?? '-' }}</td>
                        <td class="amount-col">{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        This is a computer-generated document and does not require a signature.
        Generated on {{ now()->format('d M Y, h:i A') }}.
    </div>
</body>
</html>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Bill — {{ $bill->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; color: #17241d; margin: 0; background: #f2f2f2; }
        .toolbar { max-width: 800px; margin: 1rem auto 0; display: flex; justify-content: flex-end; gap: .5rem; }
        .btn { display: inline-block; padding: .4rem 1rem; border-radius: .4rem; border: 1px solid #ced4da; background: #fff; color: #17241d; text-decoration: none; font-size: .9rem; cursor: pointer; }
        .btn-brand { background: #2f6f4f; border-color: #2f6f4f; color: #fff; }

        .sheet { max-width: 800px; margin: 1.5rem auto 3rem; background: #fff; border: 1px solid #000; padding: 0; font-size: .92rem; }
        .sheet table { width: 100%; border-collapse: collapse; }
        .sheet td, .sheet th { border: 1px solid #000; padding: .5rem .6rem; vertical-align: top; }

        .letterhead { text-align: center; padding: 1rem; }
        .letterhead .name { font-size: 1.3rem; font-weight: 700; margin: 0; }
        .letterhead .address { font-size: .85rem; color: #333; margin: .25rem 0 0; }

        .meta-table td { width: 50%; }
        .meta-table .label { font-weight: 600; }

        .items-table th { text-align: left; background: #f5f5f5; }
        .items-table td.amount, .items-table th.amount { text-align: right; white-space: nowrap; }
        .items-table .detail { font-size: .8rem; color: #555; margin-top: .2rem; }

        .totals-table td.label { text-align: right; font-weight: 600; }
        .totals-table td.amount { text-align: right; width: 140px; }
        .totals-table tr.grand td { font-weight: 700; font-size: 1.05rem; }

        .section-title { text-align: center; font-weight: 700; letter-spacing: .05em; background: #f5f5f5; }
        .paid-stamp { color: #0f5132; font-weight: 700; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { border: none; margin: 0; max-width: none; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('society.payments.show', $bill->id) }}" class="btn">&laquo; Back</a>
        <button type="button" class="btn btn-brand" data-action="print">Print / Save as PDF</button>
    </div>

    <div class="sheet">
        <div class="letterhead">
            <p class="name">{{ $society->name ?? 'FlatCare' }}</p>
            <p class="address">
                {{ collect([$society->address ?? null, $society->city ?? null, $society->state ?? null])->filter()->implode(', ') }}
            </p>
        </div>

        <table class="meta-table">
            <tr>
                <td class="label">Bill Name: {{ $bill->title }}</td>
                <td class="label">Bill No.: INV-{{ str_pad($bill->id, 6, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td>
                    <span class="label">Name:</span> {{ $residentName ?? $bill->flat?->owner_name ?? '—' }}
                </td>
                <td class="label">Bill Date: {{ $bill->created_at->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td class="label">Flat No.: {{ $bill->flat?->display_label ?? '—' }}</td>
                <td class="label">Due Date: {{ $bill->due_date->format('d-m-Y') }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50px;">Sr.No.</th>
                    <th>Particulars</th>
                    <th class="amount" style="width: 140px;">Amount (INR)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lineItems as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            {{ $item['label'] }}
                            @if ($item['detail'])
                                <div class="detail">{{ $item['detail'] }}</div>
                            @endif
                        </td>
                        <td class="amount">{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td class="label">Sub-Total</td>
                <td class="amount">₹{{ number_format($bill->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Previous Dues</td>
                <td class="amount">₹{{ number_format($previousDues, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total</td>
                <td class="amount">₹{{ number_format($bill->amount + $previousDues, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Payment Made</td>
                <td class="amount">₹{{ number_format($bill->paid_amount, 2) }}</td>
            </tr>
            <tr class="grand">
                <td class="label">Balance Due</td>
                <td class="amount">₹{{ number_format($bill->balance + $previousDues, 2) }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <td><span class="label" style="font-weight:600;">Amount in Words:</span> {{ $amountInWords }}</td>
            </tr>
            @if ($bill->notes)
                <tr>
                    <td><span style="font-weight:600;">Note:</span> {{ $bill->notes }}</td>
                </tr>
            @endif
            <tr>
                <td>
                    <span style="font-weight:600;">Terms &amp; Conditions:</span>
                    <ol style="margin: .3rem 0 0; padding-left: 1.2rem;">
                        <li>Please pay the bill on or before the due date.</li>
                        <li>Contact the society office for any billing queries or corrections.</li>
                    </ol>
                </td>
            </tr>
        </table>

        @if ($bill->paid_amount > 0)
            <table>
                <tr><td class="section-title">RECEIPT</td></tr>
            </table>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Recorded By</th>
                        <th class="amount" style="width: 120px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $methodLabel = ['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'upi' => 'UPI', 'cheque' => 'Cheque', 'other' => 'Other'];
                    @endphp
                    @foreach ($bill->payments->sortBy('payment_date') as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                            <td>{{ $methodLabel[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</td>
                            <td>{{ $payment->reference_number ?: '—' }}</td>
                            <td>{{ $payment->recordedBy?->name ?? '—' }}</td>
                            <td class="amount">₹{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($bill->balance <= 0)
                <table>
                    <tr><td class="section-title paid-stamp">PAID IN FULL</td></tr>
                </table>
            @endif
        @endif
    </div>

    <script src="{{ asset('js/society-ui.js') }}"></script>
</body>
</html>

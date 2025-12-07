<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - {{ $payment->receipt_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 20px;
        }
        .school-logo {
            max-width: 80px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        .school-name {
            font-size: 28px;
            font-weight: bold;
            color: #0d6efd;
            margin: 0;
        }
        .school-address {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
            color: #333;
        }
        .receipt-number {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            width: 150px;
            font-weight: bold;
            color: #333;
        }
        .info-value {
            flex: 1;
            color: #666;
        }
        .payment-details {
            margin: 30px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
            color: #333;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .amount-cell {
            text-align: right;
            font-weight: bold;
        }
        .total-row {
            background-color: #e7f1ff;
            font-weight: bold;
            font-size: 16px;
        }
        .total-row td {
            padding: 15px 12px;
            border-top: 2px solid #0d6efd;
            border-bottom: 2px solid #0d6efd;
        }
        .amount-in-words {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }
        .amount-in-words-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .amount-in-words-value {
            color: #666;
            font-style: italic;
        }
        .payment-method {
            margin: 20px 0;
        }
        .payment-method-label {
            font-weight: bold;
            color: #333;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
        }
        .note {
            font-size: 11px;
            color: #999;
            margin-top: 20px;
            text-align: center;
        }
        .stamp-box {
            border: 2px dashed #ccc;
            width: 120px;
            height: 80px;
            text-align: center;
            padding-top: 30px;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($tenant->logo)
            <img src="{{ public_path('storage/' . $tenant->logo) }}" alt="School Logo" class="school-logo">
        @endif
        <h1 class="school-name">{{ $tenant->name ?? 'School Management System' }}</h1>
        <p class="school-address">
            {{ $tenant->address ?? 'School Address' }}<br>
            Phone: {{ $tenant->phone ?? 'N/A' }} | Email: {{ $tenant->email ?? 'N/A' }}
        </p>
    </div>

    <div class="receipt-title">FEE PAYMENT RECEIPT</div>

    <div class="receipt-number">
        Receipt No: <strong>{{ $payment->receipt_number }}</strong><br>
        Date: <strong>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</strong>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Student Name:</div>
            <div class="info-value">{{ $payment->studentFee->student->user->name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Admission No:</div>
            <div class="info-value">{{ $payment->studentFee->student->admission_number ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Class:</div>
            <div class="info-value">{{ $payment->studentFee->feeStructure->class->name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Academic Year:</div>
            <div class="info-value">{{ $payment->studentFee->academicYear->name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fee Structure:</div>
            <div class="info-value">{{ $payment->studentFee->feeStructure->name ?? 'N/A' }}</div>
        </div>
    </div>

    <div class="payment-details">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @if($payment->studentFee->feeStructure && $payment->studentFee->feeStructure->details)
                    @foreach($payment->studentFee->feeStructure->details as $detail)
                    <tr>
                        <td>{{ $detail->feeCategory->name ?? 'Fee' }}</td>
                        <td class="amount-cell">{{ number_format($detail->amount, 2) }}</td>
                    </tr>
                    @endforeach
                @endif
                <tr>
                    <td><strong>Total Fee Amount</strong></td>
                    <td class="amount-cell">{{ number_format($payment->studentFee->total_amount, 2) }}</td>
                </tr>
                @if($payment->studentFee->discount_amount > 0)
                <tr>
                    <td>Discount</td>
                    <td class="amount-cell" style="color: #dc3545;">- {{ number_format($payment->studentFee->discount_amount, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Net Amount</strong></td>
                    <td class="amount-cell">{{ number_format($payment->studentFee->net_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>Previously Paid</td>
                    <td class="amount-cell">{{ number_format($payment->studentFee->paid_amount - $payment->amount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Amount Paid Now</td>
                    <td class="amount-cell" style="color: #198754;">{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Remaining Balance</strong></td>
                    <td class="amount-cell" style="color: {{ $payment->studentFee->balance_amount > 0 ? '#dc3545' : '#198754' }};">
                        {{ number_format($payment->studentFee->balance_amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="amount-in-words">
        <div class="amount-in-words-label">Amount in Words:</div>
        <div class="amount-in-words-value">{{ ucfirst($amountInWords) }} Only</div>
    </div>

    <div class="payment-method">
        <span class="payment-method-label">Payment Method:</span> 
        {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
        
        @if($payment->payment_method === 'cheque' && $payment->cheque_number)
            (Cheque No: {{ $payment->cheque_number }}, 
            Bank: {{ $payment->cheque_bank_name }}, 
            Date: {{ \Carbon\Carbon::parse($payment->cheque_date)->format('d M Y') }})
        @endif
        
        @if($payment->payment_method === 'online' && $payment->transaction_id)
            (Transaction ID: {{ $payment->transaction_id }})
        @endif
        
        @if($payment->payment_method === 'bank_transfer' && $payment->transaction_id)
            (Reference No: {{ $payment->transaction_id }})
        @endif
    </div>

    @if($payment->notes)
    <div class="payment-method">
        <span class="payment-method-label">Notes:</span> {{ $payment->notes }}
    </div>
    @endif

    <div class="footer">
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Student/Parent Signature</div>
            </div>
            <div class="stamp-box">
                School Seal
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    {{ $payment->collectedBy->name ?? 'Authorized Signatory' }}<br>
                    <small>Collected By</small>
                </div>
            </div>
        </div>

        <p class="note">
            This is a computer-generated receipt and is valid without signature.<br>
            Please preserve this receipt for future reference. No duplicate will be issued.
        </p>
    </div>
</body>
</html>

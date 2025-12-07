<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Payment Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #dc3545;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .message {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }
        .fee-details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .fee-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .fee-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 16px;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid #dc3545;
        }
        .fee-label {
            color: #666;
        }
        .fee-value {
            font-weight: 600;
            color: #333;
        }
        .amount-due {
            color: #dc3545;
            font-size: 18px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-overdue {
            background-color: #dc3545;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Fee Payment Reminder</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Dear {{ $parentName }},
            </div>

            <p>This is a friendly reminder regarding the fee payment for your ward:</p>

            <div class="fee-details">
                <div class="fee-row">
                    <span class="fee-label">Student Name:</span>
                    <span class="fee-value">{{ $studentName }}</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">Admission Number:</span>
                    <span class="fee-value">{{ $admissionNumber }}</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">Class:</span>
                    <span class="fee-value">{{ $className }}</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">Academic Year:</span>
                    <span class="fee-value">{{ $academicYear }}</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">Fee Structure:</span>
                    <span class="fee-value">{{ $feeStructure }}</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">Status:</span>
                    <span class="status-badge status-{{ $status }}">{{ strtoupper($status) }}</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">Total Fee Amount:</span>
                    <span class="fee-value">₹{{ number_format($totalAmount, 2) }}</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">Amount Paid:</span>
                    <span class="fee-value">₹{{ number_format($paidAmount, 2) }}</span>
                </div>
                <div class="fee-row">
                    <span class="fee-label">Outstanding Balance:</span>
                    <span class="fee-value amount-due">₹{{ number_format($balanceAmount, 2) }}</span>
                </div>
            </div>

            @if($status === 'overdue')
            <div class="message">
                <strong>⚠️ URGENT:</strong> The fee payment is overdue. Please make the payment at the earliest to avoid any inconvenience.
            </div>
            @else
            <div class="message">
                <strong>Note:</strong> This is a reminder for pending fee payment. Kindly make the payment soon to avoid late fees.
            </div>
            @endif

            <p style="margin-top: 25px;">You can visit the school office during working hours or use our online payment portal to make the payment.</p>

            <center>
                <a href="{{ $schoolWebsite ?? '#' }}" class="button">Visit School Portal</a>
            </center>

            <p style="font-size: 14px; color: #666; margin-top: 25px;">
                If you have already made the payment, please ignore this reminder. For any queries, feel free to contact the school office.
            </p>

            <p style="margin-top: 25px;">
                Best regards,<br>
                <strong>{{ $schoolName }}</strong><br>
                <span style="color: #666; font-size: 14px;">
                    Phone: {{ $schoolPhone }}<br>
                    Email: {{ $schoolEmail }}
                </span>
            </p>
        </div>

        <div class="footer">
            <p style="margin: 5px 0;">This is an automated email. Please do not reply to this message.</p>
            <p style="margin: 5px 0;">© {{ date('Y') }} {{ $schoolName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

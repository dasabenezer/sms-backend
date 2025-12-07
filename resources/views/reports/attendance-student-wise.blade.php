<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student-wise Attendance Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #0d6efd;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        .info-section {
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 3px 10px 3px 0;
            width: 150px;
        }
        .info-value {
            display: table-cell;
            padding: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #0d6efd;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-center {
            text-align: center;
        }
        .text-end {
            text-align: right;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8px;
        }
        .badge-success {
            background-color: #198754;
            color: white;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        .total-row {
            font-weight: bold;
            background-color: #e9ecef !important;
            border-top: 2px solid #0d6efd;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $tenant->name ?? 'School Management System' }}</h1>
        <h2>Student-wise Attendance Report</h2>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Class:</div>
            <div class="info-value">{{ $class->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Section:</div>
            <div class="info-value">{{ $section->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Period:</div>
            <div class="info-value">{{ $from_date }} to {{ $to_date }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Generated On:</div>
            <div class="info-value">{{ $generated_at }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Roll No.</th>
                <th>Student Name</th>
                <th>Admission No.</th>
                <th class="text-center">Total Days</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Late</th>
                <th class="text-center">Half Day</th>
                <th class="text-center">On Leave</th>
                <th class="text-end">Attendance %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report as $student)
            <tr>
                <td>{{ $student['roll_number'] }}</td>
                <td>{{ $student['name'] }}</td>
                <td>{{ $student['admission_number'] }}</td>
                <td class="text-center">{{ $student['total_days'] }}</td>
                <td class="text-center">{{ $student['present'] }}</td>
                <td class="text-center">{{ $student['absent'] }}</td>
                <td class="text-center">{{ $student['late'] }}</td>
                <td class="text-center">{{ $student['half_day'] }}</td>
                <td class="text-center">{{ $student['on_leave'] }}</td>
                <td class="text-end">
                    <span class="badge 
                        @if($student['attendance_percentage'] >= 90) badge-success
                        @elseif($student['attendance_percentage'] >= 75) badge-warning
                        @else badge-danger
                        @endif">
                        {{ number_format($student['attendance_percentage'], 2) }}%
                    </span>
                </td>
            </tr>
            @endforeach
            @if(count($report) > 0)
            <tr class="total-row">
                <td colspan="3">AVERAGE</td>
                <td class="text-center">{{ number_format($totalStats['total_days'] / $totalStats['count'], 2) }}</td>
                <td class="text-center">{{ number_format($totalStats['present'] / $totalStats['count'], 2) }}</td>
                <td class="text-center">{{ number_format($totalStats['absent'] / $totalStats['count'], 2) }}</td>
                <td class="text-center">{{ number_format($totalStats['late'] / $totalStats['count'], 2) }}</td>
                <td class="text-center">{{ number_format($totalStats['half_day'] / $totalStats['count'], 2) }}</td>
                <td class="text-center">{{ number_format($totalStats['on_leave'] / $totalStats['count'], 2) }}</td>
                <td class="text-end">
                    <span class="badge 
                        @if($totalStats['avg_percentage'] >= 90) badge-success
                        @elseif($totalStats['avg_percentage'] >= 75) badge-warning
                        @else badge-danger
                        @endif">
                        {{ number_format($totalStats['avg_percentage'], 2) }}%
                    </span>
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated report and does not require a signature.</p>
        <p>Generated by School Management System</p>
    </div>
</body>
</html>

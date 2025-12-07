<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Date-wise Attendance Report</title>
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
        <h2>Date-wise Attendance Report</h2>
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
                <th>Date</th>
                <th class="text-center">Total Students</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Late</th>
                <th class="text-center">Half Day</th>
                <th class="text-center">On Leave</th>
                <th class="text-end">Attendance %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report as $day)
            <tr>
                <td>{{ \Carbon\Carbon::parse($day->attendance_date)->format('d M, Y') }}</td>
                <td class="text-center">{{ $day->total_students }}</td>
                <td class="text-center">{{ $day->present }}</td>
                <td class="text-center">{{ $day->absent }}</td>
                <td class="text-center">{{ $day->late }}</td>
                <td class="text-center">{{ $day->half_day }}</td>
                <td class="text-center">{{ $day->on_leave }}</td>
                <td class="text-end">
                    <span class="badge 
                        @if($day->attendance_percentage >= 90) badge-success
                        @elseif($day->attendance_percentage >= 75) badge-warning
                        @else badge-danger
                        @endif">
                        {{ number_format($day->attendance_percentage, 2) }}%
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">No attendance data found for the selected period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated report and does not require a signature.</p>
        <p>Generated by School Management System</p>
    </div>
</body>
</html>

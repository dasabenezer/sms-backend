<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Timetable - {{ $class->name }} {{ $section->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
        }

        .school-logo {
            max-width: 80px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 5px 0;
        }

        .school-address {
            font-size: 12px;
            color: #666;
            margin: 3px 0;
        }

        .document-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 10px;
        }

        .class-info {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }

        .class-info-grid {
            display: table;
            width: 100%;
        }

        .class-info-row {
            display: table-row;
        }

        .class-info-label {
            display: table-cell;
            font-weight: bold;
            padding: 3px 10px 3px 0;
            width: 120px;
            color: #2c3e50;
        }

        .class-info-value {
            display: table-cell;
            padding: 3px 0;
            color: #555;
        }

        .stats-container {
            margin-bottom: 15px;
            display: table;
            width: 100%;
        }

        .stat-box {
            display: table-cell;
            background-color: #f8f9fa;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            margin-right: 10px;
            border: 1px solid #dee2e6;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #3498db;
            display: block;
        }

        .stat-label {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
            display: block;
        }

        .timetable-container {
            margin-top: 15px;
        }

        .timetable-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #2c3e50;
            color: white;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #1a252f;
        }

        td {
            border: 1px solid #dee2e6;
            padding: 6px 4px;
            vertical-align: top;
            font-size: 9px;
        }

        .period-cell {
            background-color: #ecf0f1;
            font-weight: bold;
            text-align: center;
            width: 80px;
            color: #2c3e50;
        }

        .day-header {
            background-color: #34495e;
            color: white;
            font-weight: bold;
        }

        .timetable-entry {
            min-height: 35px;
            padding: 4px;
        }

        .entry-subject {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 2px;
            font-size: 10px;
        }

        .entry-teacher {
            color: #3498db;
            margin-bottom: 2px;
            font-size: 9px;
        }

        .entry-room {
            color: #7f8c8d;
            font-size: 8px;
        }

        .empty-slot {
            color: #bdc3c7;
            font-style: italic;
            text-align: center;
            padding: 10px;
        }

        .day-stats {
            margin-top: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .day-stats-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .day-stat-item {
            display: inline-block;
            margin-right: 20px;
            padding: 5px 10px;
            background-color: white;
            border-radius: 3px;
            border: 1px solid #dee2e6;
            margin-bottom: 5px;
        }

        .day-stat-day {
            font-weight: bold;
            color: #2c3e50;
            margin-right: 5px;
        }

        .day-stat-count {
            color: #3498db;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 9px;
            color: #7f8c8d;
        }

        .time-info {
            font-size: 8px;
            color: #7f8c8d;
            display: block;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($tenant->logo)
            <img src="{{ public_path('storage/' . $tenant->logo) }}" alt="School Logo" class="school-logo">
        @endif
        <div class="school-name">{{ $tenant->name }}</div>
        @if($tenant->address)
            <div class="school-address">{{ $tenant->address }}</div>
        @endif
        @if($tenant->phone)
            <div class="school-address">Phone: {{ $tenant->phone }}</div>
        @endif
        <div class="document-title">Class Timetable</div>
    </div>

    <div class="class-info">
        <div class="class-info-grid">
            <div class="class-info-row">
                <div class="class-info-label">Class:</div>
                <div class="class-info-value">{{ $class->name }}</div>
            </div>
            <div class="class-info-row">
                <div class="class-info-label">Section:</div>
                <div class="class-info-value">{{ $section->name }}</div>
            </div>
            @if($class->description)
            <div class="class-info-row">
                <div class="class-info-label">Description:</div>
                <div class="class-info-value">{{ $class->description }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-box">
            <span class="stat-value">{{ $totalClasses }}</span>
            <span class="stat-label">Total Classes</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">{{ $subjectsCount }}</span>
            <span class="stat-label">Subjects</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">{{ $teachersCount }}</span>
            <span class="stat-label">Teachers</span>
        </div>
    </div>

    <div class="timetable-container">
        <div class="timetable-title">Weekly Schedule</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Period</th>
                    @foreach($days as $day)
                        <th class="day-header">{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($periods as $period)
                    <tr>
                        <td class="period-cell">
                            <strong>{{ $period->name }}</strong>
                            <span class="time-info">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}</span>
                        </td>
                        @foreach($days as $day)
                            <td>
                                @if(isset($scheduleGrid[$day][$period->id]) && $scheduleGrid[$day][$period->id])
                                    @php
                                        $entry = $scheduleGrid[$day][$period->id];
                                    @endphp
                                    <div class="timetable-entry">
                                        <div class="entry-subject">{{ $entry->subject->name }}</div>
                                        <div class="entry-teacher">{{ $entry->teacher->name }}</div>
                                        @if($entry->room_number)
                                            <div class="entry-room">Room: {{ $entry->room_number }}</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="empty-slot">Free</div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="day-stats">
        <div class="day-stats-title">Classes per Day</div>
        @foreach($days as $day)
            <div class="day-stat-item">
                <span class="day-stat-day">{{ $day }}:</span>
                <span class="day-stat-count">{{ $classesByDay[$day] ?? 0 }} classes</span>
            </div>
        @endforeach
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        <p>This is a computer-generated document and does not require a signature.</p>
    </div>
</body>
</html>

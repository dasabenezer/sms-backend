<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Marksheet - {{ $student->user->name }}</title>
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
        .marksheet-title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
            color: #333;
        }
        .exam-info {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 30px;
        }
        .student-info {
            margin-bottom: 30px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 8px;
            border: none;
        }
        .info-label {
            width: 150px;
            font-weight: bold;
            color: #333;
        }
        .info-value {
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
            color: #333;
        }
        table td {
            padding: 10px;
            border: 1px solid #000;
            text-align: center;
        }
        .subject-name {
            text-align: left;
        }
        .grade-cell {
            font-weight: bold;
        }
        .grade-a { color: #198754; }
        .grade-b { color: #0d6efd; }
        .grade-c { color: #ffc107; }
        .grade-f { color: #dc3545; }
        .status-pass { color: #198754; font-weight: bold; }
        .status-fail { color: #dc3545; font-weight: bold; }
        .status-absent { color: #6c757d; font-weight: bold; }
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .result-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .result-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .result-label {
            font-weight: bold;
        }
        .final-result {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            border: 2px solid #000;
        }
        .result-pass {
            color: #198754;
        }
        .result-fail {
            color: #dc3545;
        }
        .signatures {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($tenant->logo)
            <img src="{{ public_path('storage/' . $tenant->logo) }}" alt="School Logo" class="school-logo">
        @endif
        <h1 class="school-name">{{ $tenant->name }}</h1>
        <p class="school-address">
            {{ $tenant->address }}<br>
            @if($tenant->phone) Phone: {{ $tenant->phone }} | @endif
            @if($tenant->email) Email: {{ $tenant->email }} @endif
        </p>
    </div>

    <h2 class="marksheet-title">EXAMINATION MARKSHEET</h2>
    <p class="exam-info">
        <strong>{{ $exam->name }}</strong><br>
        {{ $exam->academicYear->name }}
    </p>

    <div class="student-info">
        <table class="info-table">
            <tr>
                <td class="info-label">Student Name:</td>
                <td class="info-value">{{ $student->user->name }}</td>
                <td class="info-label">Roll Number:</td>
                <td class="info-value">{{ $student->roll_number }}</td>
            </tr>
            <tr>
                <td class="info-label">Admission No:</td>
                <td class="info-value">{{ $student->admission_number }}</td>
                <td class="info-label">Class & Section:</td>
                <td class="info-value">{{ $student->class->name }} - {{ $student->section->name }}</td>
            </tr>
            <tr>
                <td class="info-label">Date of Birth:</td>
                <td class="info-value">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d M, Y') : '-' }}</td>
                <td class="info-label">Date:</td>
                <td class="info-value">{{ \Carbon\Carbon::now()->format('d M, Y') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">S.No</th>
                <th>Subject</th>
                <th style="width: 100px;">Max Marks</th>
                <th style="width: 100px;">Marks Obtained</th>
                <th style="width: 80px;">Grade</th>
                <th style="width: 100px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $index => $subject)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="subject-name">{{ $subject['subject_name'] }}</td>
                <td>{{ $subject['total_marks'] }}</td>
                <td>
                    @if($subject['is_absent'])
                        <span class="status-absent">Absent</span>
                    @else
                        {{ $subject['marks_obtained'] ?? '-' }}
                    @endif
                </td>
                <td class="grade-cell 
                    @if(in_array($subject['grade'], ['A+', 'A'])) grade-a
                    @elseif(in_array($subject['grade'], ['B+', 'B'])) grade-b
                    @elseif(in_array($subject['grade'], ['C', 'D'])) grade-c
                    @elseif($subject['grade'] == 'F') grade-f
                    @endif">
                    {{ $subject['grade'] }}
                </td>
                <td>
                    @if($subject['status'] == 'Pass')
                        <span class="status-pass">{{ $subject['status'] }}</span>
                    @elseif($subject['status'] == 'Fail')
                        <span class="status-fail">{{ $subject['status'] }}</span>
                    @else
                        <span class="status-absent">{{ $subject['status'] }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">TOTAL</td>
                <td>{{ $totalMarks }}</td>
                <td>{{ $totalObtained }}</td>
                <td>{{ $overallGrade }}</td>
                <td>
                    @if($result == 'PASS')
                        <span class="status-pass">{{ $result }}</span>
                    @else
                        <span class="status-fail">{{ $result }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td colspan="3"><strong>Percentage</strong></td>
                <td colspan="3"><strong>{{ number_format($percentage, 2) }}%</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="final-result {{ $result == 'PASS' ? 'result-pass' : 'result-fail' }}">
        RESULT: {{ $result }}
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                Class Teacher
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                Principal
            </div>
        </div>
    </div>

    <div class="footer">
        <p>This is a computer-generated marksheet and does not require a signature.</p>
        <p>Generated on {{ \Carbon\Carbon::now()->format('d M, Y h:i A') }}</p>
    </div>
</body>
</html>

<?php

namespace App\Http\Controllers;

use App\Models\StudentAttendance;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AttendanceController extends Controller
{
    // ==================== MARK ATTENDANCE ====================

    public function markAttendance(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'attendance_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:present,absent,late,half_day,on_leave',
            'attendances.*.check_in_time' => 'nullable|date_format:H:i',
            'attendances.*.check_out_time' => 'nullable|date_format:H:i',
            'attendances.*.remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $markedCount = 0;
            $updatedCount = 0;

            foreach ($request->attendances as $attendance) {
                $data = [
                    'tenant_id' => $request->user()->tenant_id,
                    'student_id' => $attendance['student_id'],
                    'class_id' => $request->class_id,
                    'section_id' => $request->section_id,
                    'attendance_date' => $request->attendance_date,
                    'status' => $attendance['status'],
                    'check_in_time' => $attendance['check_in_time'] ?? null,
                    'check_out_time' => $attendance['check_out_time'] ?? null,
                    'remarks' => $attendance['remarks'] ?? null,
                    'marked_by' => $request->user()->id,
                ];

                $existingAttendance = StudentAttendance::where('student_id', $attendance['student_id'])
                    ->where('attendance_date', $request->attendance_date)
                    ->first();

                if ($existingAttendance) {
                    $existingAttendance->update($data);
                    $updatedCount++;
                } else {
                    StudentAttendance::create($data);
                    $markedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Attendance marked successfully. New: {$markedCount}, Updated: {$updatedCount}",
                'data' => [
                    'marked' => $markedCount,
                    'updated' => $updatedCount
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== GET ATTENDANCE ====================

    public function getAttendance(Request $request)
    {
        $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'student_id' => 'nullable|exists:students,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'status' => 'nullable|in:present,absent,late,half_day,on_leave',
        ]);

        $query = StudentAttendance::where('tenant_id', $request->user()->tenant_id)
            ->with(['student.user', 'class', 'section', 'markedBy']);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->student_id) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->from_date) {
            $query->whereDate('attendance_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('attendance_date', '<=', $request->to_date);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('check_in_time', 'asc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    // ==================== GET ATTENDANCE FOR DATE ====================

    public function getAttendanceForDate(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
        ]);

        // Get all students in the class/section
        $students = Student::where('tenant_id', $request->user()->tenant_id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('is_active', true)
            ->with(['user'])
            ->orderBy('roll_number')
            ->get();

        // Get existing attendance for the date
        $attendances = StudentAttendance::where('tenant_id', $request->user()->tenant_id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('attendance_date', $request->date)
            ->get()
            ->keyBy('student_id');

        // Merge students with attendance data
        $studentsWithAttendance = $students->map(function ($student) use ($attendances) {
            $attendance = $attendances->get($student->id);
            return [
                'id' => $student->id,
                'name' => $student->user->name,
                'roll_number' => $student->roll_number,
                'admission_number' => $student->admission_number,
                'attendance' => $attendance ? [
                    'id' => $attendance->id,
                    'status' => $attendance->status,
                    'check_in_time' => $attendance->check_in_time ? Carbon::parse($attendance->check_in_time)->format('H:i') : null,
                    'check_out_time' => $attendance->check_out_time ? Carbon::parse($attendance->check_out_time)->format('H:i') : null,
                    'remarks' => $attendance->remarks,
                ] : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $studentsWithAttendance
        ]);
    }

    // ==================== ATTENDANCE SUMMARY ====================

    public function getAttendanceSummary(Request $request)
    {
        $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'class_id' => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = StudentAttendance::where('tenant_id', $request->user()->tenant_id);

        if ($request->student_id) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->from_date) {
            $query->whereDate('attendance_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('attendance_date', '<=', $request->to_date);
        }

        $summary = [
            'total_days' => (clone $query)->count(),
            'present' => (clone $query)->where('status', 'present')->count(),
            'absent' => (clone $query)->where('status', 'absent')->count(),
            'late' => (clone $query)->where('status', 'late')->count(),
            'half_day' => (clone $query)->where('status', 'half_day')->count(),
            'on_leave' => (clone $query)->where('status', 'on_leave')->count(),
        ];

        $summary['attendance_percentage'] = $summary['total_days'] > 0
            ? round((($summary['present'] + $summary['late'] + $summary['half_day']) / $summary['total_days']) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    // ==================== STUDENT-WISE REPORT ====================

    public function getStudentWiseReport(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $students = Student::where('tenant_id', $request->user()->tenant_id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('is_active', true)
            ->with(['user'])
            ->orderBy('roll_number')
            ->get();

        $report = $students->map(function ($student) use ($request) {
            $attendances = StudentAttendance::where('student_id', $student->id)
                ->whereBetween('attendance_date', [$request->from_date, $request->to_date])
                ->get();

            $totalDays = $attendances->count();
            $present = $attendances->where('status', 'present')->count();
            $absent = $attendances->where('status', 'absent')->count();
            $late = $attendances->where('status', 'late')->count();
            $halfDay = $attendances->where('status', 'half_day')->count();
            $onLeave = $attendances->where('status', 'on_leave')->count();

            $attendancePercentage = $totalDays > 0
                ? round((($present + $late + $halfDay) / $totalDays) * 100, 2)
                : 0;

            return [
                'student_id' => $student->id,
                'name' => $student->user->name,
                'roll_number' => $student->roll_number,
                'admission_number' => $student->admission_number,
                'total_days' => $totalDays,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'half_day' => $halfDay,
                'on_leave' => $onLeave,
                'attendance_percentage' => $attendancePercentage,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    // ==================== DATE-WISE REPORT ====================

    public function getDateWiseReport(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $attendances = StudentAttendance::where('tenant_id', $request->user()->tenant_id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->whereBetween('attendance_date', [$request->from_date, $request->to_date])
            ->selectRaw('
                attendance_date,
                COUNT(*) as total_students,
                SUM(CASE WHEN status = \'present\' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = \'absent\' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = \'late\' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = \'half_day\' THEN 1 ELSE 0 END) as half_day,
                SUM(CASE WHEN status = \'on_leave\' THEN 1 ELSE 0 END) as on_leave
            ')
            ->groupBy('attendance_date')
            ->orderBy('attendance_date', 'desc')
            ->get();

        $report = $attendances->map(function ($attendance) {
            $attendancePercentage = $attendance->total_students > 0
                ? round((($attendance->present + $attendance->late + $attendance->half_day) / $attendance->total_students) * 100, 2)
                : 0;

            return [
                'date' => $attendance->attendance_date,
                'total_students' => $attendance->total_students,
                'present' => $attendance->present,
                'absent' => $attendance->absent,
                'late' => $attendance->late,
                'half_day' => $attendance->half_day,
                'on_leave' => $attendance->on_leave,
                'attendance_percentage' => $attendancePercentage,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    // ==================== DASHBOARD STATS ====================

    public function getDashboardStats(Request $request)
    {
        $today = Carbon::today();
        
        // Today's attendance
        $todayAttendance = StudentAttendance::where('tenant_id', $request->user()->tenant_id)
            ->whereDate('attendance_date', $today)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late
            ')
            ->first();

        // This month's stats
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        
        $monthlyAttendance = StudentAttendance::where('tenant_id', $request->user()->tenant_id)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->selectRaw('
                COUNT(DISTINCT student_id) as unique_students,
                COUNT(*) as total_records,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent
            ')
            ->first();

        $monthlyPercentage = $monthlyAttendance->total_records > 0
            ? round(($monthlyAttendance->present / $monthlyAttendance->total_records) * 100, 2)
            : 0;

        // Classes marked today
        $classesMarkedToday = StudentAttendance::where('tenant_id', $request->user()->tenant_id)
            ->whereDate('attendance_date', $today)
            ->distinct('class_id', 'section_id')
            ->count(DB::raw('DISTINCT CONCAT(class_id, "-", section_id)'));

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'total' => $todayAttendance->total ?? 0,
                    'present' => $todayAttendance->present ?? 0,
                    'absent' => $todayAttendance->absent ?? 0,
                    'late' => $todayAttendance->late ?? 0,
                    'percentage' => $todayAttendance->total > 0 
                        ? round(($todayAttendance->present / $todayAttendance->total) * 100, 2)
                        : 0
                ],
                'monthly' => [
                    'unique_students' => $monthlyAttendance->unique_students ?? 0,
                    'total_records' => $monthlyAttendance->total_records ?? 0,
                    'present' => $monthlyAttendance->present ?? 0,
                    'absent' => $monthlyAttendance->absent ?? 0,
                    'percentage' => $monthlyPercentage
                ],
                'classes_marked_today' => $classesMarkedToday
            ]
        ]);
    }

    // ==================== FILTERS ====================

    public function getClasses(Request $request)
    {
        $classes = ClassModel::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->with(['sections' => function($query) {
                $query->where('is_active', true)
                    ->orderBy('name');
            }])
            ->has('sections') // Only get classes that have sections
            ->orderBy('order')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    // ==================== PDF EXPORTS ====================

    public function exportStudentWisePDF(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $class = ClassModel::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $request->class_id)
            ->first();
        
        $section = Section::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $request->section_id)
            ->first();

        // Get all students in the class/section
        $students = Student::where('tenant_id', $request->user()->tenant_id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('is_active', true)
            ->with(['user'])
            ->orderBy('roll_number')
            ->get();

        $reportData = [];
        $totalStats = [
            'total_days' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'half_day' => 0,
            'on_leave' => 0,
            'count' => 0
        ];

        foreach ($students as $student) {
            $attendances = StudentAttendance::where('tenant_id', $request->user()->tenant_id)
                ->where('student_id', $student->id)
                ->whereBetween('attendance_date', [$request->from_date, $request->to_date])
                ->get();

            $stats = [
                'total_days' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'half_day' => $attendances->where('status', 'half_day')->count(),
                'on_leave' => $attendances->where('status', 'on_leave')->count(),
            ];

            $stats['attendance_percentage'] = $stats['total_days'] > 0 
                ? round((($stats['present'] + $stats['late'] + $stats['half_day']) / $stats['total_days']) * 100, 2)
                : 0;

            $reportData[] = [
                'student_id' => $student->id,
                'roll_number' => $student->roll_number,
                'name' => $student->user->name,
                'admission_number' => $student->admission_number,
                ...$stats
            ];

            // Add to totals
            $totalStats['total_days'] += $stats['total_days'];
            $totalStats['present'] += $stats['present'];
            $totalStats['absent'] += $stats['absent'];
            $totalStats['late'] += $stats['late'];
            $totalStats['half_day'] += $stats['half_day'];
            $totalStats['on_leave'] += $stats['on_leave'];
            $totalStats['count']++;
        }

        // Calculate averages
        if ($totalStats['count'] > 0) {
            $totalStats['avg_percentage'] = $totalStats['total_days'] > 0 
                ? round((($totalStats['present'] + $totalStats['late'] + $totalStats['half_day']) / $totalStats['total_days']) * 100, 2)
                : 0;
        }

        $pdf = Pdf::loadView('reports.attendance-student-wise', [
            'report' => $reportData,
            'class' => $class,
            'section' => $section,
            'from_date' => Carbon::parse($request->from_date)->format('d M, Y'),
            'to_date' => Carbon::parse($request->to_date)->format('d M, Y'),
            'generated_at' => Carbon::now()->format('d M, Y H:i'),
            'tenant' => $request->user()->tenant,
            'totalStats' => $totalStats
        ])->setPaper('a4', 'landscape');

        $filename = 'attendance-student-wise-' . str_replace(' ', '-', $class->name) . '-' . $section->name . '-' . date('Y-m-d') . '.pdf';
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportDateWisePDF(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $class = ClassModel::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $request->class_id)
            ->first();
        
        $section = Section::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $request->section_id)
            ->first();

        $report = StudentAttendance::where('tenant_id', $request->user()->tenant_id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->whereBetween('attendance_date', [$request->from_date, $request->to_date])
            ->select(
                'attendance_date',
                DB::raw('COUNT(*) as total_students'),
                DB::raw('SUM(CASE WHEN status = \'present\' THEN 1 ELSE 0 END) as present'),
                DB::raw('SUM(CASE WHEN status = \'absent\' THEN 1 ELSE 0 END) as absent'),
                DB::raw('SUM(CASE WHEN status = \'late\' THEN 1 ELSE 0 END) as late'),
                DB::raw('SUM(CASE WHEN status = \'half_day\' THEN 1 ELSE 0 END) as half_day'),
                DB::raw('SUM(CASE WHEN status = \'on_leave\' THEN 1 ELSE 0 END) as on_leave'),
                DB::raw('ROUND((SUM(CASE WHEN status IN (\'present\', \'late\', \'half_day\') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage')
            )
            ->groupBy('attendance_date')
            ->orderBy('attendance_date', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.attendance-date-wise', [
            'report' => $report,
            'class' => $class,
            'section' => $section,
            'from_date' => Carbon::parse($request->from_date)->format('d M, Y'),
            'to_date' => Carbon::parse($request->to_date)->format('d M, Y'),
            'generated_at' => Carbon::now()->format('d M, Y H:i'),
            'tenant' => $request->user()->tenant
        ])->setPaper('a4', 'landscape');

        $filename = 'attendance-date-wise-' . str_replace(' ', '-', $class->name) . '-' . $section->name . '-' . date('Y-m-d') . '.pdf';
        
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}



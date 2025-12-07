<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamMark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExamController extends Controller
{
    // ==================== EXAM MANAGEMENT ====================

    public function index(Request $request)
    {
        $query = Exam::where('tenant_id', $request->user()->tenant_id)
            ->with(['academicYear', 'examType']);

        if ($request->academic_year_id) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->exam_type_id) {
            $query->where('exam_type_id', $request->exam_type_id);
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        $exams = $query->orderBy('start_date', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $exams
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'exam_type_id' => 'required|exists:exam_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'instructions' => 'nullable|string'
        ]);

        $exam = Exam::create([
            'tenant_id' => $request->user()->tenant_id,
            'academic_year_id' => $request->academic_year_id,
            'exam_type_id' => $request->exam_type_id,
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'instructions' => $request->instructions,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exam created successfully',
            'data' => $exam->load(['academicYear', 'examType'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:255',
            'exam_type_id' => 'required|exists:exam_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'instructions' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $exam->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Exam updated successfully',
            'data' => $exam->load(['academicYear', 'examType'])
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $exam = Exam::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $exam->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam deleted successfully'
        ]);
    }

    // ==================== EXAM SCHEDULE ====================

    public function getSchedules(Request $request, $examId)
    {
        $exam = Exam::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($examId);

        $query = ExamSchedule::where('tenant_id', $request->user()->tenant_id)
            ->where('exam_id', $examId)
            ->with(['class', 'section', 'subject']);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        $schedules = $query->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    public function storeSchedule(Request $request, $examId)
    {
        $exam = Exam::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($examId);

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_marks' => 'required|integer|min:1',
            'min_passing_marks' => 'required|integer|min:1|lte:max_marks',
            'room_number' => 'nullable|string|max:50'
        ]);

        // Calculate duration in minutes
        $startTime = \Carbon\Carbon::createFromFormat('H:i', $request->start_time);
        $endTime = \Carbon\Carbon::createFromFormat('H:i', $request->end_time);
        $durationMinutes = $endTime->diffInMinutes($startTime);

        $schedule = ExamSchedule::create([
            'tenant_id' => $request->user()->tenant_id,
            'exam_id' => $examId,
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'subject_id' => $request->subject_id,
            'exam_date' => $request->exam_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_minutes' => $durationMinutes,
            'max_marks' => $request->max_marks ?? 100,
            'min_passing_marks' => $request->min_passing_marks ?? 33,
            'room_number' => $request->room_number
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exam schedule created successfully',
            'data' => $schedule->load(['class', 'section', 'subject'])
        ], 201);
    }

    public function updateSchedule(Request $request, $examId, $scheduleId)
    {
        $schedule = ExamSchedule::where('tenant_id', $request->user()->tenant_id)
            ->where('exam_id', $examId)
            ->findOrFail($scheduleId);

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_marks' => 'required|integer|min:1',
            'min_passing_marks' => 'required|integer|min:1|lte:max_marks',
            'room_number' => 'nullable|string|max:50'
        ]);

        // Calculate duration in minutes
        $startTime = \Carbon\Carbon::createFromFormat('H:i', $request->start_time);
        $endTime = \Carbon\Carbon::createFromFormat('H:i', $request->end_time);
        $durationMinutes = $endTime->diffInMinutes($startTime);

        $schedule->update([
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'subject_id' => $request->subject_id,
            'exam_date' => $request->exam_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_minutes' => $durationMinutes,
            'max_marks' => $request->max_marks,
            'min_passing_marks' => $request->min_passing_marks,
            'room_number' => $request->room_number
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exam schedule updated successfully',
            'data' => $schedule->load(['class', 'section', 'subject'])
        ]);
    }

    public function deleteSchedule(Request $request, $examId, $scheduleId)
    {
        $schedule = ExamSchedule::where('tenant_id', $request->user()->tenant_id)
            ->where('exam_id', $examId)
            ->findOrFail($scheduleId);

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam schedule deleted successfully'
        ]);
    }

    // ==================== MARKS ENTRY ====================

    public function getMarksEntry(Request $request, $scheduleId)
    {
        $schedule = ExamSchedule::where('tenant_id', $request->user()->tenant_id)
            ->with(['exam', 'class', 'section', 'subject'])
            ->findOrFail($scheduleId);

        // Get all students in the class/section
        $students = Student::where('tenant_id', $request->user()->tenant_id)
            ->where('class_id', $schedule->class_id)
            ->where('section_id', $schedule->section_id)
            ->where('is_active', true)
            ->with(['user'])
            ->orderBy('roll_number')
            ->get();

        // Get existing marks
        $marks = ExamMark::where('tenant_id', $request->user()->tenant_id)
            ->where('exam_schedule_id', $scheduleId)
            ->get()
            ->keyBy('student_id');

        // Merge students with marks
        $studentsWithMarks = $students->map(function ($student) use ($marks, $schedule) {
            $mark = $marks->get($student->id);
            return [
                'id' => $student->id,
                'name' => $student->user->name,
                'roll_number' => $student->roll_number,
                'admission_number' => $student->admission_number,
                'marks_obtained' => $mark ? $mark->total_marks : null,
                'is_absent' => $mark ? $mark->is_absent : false,
                'remarks' => $mark ? $mark->remarks : null,
                'mark_id' => $mark ? $mark->id : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'schedule' => $schedule,
                'students' => $studentsWithMarks
            ]
        ]);
    }

    public function storeMarks(Request $request, $scheduleId)
    {
        $schedule = ExamSchedule::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($scheduleId);

        $request->validate([
            'marks' => 'required|array',
            'marks.*.student_id' => 'required|exists:students,id',
            'marks.*.marks_obtained' => 'nullable|numeric|min:0|max:' . $schedule->max_marks,
            'marks.*.is_absent' => 'boolean',
            'marks.*.remarks' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->marks as $markData) {
                $marksObtained = $markData['is_absent'] ? null : $markData['marks_obtained'];
                
                ExamMark::updateOrCreate(
                    [
                        'tenant_id' => $request->user()->tenant_id,
                        'exam_schedule_id' => $scheduleId,
                        'student_id' => $markData['student_id']
                    ],
                    [
                        'theory_marks' => $marksObtained,
                        'practical_marks' => null,
                        'total_marks' => $marksObtained ?? 0,
                        'grade' => null,
                        'is_absent' => $markData['is_absent'] ?? false,
                        'remarks' => $markData['remarks'] ?? null,
                        'entered_by' => $request->user()->id,
                        'entered_at' => now()
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Marks saved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save marks: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== MARKSHEET ====================

    public function getMarksheet(Request $request, $examId, $studentId)
    {
        $exam = Exam::where('tenant_id', $request->user()->tenant_id)
            ->with(['academicYear'])
            ->findOrFail($examId);

        $student = Student::where('tenant_id', $request->user()->tenant_id)
            ->with(['user', 'class', 'section'])
            ->findOrFail($studentId);

        // Get all exam schedules for the student's class and section
        $schedules = ExamSchedule::where('tenant_id', $request->user()->tenant_id)
            ->where('exam_id', $examId)
            ->where('class_id', $student->class_id)
            ->where('section_id', $student->section_id)
            ->with(['subject'])
            ->orderBy('exam_date')
            ->get();

        // Get marks for all schedules - use exam_schedule_id instead of exam_id
        $scheduleIds = $schedules->pluck('id')->toArray();
        $marks = ExamMark::where('tenant_id', $request->user()->tenant_id)
            ->whereIn('exam_schedule_id', $scheduleIds)
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('exam_schedule_id');

        // Calculate results
        $totalMarks = 0;
        $totalObtained = 0;
        $subjects = [];
        $allPassed = true;

        foreach ($schedules as $schedule) {
            $mark = $marks->get($schedule->id);
            $totalMarks += $schedule->max_marks;

            $subjectData = [
                'subject_name' => $schedule->subject->name,
                'total_marks' => $schedule->max_marks,
                'passing_marks' => $schedule->min_passing_marks,
                'marks_obtained' => null,
                'grade' => null,
                'status' => 'pending',
                'is_absent' => false
            ];

            if ($mark) {
                if ($mark->is_absent) {
                    $subjectData['is_absent'] = true;
                    $subjectData['grade'] = 'AB';
                    $subjectData['status'] = 'absent';
                    $allPassed = false;
                } else {
                    $subjectData['marks_obtained'] = $mark->total_marks;
                    $totalObtained += $mark->total_marks;
                    
                    // Calculate grade
                    $percentage = ($mark->total_marks / $schedule->max_marks) * 100;
                    if ($percentage >= 90) $subjectData['grade'] = 'A+';
                    elseif ($percentage >= 80) $subjectData['grade'] = 'A';
                    elseif ($percentage >= 70) $subjectData['grade'] = 'B+';
                    elseif ($percentage >= 60) $subjectData['grade'] = 'B';
                    elseif ($percentage >= 50) $subjectData['grade'] = 'C';
                    elseif ($percentage >= 40) $subjectData['grade'] = 'D';
                    else $subjectData['grade'] = 'F';

                    $subjectData['status'] = $mark->total_marks >= $schedule->min_passing_marks ? 'pass' : 'fail';
                    
                    if ($subjectData['status'] === 'fail') {
                        $allPassed = false;
                    }
                }
            }

            $subjects[] = $subjectData;
        }

        $percentage = $totalMarks > 0 ? round(($totalObtained / $totalMarks) * 100, 2) : 0;
        
        // Overall grade
        if ($percentage >= 90) $overallGrade = 'A+';
        elseif ($percentage >= 80) $overallGrade = 'A';
        elseif ($percentage >= 70) $overallGrade = 'B+';
        elseif ($percentage >= 60) $overallGrade = 'B';
        elseif ($percentage >= 50) $overallGrade = 'C';
        elseif ($percentage >= 40) $overallGrade = 'D';
        else $overallGrade = 'F';

        return response()->json([
            'success' => true,
            'data' => [
                'exam' => $exam,
                'student' => $student,
                'subjects' => $subjects,
                'total_marks' => $totalMarks,
                'total_obtained' => $totalObtained,
                'percentage' => $percentage,
                'overall_grade' => $overallGrade,
                'result' => $allPassed ? 'PASS' : 'FAIL'
            ]
        ]);
    }

    // ==================== FILTERS ====================

    public function getFilters(Request $request)
    {
        $academicYears = \App\Models\AcademicYear::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name']);

        $examTypes = \App\Models\ExamType::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $classes = \App\Models\ClassModel::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->with(['sections' => function($query) {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->has('sections')
            ->orderBy('order')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'academic_years' => $academicYears,
                'exam_types' => $examTypes,
                'classes' => $classes
            ]
        ]);
    }

    public function getSubjects(Request $request)
    {
        if ($request->class_id) {
            // Get subjects assigned to the class
            $subjects = \DB::table('class_subjects')
                ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.id')
                ->where('class_subjects.tenant_id', $request->user()->tenant_id)
                ->where('class_subjects.class_id', $request->class_id)
                ->select('subjects.id', 'subjects.name', 'subjects.code')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        } else {
            // Get all subjects
            $subjects = Subject::where('tenant_id', $request->user()->tenant_id)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        }

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    // ==================== PDF MARKSHEET ====================

    public function downloadMarksheet(Request $request, $examId, $studentId)
    {
        $exam = Exam::where('tenant_id', $request->user()->tenant_id)
            ->with(['academicYear'])
            ->findOrFail($examId);

        $student = Student::where('tenant_id', $request->user()->tenant_id)
            ->with(['user', 'class', 'section'])
            ->findOrFail($studentId);

        // Get all exam schedules
        $schedules = ExamSchedule::where('tenant_id', $request->user()->tenant_id)
            ->where('exam_id', $examId)
            ->where('class_id', $student->class_id)
            ->where('section_id', $student->section_id)
            ->with(['subject'])
            ->orderBy('exam_date')
            ->get();

        // Get marks
        $scheduleIds = $schedules->pluck('id')->toArray();
        $marks = ExamMark::where('tenant_id', $request->user()->tenant_id)
            ->whereIn('exam_schedule_id', $scheduleIds)
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('exam_schedule_id');

        // Calculate results
        $totalMarks = 0;
        $totalObtained = 0;
        $subjects = [];
        $allPassed = true;

        foreach ($schedules as $schedule) {
            $mark = $marks->get($schedule->id);
            $totalMarks += $schedule->max_marks;

            $subjectData = [
                'subject_name' => $schedule->subject->name,
                'total_marks' => $schedule->max_marks,
                'passing_marks' => $schedule->min_passing_marks,
                'marks_obtained' => null,
                'grade' => null,
                'status' => 'Pending',
                'is_absent' => false
            ];

            if ($mark) {
                if ($mark->is_absent) {
                    $subjectData['is_absent'] = true;
                    $subjectData['grade'] = 'AB';
                    $subjectData['status'] = 'Absent';
                    $allPassed = false;
                } else {
                    $subjectData['marks_obtained'] = $mark->total_marks;
                    $totalObtained += $mark->total_marks;
                    
                    $percentage = ($mark->total_marks / $schedule->max_marks) * 100;
                    if ($percentage >= 90) $subjectData['grade'] = 'A+';
                    elseif ($percentage >= 80) $subjectData['grade'] = 'A';
                    elseif ($percentage >= 70) $subjectData['grade'] = 'B+';
                    elseif ($percentage >= 60) $subjectData['grade'] = 'B';
                    elseif ($percentage >= 50) $subjectData['grade'] = 'C';
                    elseif ($percentage >= 40) $subjectData['grade'] = 'D';
                    else $subjectData['grade'] = 'F';

                    $subjectData['status'] = $mark->total_marks >= $schedule->min_passing_marks ? 'Pass' : 'Fail';
                    
                    if ($subjectData['status'] === 'Fail') {
                        $allPassed = false;
                    }
                }
            }

            $subjects[] = $subjectData;
        }

        $percentage = $totalMarks > 0 ? round(($totalObtained / $totalMarks) * 100, 2) : 0;
        
        if ($percentage >= 90) $overallGrade = 'A+';
        elseif ($percentage >= 80) $overallGrade = 'A';
        elseif ($percentage >= 70) $overallGrade = 'B+';
        elseif ($percentage >= 60) $overallGrade = 'B';
        elseif ($percentage >= 50) $overallGrade = 'C';
        elseif ($percentage >= 40) $overallGrade = 'D';
        else $overallGrade = 'F';

        $tenant = $request->user()->tenant;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('marksheets.student', [
            'exam' => $exam,
            'student' => $student,
            'subjects' => $subjects,
            'totalMarks' => $totalMarks,
            'totalObtained' => $totalObtained,
            'percentage' => $percentage,
            'overallGrade' => $overallGrade,
            'result' => $allPassed ? 'PASS' : 'FAIL',
            'tenant' => $tenant
        ]);

        return $pdf->download('marksheet-' . $student->user->name . '-' . $exam->name . '.pdf');
    }
}

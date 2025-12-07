<?php

namespace App\Http\Controllers;

use App\Models\Period;
use App\Models\Timetable;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    // ==================== PERIODS ====================

    public function getPeriods(Request $request)
    {
        $periods = Period::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('order_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $periods
        ]);
    }

    public function storePeriod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'order_number' => 'required|integer|min:1',
            'is_break' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $period = Period::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'order_number' => $request->order_number,
            'is_break' => $request->is_break ?? false,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Period created successfully',
            'data' => $period
        ]);
    }

    public function updatePeriod(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'order_number' => 'required|integer|min:1',
            'is_break' => 'boolean',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $period = Period::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $period->update($request->only([
            'name',
            'start_time',
            'end_time',
            'order_number',
            'is_break',
            'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Period updated successfully',
            'data' => $period
        ]);
    }

    public function deletePeriod(Request $request, $id)
    {
        $period = Period::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        // Check if period is used in timetable
        $usedInTimetable = Timetable::where('period_id', $id)->exists();
        if ($usedInTimetable) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete period that is used in timetable'
            ], 422);
        }

        $period->delete();

        return response()->json([
            'success' => true,
            'message' => 'Period deleted successfully'
        ]);
    }

    // ==================== TIMETABLE ====================

    public function getTimetable(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $query = Timetable::where('tenant_id', $tenantId)
            ->with(['class', 'section', 'period', 'subject', 'teacher', 'academicYear']);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section_id) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->teacher_id) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->day_of_week) {
            $query->where('day_of_week', $request->day_of_week);
        }

        // Get active academic year if not specified
        if ($request->academic_year_id) {
            $query->where('academic_year_id', $request->academic_year_id);
        } else {
            $activeYear = AcademicYear::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();
            if ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            }
        }

        $timetable = $query->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $timetable
        ]);
    }

    public function storeTimetable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'period_id' => 'required|exists:periods,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'room_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $tenantId = $request->user()->tenant_id;

        // Get active academic year
        $activeYear = AcademicYear::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$activeYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic year found'
            ], 422);
        }

        // Check for conflicts
        $conflict = Timetable::where('tenant_id', $tenantId)
            ->where('academic_year_id', $activeYear->id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('period_id', $request->period_id)
            ->where('day_of_week', $request->day_of_week)
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'A class is already scheduled for this period'
            ], 422);
        }

        // Check teacher availability
        if ($request->teacher_id) {
            $teacherConflict = Timetable::where('tenant_id', $tenantId)
                ->where('academic_year_id', $activeYear->id)
                ->where('teacher_id', $request->teacher_id)
                ->where('period_id', $request->period_id)
                ->where('day_of_week', $request->day_of_week)
                ->exists();

            if ($teacherConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher is already assigned to another class in this period'
                ], 422);
            }
        }

        $timetable = Timetable::create([
            'tenant_id' => $tenantId,
            'academic_year_id' => $activeYear->id,
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'period_id' => $request->period_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'day_of_week' => $request->day_of_week,
            'room_number' => $request->room_number,
            'notes' => $request->notes
        ]);

        $timetable->load(['class', 'section', 'period', 'subject', 'teacher']);

        return response()->json([
            'success' => true,
            'message' => 'Timetable entry created successfully',
            'data' => $timetable
        ]);
    }

    public function updateTimetable(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'period_id' => 'required|exists:periods,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'room_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $timetable = Timetable::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        // Check for conflicts (excluding current entry)
        $conflict = Timetable::where('tenant_id', $request->user()->tenant_id)
            ->where('id', '!=', $id)
            ->where('academic_year_id', $timetable->academic_year_id)
            ->where('class_id', $timetable->class_id)
            ->where('section_id', $timetable->section_id)
            ->where('period_id', $request->period_id)
            ->where('day_of_week', $request->day_of_week)
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'A class is already scheduled for this period'
            ], 422);
        }

        // Check teacher availability
        if ($request->teacher_id) {
            $teacherConflict = Timetable::where('tenant_id', $request->user()->tenant_id)
                ->where('id', '!=', $id)
                ->where('academic_year_id', $timetable->academic_year_id)
                ->where('teacher_id', $request->teacher_id)
                ->where('period_id', $request->period_id)
                ->where('day_of_week', $request->day_of_week)
                ->exists();

            if ($teacherConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher is already assigned to another class in this period'
                ], 422);
            }
        }

        $timetable->update($request->only([
            'period_id',
            'subject_id',
            'teacher_id',
            'day_of_week',
            'room_number',
            'notes'
        ]));

        $timetable->load(['class', 'section', 'period', 'subject', 'teacher']);

        return response()->json([
            'success' => true,
            'message' => 'Timetable entry updated successfully',
            'data' => $timetable
        ]);
    }

    public function deleteTimetable(Request $request, $id)
    {
        $timetable = Timetable::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $timetable->delete();

        return response()->json([
            'success' => true,
            'message' => 'Timetable entry deleted successfully'
        ]);
    }

    // ==================== FILTERS ====================

    public function getFilters(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        // Get classes that have at least one section
        $classes = ClassModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['sections' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->has('sections') // Only get classes that have sections
            ->orderBy('order')
            ->get();

        $teachers = User::where('tenant_id', $tenantId)
            ->where('role', 'teacher')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $subjects = Subject::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data' => [
                'classes' => $classes,
                'teachers' => $teachers,
                'subjects' => $subjects
            ]
        ]);
    }

    public function downloadTeacherTimetable(Request $request, $teacherId)
    {
        $tenantId = $request->user()->tenant_id;
        $tenant = $request->user()->tenant;

        // Get teacher details
        $teacher = User::where('tenant_id', $tenantId)
            ->where('id', $teacherId)
            ->where('role', 'teacher')
            ->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }

        // Get active periods (excluding breaks)
        $periods = Period::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_break', false)
            ->orderBy('order_number')
            ->get();

        // Get timetable for this teacher
        $timetable = Timetable::where('tenant_id', $tenantId)
            ->where('teacher_id', $teacherId)
            ->with(['class', 'section', 'subject', 'period'])
            ->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Organize timetable by day and period
        $scheduleGrid = [];
        foreach ($days as $day) {
            $scheduleGrid[$day] = [];
            foreach ($periods as $period) {
                $entry = $timetable->first(function ($item) use ($day, $period) {
                    return $item->day_of_week === $day && $item->period_id === $period->id;
                });
                $scheduleGrid[$day][$period->id] = $entry;
            }
        }

        // Calculate statistics
        $totalClasses = $timetable->count();
        $subjectsCount = $timetable->pluck('subject_id')->unique()->count();
        $classesCount = $timetable->map(function ($item) {
            return $item->class_id . '-' . $item->section_id;
        })->unique()->count();

        $classesByDay = [];
        foreach ($days as $day) {
            $classesByDay[$day] = $timetable->where('day_of_week', $day)->count();
        }

        $pdf = \PDF::loadView('timetables.teacher', [
            'teacher' => $teacher,
            'periods' => $periods,
            'days' => $days,
            'scheduleGrid' => $scheduleGrid,
            'tenant' => $tenant,
            'totalClasses' => $totalClasses,
            'subjectsCount' => $subjectsCount,
            'classesCount' => $classesCount,
            'classesByDay' => $classesByDay
        ])->setPaper('a4', 'landscape');

        return $pdf->download('timetable-' . \Str::slug($teacher->name) . '.pdf');
    }

    public function downloadClassTimetable(Request $request, $classId, $sectionId)
    {
        $tenantId = $request->user()->tenant_id;
        $tenant = $request->user()->tenant;

        // Get class and section details
        $class = ClassModel::where('tenant_id', $tenantId)
            ->where('id', $classId)
            ->with('sections')
            ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found'
            ], 404);
        }

        $section = $class->sections->firstWhere('id', $sectionId);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        // Get active periods (excluding breaks)
        $periods = Period::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_break', false)
            ->orderBy('order_number')
            ->get();

        // Get timetable for this class and section
        $timetable = Timetable::where('tenant_id', $tenantId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->with(['subject', 'teacher', 'period'])
            ->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Organize timetable by day and period
        $scheduleGrid = [];
        foreach ($days as $day) {
            $scheduleGrid[$day] = [];
            foreach ($periods as $period) {
                $entry = $timetable->first(function ($item) use ($day, $period) {
                    return $item->day_of_week === $day && $item->period_id === $period->id;
                });
                $scheduleGrid[$day][$period->id] = $entry;
            }
        }

        // Calculate statistics
        $totalClasses = $timetable->count();
        $subjectsCount = $timetable->pluck('subject_id')->unique()->count();
        $teachersCount = $timetable->pluck('teacher_id')->unique()->count();

        $classesByDay = [];
        foreach ($days as $day) {
            $classesByDay[$day] = $timetable->where('day_of_week', $day)->count();
        }

        $pdf = \PDF::loadView('timetables.class', [
            'class' => $class,
            'section' => $section,
            'periods' => $periods,
            'days' => $days,
            'scheduleGrid' => $scheduleGrid,
            'tenant' => $tenant,
            'totalClasses' => $totalClasses,
            'subjectsCount' => $subjectsCount,
            'teachersCount' => $teachersCount,
            'classesByDay' => $classesByDay
        ])->setPaper('a4', 'landscape');

        return $pdf->download('timetable-' . \Str::slug($class->name . '-' . $section->name) . '.pdf');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Section;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $tenantId = $request->user()->tenant_id;

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'students' => [],
                    'teachers' => [],
                    'classes' => [],
                    'subjects' => []
                ]
            ]);
        }

        // Search students
        $students = Student::where('tenant_id', $tenantId)
            ->where(function($q) use ($query) {
                $q->where('first_name', 'ILIKE', "%{$query}%")
                  ->orWhere('last_name', 'ILIKE', "%{$query}%")
                  ->orWhere('admission_number', 'ILIKE', "%{$query}%")
                  ->orWhere('email', 'ILIKE', "%{$query}%");
            })
            ->with(['class', 'section'])
            ->limit(5)
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'type' => 'student',
                    'title' => $student->first_name . ' ' . $student->last_name,
                    'subtitle' => $student->class->name . ' - ' . $student->section->name,
                    'meta' => $student->admission_number,
                    'url' => '/students/' . $student->id
                ];
            });

        // Search teachers
        $teachers = User::where('tenant_id', $tenantId)
            ->where('role', 'teacher')
            ->where(function($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('email', 'ILIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function($teacher) {
                return [
                    'id' => $teacher->id,
                    'type' => 'teacher',
                    'title' => $teacher->name,
                    'subtitle' => $teacher->specialization ?? 'Teacher',
                    'meta' => $teacher->email,
                    'url' => '/timetable/teachers'
                ];
            });

        // Search classes
        $classes = ClassModel::where('tenant_id', $tenantId)
            ->where('name', 'ILIKE', "%{$query}%")
            ->with('sections')
            ->limit(5)
            ->get()
            ->map(function($class) {
                return [
                    'id' => $class->id,
                    'type' => 'class',
                    'title' => $class->name,
                    'subtitle' => $class->sections->count() . ' sections',
                    'meta' => '',
                    'url' => '/students?class=' . $class->id
                ];
            });

        // Search subjects
        $subjects = Subject::where('tenant_id', $tenantId)
            ->where(function($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                  ->orWhere('code', 'ILIKE', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'type' => 'subject',
                    'title' => $subject->name,
                    'subtitle' => 'Subject',
                    'meta' => $subject->code,
                    'url' => '/subjects'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'students' => $students,
                'teachers' => $teachers,
                'classes' => $classes,
                'subjects' => $subjects
            ]
        ]);
    }
}

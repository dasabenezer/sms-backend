<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('code', 'ilike', '%' . $request->search . '%');
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $subjects = $query->orderBy('name')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'required|in:core,elective,optional',
            'max_marks' => 'required|integer|min:1',
            'min_passing_marks' => 'required|integer|min:1|lte:max_marks',
            'is_active' => 'boolean'
        ]);

        $subject = Subject::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'max_marks' => $request->max_marks,
            'min_passing_marks' => $request->min_passing_marks,
            'is_active' => $request->is_active ?? true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => $subject
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'required|in:core,elective,optional',
            'max_marks' => 'required|integer|min:1',
            'min_passing_marks' => 'required|integer|min:1|lte:max_marks',
            'is_active' => 'boolean'
        ]);

        $subject->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => $subject
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $subject = Subject::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully'
        ]);
    }

    // Assign subjects to class
    public function assignToClass(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'teacher_ids' => 'nullable|array'
        ]);

        $tenantId = $request->user()->tenant_id;

        // Remove existing assignments for this class and academic year
        DB::table('class_subjects')
            ->where('tenant_id', $tenantId)
            ->where('class_id', $request->class_id)
            ->where('academic_year_id', $request->academic_year_id)
            ->delete();

        // Add new assignments
        $assignments = [];
        foreach ($request->subject_ids as $index => $subjectId) {
            $assignments[] = [
                'tenant_id' => $tenantId,
                'class_id' => $request->class_id,
                'subject_id' => $subjectId,
                'academic_year_id' => $request->academic_year_id,
                'teacher_id' => $request->teacher_ids[$index] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('class_subjects')->insert($assignments);

        return response()->json([
            'success' => true,
            'message' => 'Subjects assigned to class successfully'
        ]);
    }

    public function getClassSubjects(Request $request, $classId)
    {
        $academicYearId = $request->academic_year_id;

        $subjects = DB::table('class_subjects')
            ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.id')
            ->where('class_subjects.tenant_id', $request->user()->tenant_id)
            ->where('class_subjects.class_id', $classId)
            ->where('class_subjects.academic_year_id', $academicYearId)
            ->select('subjects.*', 'class_subjects.teacher_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }
}


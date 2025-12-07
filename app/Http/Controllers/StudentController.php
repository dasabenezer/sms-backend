<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Display a listing of students with filters
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        $query = Student::where('tenant_id', $tenantId)
            ->with(['class', 'section', 'user']);

        // Apply filters
        if ($request->has('class') && $request->class != '') {
            $query->where('class_id', $request->class);
        }

        if ($request->has('section') && $request->section != '') {
            $query->where('section_id', $request->section);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('admission_number', 'LIKE', "%{$search}%")
                  ->orWhere('roll_number', 'LIKE', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $students = $query->orderBy('admission_number', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Store a newly created student
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admission_number' => 'required|string|unique:students,admission_number',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'roll_number' => 'nullable|string|max:20',
            'admission_date' => 'required|date',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion' => 'nullable|string|max:50',
            'category' => 'nullable|in:General,OBC,SC,ST,Other',
            'mother_tongue' => 'nullable|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'father_name' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:15',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:15',
            'mother_occupation' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_phone' => 'nullable|string|max:15',
            'guardian_relation' => 'nullable|string|max:50',
            'previous_school' => 'nullable|string|max:255',
            'transfer_certificate' => 'nullable|string|max:100',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if student with admission number already exists
        $existingStudent = Student::where('tenant_id', $request->user()->tenant_id)
            ->where('admission_number', $request->admission_number)
            ->first();

        if ($existingStudent) {
            return response()->json([
                'success' => false,
                'message' => 'Student with this admission number already exists'
            ], 422);
        }

        // Create or find user account for student
        $email = $request->email ?: $request->admission_number . '@student.local';
        
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user) {
            $user = \App\Models\User::create([
                'tenant_id' => $request->user()->tenant_id,
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $email,
                'password' => bcrypt($request->date_of_birth), // Default password is DOB
                'role' => 'student',
                'is_active' => true,
            ]);
        }

        // Get or create default academic year
        $academicYear = \App\Models\AcademicYear::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->first();

        if (!$academicYear) {
            $academicYear = \App\Models\AcademicYear::create([
                'tenant_id' => $request->user()->tenant_id,
                'name' => date('Y') . '-' . (date('Y') + 1),
                'start_date' => date('Y') . '-04-01',
                'end_date' => (date('Y') + 1) . '-03-31',
                'is_active' => true,
            ]);
        }

        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('students', $filename, 'public');
            $photoPath = 'students/' . $filename;
        }

        $student = Student::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $user->id,
            'academic_year_id' => $academicYear->id,
            'admission_number' => $request->admission_number,
            'admission_date' => $request->admission_date,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'blood_group' => $request->blood_group,
            'aadhar_number' => $request->aadhar_number,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'nationality' => 'Indian',
            'religion' => $request->religion,
            'caste_category' => $request->category,
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'roll_number' => $request->roll_number,
            'previous_school_name' => $request->previous_school,
            'phone' => $request->phone,
            'email' => $request->email,
            'mother_tongue' => $request->mother_tongue,
            'transfer_certificate' => $request->transfer_certificate,
            'father_name' => $request->father_name,
            'father_phone' => $request->father_phone,
            'father_occupation' => $request->father_occupation,
            'mother_name' => $request->mother_name,
            'mother_phone' => $request->mother_phone,
            'mother_occupation' => $request->mother_occupation,
            'guardian_name' => $request->guardian_name,
            'guardian_phone' => $request->guardian_phone,
            'guardian_relation' => $request->guardian_relation,
            'photo' => $photoPath,
            'status' => 'active',
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => $student->load(['class', 'section', 'user'])
        ], 201);
    }

    /**
     * Display the specified student
     */
    public function show(Request $request, $id)
    {
        $student = Student::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    /**
     * Update the specified student
     */
    public function update(Request $request, $id)
    {
        $student = Student::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'admission_number' => 'sometimes|string|unique:students,admission_number,' . $id,
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other',
            'class_id' => 'sometimes|exists:classes,id',
            'section_id' => 'sometimes|exists:sections,id',
            'roll_number' => 'nullable|string|max:20',
            'admission_date' => 'sometimes|date',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion' => 'nullable|string|max:50',
            'category' => 'nullable|in:General,OBC,SC,ST,Other',
            'mother_tongue' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'father_name' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:15',
            'father_occupation' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:15',
            'mother_occupation' => 'nullable|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_phone' => 'nullable|string|max:15',
            'guardian_relation' => 'nullable|string|max:50',
            'previous_school' => 'nullable|string|max:255',
            'transfer_certificate' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive,transferred,graduated',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($student->photo && \Storage::disk('public')->exists($student->photo)) {
                \Storage::disk('public')->delete($student->photo);
            }
            
            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('students', $filename, 'public');
            $photoPath = 'students/' . $filename;
        } else {
            $photoPath = $student->photo;
        }

        // Map form field names to database column names
        $updateData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'blood_group' => $request->blood_group,
            'aadhar_number' => $request->aadhar_number,
            'religion' => $request->religion,
            'caste_category' => $request->category,
            'mother_tongue' => $request->mother_tongue,
            'admission_date' => $request->admission_date,
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'roll_number' => $request->roll_number,
            'previous_school_name' => $request->previous_school,
            'transfer_certificate' => $request->transfer_certificate,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'phone' => $request->phone,
            'email' => $request->email,
            'father_name' => $request->father_name,
            'father_phone' => $request->father_phone,
            'father_occupation' => $request->father_occupation,
            'mother_name' => $request->mother_name,
            'mother_phone' => $request->mother_phone,
            'mother_occupation' => $request->mother_occupation,
            'guardian_name' => $request->guardian_name,
            'guardian_phone' => $request->guardian_phone,
            'guardian_relation' => $request->guardian_relation,
            'photo' => $photoPath,
        ];

        // Remove null/empty values to avoid overwriting existing data
        $updateData = array_filter($updateData, function($value) {
            return $value !== null && $value !== '';
        });

        $student->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => $student->load(['class', 'section', 'user'])
        ]);
    }

    /**
     * Remove the specified student
     */
    public function destroy(Request $request, $id)
    {
        $student = Student::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Get classes list
     */
    public function getClasses(Request $request)
    {
        $classes = Student::where('tenant_id', $request->user()->tenant_id)
            ->distinct()
            ->pluck('class')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    /**
     * Get sections by class
     */
    public function getSections(Request $request)
    {
        $query = Student::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('class')) {
            $query->where('class', $request->class);
        }

        $sections = $query->distinct()
            ->pluck('section')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'admission_number',
        'admission_date',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'blood_group',
        'aadhar_number',
        'address',
        'city',
        'state',
        'pincode',
        'nationality',
        'religion',
        'caste_category',
        'roll_number',
        'previous_school_name',
        'previous_class',
        'phone',
        'alternate_phone',
        'father_name',
        'father_phone',
        'father_occupation',
        'mother_name',
        'mother_phone',
        'mother_occupation',
        'guardian_name',
        'guardian_phone',
        'guardian_relation',
        'photo',
        'birth_certificate',
        'transfer_certificate',
        'aadhar_card',
        'status',
        'is_active',
        'mother_tongue',
        'email',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'student_parents', 'student_id', 'parent_id')
            ->withPivot('relation', 'is_primary')
            ->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function fees()
    {
        return $this->hasMany(StudentFee::class);
    }

    public function examMarks()
    {
        return $this->hasMany(ExamMark::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

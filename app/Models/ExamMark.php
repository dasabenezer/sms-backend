<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'exam_schedule_id',
        'student_id',
        'theory_marks',
        'practical_marks',
        'total_marks',
        'grade',
        'remarks',
        'is_absent',
        'entered_by',
        'entered_at'
    ];

    protected $casts = [
        'is_absent' => 'boolean'
    ];

    // ==================== RELATIONSHIPS ====================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSchedule()
    {
        return $this->belongsTo(ExamSchedule::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // ==================== ACCESSORS ====================

    public function getGradeAttribute()
    {
        if ($this->is_absent) {
            return 'AB';
        }

        $percentage = ($this->marks_obtained / $this->examSchedule->total_marks) * 100;

        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B+';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C';
        if ($percentage >= 40) return 'D';
        return 'F';
    }

    public function getStatusAttribute()
    {
        if ($this->is_absent) {
            return 'absent';
        }

        return $this->marks_obtained >= $this->examSchedule->passing_marks ? 'pass' : 'fail';
    }
}

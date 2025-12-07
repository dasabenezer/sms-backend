<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'academic_year_id',
        'class_id',
        'name',
        'total_amount',
        'frequency',
        'effective_from',
        'effective_till',
        'is_active',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_till' => 'date',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function details()
    {
        return $this->hasMany(FeeStructureDetail::class);
    }

    public function studentFees()
    {
        return $this->hasMany(StudentFee::class);
    }
}

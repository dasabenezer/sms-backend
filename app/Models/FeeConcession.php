<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeConcession extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'fee_category_id',
        'concession_type',
        'concession_value',
        'reason',
        'effective_from',
        'effective_till',
        'is_active',
        'approved_by',
    ];

    protected $casts = [
        'concession_value' => 'decimal:2',
        'effective_from' => 'date',
        'effective_till' => 'date',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeCategory()
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

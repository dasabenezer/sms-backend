<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'max_marks',
        'min_passing_marks',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_marks' => 'integer',
        'min_passing_marks' => 'integer'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_subjects')
            ->withPivot(['academic_year_id', 'teacher_id'])
            ->withTimestamps();
    }
}


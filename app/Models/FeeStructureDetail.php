<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructureDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_structure_id',
        'fee_category_id',
        'amount',
        'due_date_type',
        'due_date',
        'due_day',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'due_day' => 'integer',
    ];

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function feeCategory()
    {
        return $this->belongsTo(FeeCategory::class);
    }
}

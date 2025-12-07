<?php

namespace App\Http\Controllers;

use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\FeeStructureDetail;
use App\Models\StudentFee;
use App\Models\FeePayment;
use App\Models\FeeConcession;
use App\Models\Student;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class FeeController extends Controller
{
    // ==================== FEE CATEGORIES ====================
    
    public function getCategories(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        $categories = FeeCategory::where('tenant_id', $tenantId)
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('code', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->has('is_active'), function($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->orderBy('name')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = FeeCategory::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fee category created successfully',
            'data' => $category
        ], 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = FeeCategory::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Fee category updated successfully',
            'data' => $category
        ]);
    }

    public function deleteCategory(Request $request, $id)
    {
        $category = FeeCategory::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        // Check if category is used in any fee structure
        $isUsed = FeeStructureDetail::where('fee_category_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category. It is being used in fee structures.'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fee category deleted successfully'
        ]);
    }

    // ==================== FEE STRUCTURES ====================

    public function getStructures(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        $structures = FeeStructure::where('tenant_id', $tenantId)
            ->with(['academicYear', 'class', 'details.feeCategory'])
            ->when($request->search, function($query, $search) {
                $query->where('name', 'ILIKE', "%{$search}%");
            })
            ->when($request->academic_year_id, function($query, $yearId) {
                $query->where('academic_year_id', $yearId);
            })
            ->when($request->class_id, function($query, $classId) {
                $query->where('class_id', $classId);
            })
            ->when($request->has('is_active'), function($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $structures
        ]);
    }

    public function getStructure(Request $request, $id)
    {
        $structure = FeeStructure::where('tenant_id', $request->user()->tenant_id)
            ->with(['academicYear', 'class', 'details.feeCategory'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $structure
        ]);
    }

    public function storeStructure(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'frequency' => 'required|in:annual,monthly,term',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'details' => 'required|array|min:1',
            'details.*.fee_category_id' => 'required|exists:fee_categories,id',
            'details.*.amount' => 'required|numeric|min:0',
            'details.*.due_date_type' => 'required|in:fixed,monthly',
            'details.*.due_date' => 'required_if:details.*.due_date_type,fixed|nullable|date',
            'details.*.due_day' => 'required_if:details.*.due_date_type,monthly|nullable|integer|min:1|max:31',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate total amount
            $totalAmount = collect($request->details)->sum('amount');

            // Create fee structure
            $structure = FeeStructure::create([
                'tenant_id' => $request->user()->tenant_id,
                'academic_year_id' => $request->academic_year_id,
                'class_id' => $request->class_id,
                'name' => $request->name,
                'total_amount' => $totalAmount,
                'frequency' => $request->frequency,
                'effective_from' => $request->effective_from,
                'effective_till' => $request->effective_till,
                'is_active' => true,
            ]);

            // Create fee structure details
            foreach ($request->details as $detail) {
                FeeStructureDetail::create([
                    'fee_structure_id' => $structure->id,
                    'fee_category_id' => $detail['fee_category_id'],
                    'amount' => $detail['amount'],
                    'due_date_type' => $detail['due_date_type'],
                    'due_date' => $detail['due_date'] ?? null,
                    'due_day' => $detail['due_day'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fee structure created successfully',
                'data' => $structure->load(['details.feeCategory'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create fee structure: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStructure(Request $request, $id)
    {
        $structure = FeeStructure::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'frequency' => 'required|in:annual,monthly,term',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
            'details' => 'required|array|min:1',
            'details.*.fee_category_id' => 'required|exists:fee_categories,id',
            'details.*.amount' => 'required|numeric|min:0',
            'details.*.due_date_type' => 'required|in:fixed,monthly',
            'details.*.due_date' => 'required_if:details.*.due_date_type,fixed|nullable|date',
            'details.*.due_day' => 'required_if:details.*.due_date_type,monthly|nullable|integer|min:1|max:31',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate total amount
            $totalAmount = collect($request->details)->sum('amount');

            // Update fee structure
            $structure->update([
                'academic_year_id' => $request->academic_year_id,
                'class_id' => $request->class_id,
                'name' => $request->name,
                'total_amount' => $totalAmount,
                'frequency' => $request->frequency,
                'effective_from' => $request->effective_from,
                'effective_till' => $request->effective_till,
                'is_active' => $request->is_active ?? $structure->is_active,
            ]);

            // Delete existing details and create new ones
            FeeStructureDetail::where('fee_structure_id', $structure->id)->delete();

            foreach ($request->details as $detail) {
                FeeStructureDetail::create([
                    'fee_structure_id' => $structure->id,
                    'fee_category_id' => $detail['fee_category_id'],
                    'amount' => $detail['amount'],
                    'due_date_type' => $detail['due_date_type'],
                    'due_date' => $detail['due_date'] ?? null,
                    'due_day' => $detail['due_day'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fee structure updated successfully',
                'data' => $structure->load(['details.feeCategory'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fee structure: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteStructure(Request $request, $id)
    {
        $structure = FeeStructure::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        // Check if structure is assigned to students
        $isUsed = StudentFee::where('fee_structure_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete structure. It is assigned to students.'
            ], 422);
        }

        $structure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fee structure deleted successfully'
        ]);
    }

    // ==================== STUDENT FEES ====================

    public function getStudentFees(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        $fees = StudentFee::where('tenant_id', $tenantId)
            ->with(['student.user', 'student.class', 'student.section', 'feeStructure', 'academicYear'])
            ->when($request->search, function($query, $search) {
                $query->whereHas('student', function($q) use ($search) {
                    $q->where('first_name', 'ILIKE', "%{$search}%")
                      ->orWhere('last_name', 'ILIKE', "%{$search}%")
                      ->orWhere('admission_number', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->academic_year_id, function($query, $yearId) {
                $query->where('academic_year_id', $yearId);
            })
            ->when($request->class_id, function($query, $classId) {
                $query->whereHas('student', function($q) use ($classId) {
                    $q->where('class_id', $classId);
                });
            })
            ->when($request->status, function($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $fees
        ]);
    }

    public function assignFeeToStudents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = $request->user()->tenant_id;
        
        // Get fee structure
        $feeStructure = FeeStructure::where('tenant_id', $tenantId)
            ->findOrFail($request->fee_structure_id);

        DB::beginTransaction();
        try {
            $assignedCount = 0;
            $skippedCount = 0;

            foreach ($request->student_ids as $studentId) {
                // Check if student already has this fee assigned
                $exists = StudentFee::where('tenant_id', $tenantId)
                    ->where('student_id', $studentId)
                    ->where('fee_structure_id', $request->fee_structure_id)
                    ->where('academic_year_id', $request->academic_year_id)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                $discountAmount = $request->discount_amount ?? 0;
                $netAmount = $feeStructure->total_amount - $discountAmount;

                StudentFee::create([
                    'tenant_id' => $tenantId,
                    'student_id' => $studentId,
                    'fee_structure_id' => $request->fee_structure_id,
                    'academic_year_id' => $request->academic_year_id,
                    'total_amount' => $feeStructure->total_amount,
                    'discount_amount' => $discountAmount,
                    'net_amount' => $netAmount,
                    'paid_amount' => 0,
                    'balance_amount' => $netAmount,
                    'status' => 'pending',
                ]);

                $assignedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Fee assigned to {$assignedCount} students. {$skippedCount} students already had this fee assigned.",
                'data' => [
                    'assigned' => $assignedCount,
                    'skipped' => $skippedCount
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign fee: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== FEE PAYMENTS ====================

    public function getPayments(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        $payments = FeePayment::where('tenant_id', $tenantId)
            ->with(['student.user', 'studentFee.feeStructure', 'collectedBy'])
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('receipt_number', 'ILIKE', "%{$search}%")
                      ->orWhere('transaction_id', 'ILIKE', "%{$search}%")
                      ->orWhereHas('student', function($sq) use ($search) {
                          $sq->where('first_name', 'ILIKE', "%{$search}%")
                            ->orWhere('last_name', 'ILIKE', "%{$search}%");
                      });
                });
            })
            ->when($request->from_date, function($query, $fromDate) {
                $query->whereDate('payment_date', '>=', $fromDate);
            })
            ->when($request->to_date, function($query, $toDate) {
                $query->whereDate('payment_date', '<=', $toDate);
            })
            ->when($request->payment_method, function($query, $method) {
                $query->where('payment_method', $method);
            })
            ->orderBy('payment_date', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    public function storePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_fee_id' => 'required|exists:student_fees,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,online,cheque,bank_transfer',
            'transaction_id' => 'nullable|string|max:255',
            'cheque_number' => 'required_if:payment_method,cheque|nullable|string|max:255',
            'cheque_date' => 'required_if:payment_method,cheque|nullable|date',
            'bank_name' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = $request->user()->tenant_id;

        // Get student fee
        $studentFee = StudentFee::where('tenant_id', $tenantId)
            ->with('student')
            ->findOrFail($request->student_fee_id);

        // Validate amount doesn't exceed balance
        if ($request->amount > $studentFee->balance_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot exceed balance amount of ₹' . number_format($studentFee->balance_amount, 2)
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate receipt number
            $receiptNumber = 'RCP' . date('Y') . str_pad(FeePayment::where('tenant_id', $tenantId)->count() + 1, 6, '0', STR_PAD_LEFT);

            // Create payment
            $payment = FeePayment::create([
                'tenant_id' => $tenantId,
                'student_id' => $studentFee->student_id,
                'student_fee_id' => $request->student_fee_id,
                'receipt_number' => $receiptNumber,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'cheque_number' => $request->cheque_number,
                'cheque_date' => $request->cheque_date,
                'bank_name' => $request->bank_name,
                'remarks' => $request->remarks,
                'status' => 'success',
                'collected_by' => $request->user()->id,
            ]);

            // Update student fee
            $newPaidAmount = $studentFee->paid_amount + $request->amount;
            $newBalanceAmount = $studentFee->balance_amount - $request->amount;
            
            $status = 'pending';
            if ($newBalanceAmount <= 0) {
                $status = 'paid';
            } elseif ($newPaidAmount > 0) {
                $status = 'partial';
            }

            $studentFee->update([
                'paid_amount' => $newPaidAmount,
                'balance_amount' => $newBalanceAmount,
                'status' => $status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => $payment->load(['student.user', 'studentFee.feeStructure'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPendingFees(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        $pendingFees = StudentFee::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->with(['student.user', 'student.class', 'student.section', 'feeStructure', 'academicYear'])
            ->when($request->class_id, function($query, $classId) {
                $query->whereHas('student', function($q) use ($classId) {
                    $q->where('class_id', $classId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $pendingFees
        ]);
    }

    // ==================== FEE CONCESSIONS ====================

    public function getConcessions(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        
        $concessions = FeeConcession::where('tenant_id', $tenantId)
            ->with(['student.user', 'feeCategory', 'approvedBy'])
            ->when($request->student_id, function($query, $studentId) {
                $query->where('student_id', $studentId);
            })
            ->when($request->has('is_active'), function($query) use ($request) {
                $query->where('is_active', $request->is_active);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $concessions
        ]);
    }

    public function storeConcession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'fee_category_id' => 'nullable|exists:fee_categories,id',
            'concession_type' => 'required|in:percentage,fixed',
            'concession_value' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
            'effective_from' => 'required|date',
            'effective_till' => 'nullable|date|after:effective_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $concession = FeeConcession::create([
            'tenant_id' => $request->user()->tenant_id,
            'student_id' => $request->student_id,
            'fee_category_id' => $request->fee_category_id,
            'concession_type' => $request->concession_type,
            'concession_value' => $request->concession_value,
            'reason' => $request->reason,
            'effective_from' => $request->effective_from,
            'effective_till' => $request->effective_till,
            'is_active' => true,
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fee concession created successfully',
            'data' => $concession->load(['student.user', 'feeCategory'])
        ], 201);
    }

    // ==================== FILTER DATA ====================

    public function getClasses(Request $request)
    {
        $classes = ClassModel::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    public function getAcademicYears(Request $request)
    {
        $academicYears = \App\Models\AcademicYear::where('tenant_id', $request->user()->tenant_id)
            ->orderBy('is_active', 'desc')
            ->orderBy('start_date', 'desc')
            ->get(['id', 'name', 'start_date', 'end_date', 'is_active']);

        return response()->json([
            'success' => true,
            'data' => $academicYears
        ]);
    }

    // ==================== PDF RECEIPT ====================

    public function downloadReceipt(Request $request, $id)
    {
        $payment = FeePayment::where('tenant_id', $request->user()->tenant_id)
            ->with([
                'studentFee.student.user',
                'studentFee.feeStructure.class',
                'studentFee.feeStructure.details.feeCategory',
                'studentFee.academicYear',
                'collectedBy'
            ])
            ->findOrFail($id);

        // Convert amount to words
        $amountInWords = $this->convertNumberToWords($payment->amount);

        // Get tenant details
        $tenant = $request->user()->tenant;

        $pdf = Pdf::loadView('receipts.payment', [
            'payment' => $payment,
            'tenant' => $tenant,
            'amountInWords' => $amountInWords
        ]);

        return $pdf->download('receipt-' . $payment->receipt_number . '.pdf');
    }

    private function convertNumberToWords($number)
    {
        $words = [
            0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
            5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
            10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
            14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
            18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
            40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy',
            80 => 'eighty', 90 => 'ninety'
        ];

        $number = number_format($number, 2, '.', '');
        list($rupees, $paise) = explode('.', $number);
        
        $rupees = (int) $rupees;
        $paise = (int) $paise;

        $rupeesWords = '';
        
        if ($rupees == 0) {
            $rupeesWords = 'zero';
        } else {
            // Crores
            if ($rupees >= 10000000) {
                $crores = floor($rupees / 10000000);
                $rupeesWords .= $this->convertLessThanThousand($crores) . ' crore ';
                $rupees %= 10000000;
            }
            
            // Lakhs
            if ($rupees >= 100000) {
                $lakhs = floor($rupees / 100000);
                $rupeesWords .= $this->convertLessThanThousand($lakhs) . ' lakh ';
                $rupees %= 100000;
            }
            
            // Thousands
            if ($rupees >= 1000) {
                $thousands = floor($rupees / 1000);
                $rupeesWords .= $this->convertLessThanThousand($thousands) . ' thousand ';
                $rupees %= 1000;
            }
            
            // Hundreds
            if ($rupees >= 100) {
                $hundreds = floor($rupees / 100);
                $rupeesWords .= $words[$hundreds] . ' hundred ';
                $rupees %= 100;
            }
            
            // Remaining (under 100)
            if ($rupees > 0) {
                if ($rupees < 20) {
                    $rupeesWords .= $words[$rupees];
                } else {
                    $tens = floor($rupees / 10) * 10;
                    $units = $rupees % 10;
                    $rupeesWords .= $words[$tens];
                    if ($units > 0) {
                        $rupeesWords .= ' ' . $words[$units];
                    }
                }
            }
        }

        $result = trim($rupeesWords) . ' rupees';
        
        if ($paise > 0) {
            $paiseWords = '';
            if ($paise < 20) {
                $paiseWords = $words[$paise];
            } else {
                $tens = floor($paise / 10) * 10;
                $units = $paise % 10;
                $paiseWords = $words[$tens];
                if ($units > 0) {
                    $paiseWords .= ' ' . $words[$units];
                }
            }
            $result .= ' and ' . trim($paiseWords) . ' paise';
        }

        return $result;
    }

    private function convertLessThanThousand($number)
    {
        $words = [
            0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
            5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
            10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
            14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
            18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
            40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy',
            80 => 'eighty', 90 => 'ninety'
        ];

        $result = '';
        
        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $result .= $words[$hundreds] . ' hundred ';
            $number %= 100;
        }
        
        if ($number > 0) {
            if ($number < 20) {
                $result .= $words[$number];
            } else {
                $tens = floor($number / 10) * 10;
                $units = $number % 10;
                $result .= $words[$tens];
                if ($units > 0) {
                    $result .= ' ' . $words[$units];
                }
            }
        }

        return trim($result);
    }

    // ==================== REPORTS & ANALYTICS ====================

    public function getReports(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:classes,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $query = FeePayment::where('fee_payments.tenant_id', $request->user()->tenant_id);

        if ($request->start_date) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        // Collection Summary
        $collectionSummary = [
            'total_collected' => $query->sum('amount'),
            'total_transactions' => $query->count(),
            'cash_collected' => (clone $query)->where('payment_method', 'cash')->sum('amount'),
            'online_collected' => (clone $query)->where('payment_method', 'online')->sum('amount'),
            'cheque_collected' => (clone $query)->where('payment_method', 'cheque')->sum('amount'),
            'bank_transfer_collected' => (clone $query)->where('payment_method', 'bank_transfer')->sum('amount'),
        ];

        // Daily collection trend (last 30 days or date range)
        $startDate = $request->start_date ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $request->end_date ?? now()->format('Y-m-d');
        
        $dailyCollections = FeePayment::where('fee_payments.tenant_id', $request->user()->tenant_id)
            ->whereDate('payment_date', '>=', $startDate)
            ->whereDate('payment_date', '<=', $endDate)
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Class-wise collection
        $classWiseQuery = FeePayment::join('student_fees', 'fee_payments.student_fee_id', '=', 'student_fees.id')
            ->join('fee_structures', 'student_fees.fee_structure_id', '=', 'fee_structures.id')
            ->join('classes', 'fee_structures.class_id', '=', 'classes.id')
            ->where('fee_payments.tenant_id', $request->user()->tenant_id);

        if ($request->start_date) {
            $classWiseQuery->whereDate('fee_payments.payment_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $classWiseQuery->whereDate('fee_payments.payment_date', '<=', $request->end_date);
        }

        $classWiseCollection = $classWiseQuery
            ->selectRaw('classes.id, classes.name, SUM(fee_payments.amount) as total_collected, COUNT(DISTINCT student_fees.student_id) as students_paid')
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('total_collected', 'desc')
            ->get();

        // Payment method breakdown
        $paymentMethodBreakdown = FeePayment::where('tenant_id', $request->user()->tenant_id);
        
        if ($request->start_date) {
            $paymentMethodBreakdown->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $paymentMethodBreakdown->whereDate('payment_date', '<=', $request->end_date);
        }

        $paymentMethodBreakdown = $paymentMethodBreakdown
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();

        // Fee category-wise collection
        $categoryWiseQuery = \DB::table('fee_payments')
            ->join('student_fees', 'fee_payments.student_fee_id', '=', 'student_fees.id')
            ->join('fee_structures', 'student_fees.fee_structure_id', '=', 'fee_structures.id')
            ->join('fee_structure_details', 'fee_structures.id', '=', 'fee_structure_details.fee_structure_id')
            ->join('fee_categories', 'fee_structure_details.fee_category_id', '=', 'fee_categories.id')
            ->where('fee_payments.tenant_id', $request->user()->tenant_id);

        if ($request->start_date) {
            $categoryWiseQuery->whereDate('fee_payments.payment_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $categoryWiseQuery->whereDate('fee_payments.payment_date', '<=', $request->end_date);
        }

        $categoryWiseCollection = $categoryWiseQuery
            ->selectRaw('fee_categories.id, fee_categories.name, fee_categories.code, COUNT(DISTINCT fee_payments.id) as payment_count')
            ->groupBy('fee_categories.id', 'fee_categories.name', 'fee_categories.code')
            ->get();

        // Pending fees summary
        $pendingFeesQuery = StudentFee::where('tenant_id', $request->user()->tenant_id)
            ->whereIn('status', ['pending', 'partial', 'overdue']);

        if ($request->class_id) {
            $pendingFeesQuery->whereHas('feeStructure', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        if ($request->academic_year_id) {
            $pendingFeesQuery->where('academic_year_id', $request->academic_year_id);
        }

        $pendingSummary = [
            'total_pending_amount' => $pendingFeesQuery->sum('balance_amount'),
            'total_pending_students' => $pendingFeesQuery->distinct('student_id')->count(),
            'overdue_amount' => (clone $pendingFeesQuery)->where('status', 'overdue')->sum('balance_amount'),
            'overdue_students' => (clone $pendingFeesQuery)->where('status', 'overdue')->distinct('student_id')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'collection_summary' => $collectionSummary,
                'daily_collections' => $dailyCollections,
                'class_wise_collection' => $classWiseCollection,
                'payment_method_breakdown' => $paymentMethodBreakdown,
                'category_wise_collection' => $categoryWiseCollection,
                'pending_summary' => $pendingSummary,
            ]
        ]);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentFee;
use Illuminate\Support\Facades\Mail;

class SendFeeReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:send-reminders {--tenant= : Specific tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send fee payment reminders to students with pending or overdue fees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting fee reminder process...');

        // Get all student fees that are pending, partial, or overdue
        $query = StudentFee::with([
            'student.user',
            'student.parent',
            'feeStructure.class',
            'academicYear',
            'tenant'
        ])->whereIn('status', ['pending', 'partial', 'overdue'])
        ->where('balance_amount', '>', 0);

        // If specific tenant is provided
        if ($this->option('tenant')) {
            $query->where('tenant_id', $this->option('tenant'));
        }

        $studentFees = $query->get();

        $this->info("Found {$studentFees->count()} students with pending fees");

        $sentCount = 0;
        $failedCount = 0;

        foreach ($studentFees as $studentFee) {
            try {
                // Get parent email (primary contact for payment)
                $parentEmail = $studentFee->student->parent->email ?? null;
                
                // Fallback to student email if parent email not available
                $recipientEmail = $parentEmail ?: ($studentFee->student->user->email ?? null);

                if (!$recipientEmail) {
                    $this->warn("No email found for student: {$studentFee->student->user->name}");
                    $failedCount++;
                    continue;
                }

                // Prepare email data
                $emailData = [
                    'parentName' => $studentFee->student->parent->name ?? 'Parent/Guardian',
                    'studentName' => $studentFee->student->user->name,
                    'admissionNumber' => $studentFee->student->admission_number,
                    'className' => $studentFee->feeStructure->class->name ?? 'N/A',
                    'academicYear' => $studentFee->academicYear->name ?? 'N/A',
                    'feeStructure' => $studentFee->feeStructure->name ?? 'N/A',
                    'status' => $studentFee->status,
                    'totalAmount' => $studentFee->total_amount,
                    'paidAmount' => $studentFee->paid_amount,
                    'balanceAmount' => $studentFee->balance_amount,
                    'schoolName' => $studentFee->tenant->name ?? 'School',
                    'schoolPhone' => $studentFee->tenant->phone ?? 'N/A',
                    'schoolEmail' => $studentFee->tenant->email ?? 'N/A',
                    'schoolWebsite' => $studentFee->tenant->website ?? null,
                ];

                // Send email
                Mail::send('emails.fee-reminder', $emailData, function ($message) use ($recipientEmail, $studentFee, $emailData) {
                    $message->to($recipientEmail)
                        ->subject("Fee Payment Reminder - {$emailData['studentName']}");
                });

                $this->info("✓ Sent reminder to: {$recipientEmail}");
                $sentCount++;

                // Optional: Create a reminder record in database
                \App\Models\FeeReminder::create([
                    'tenant_id' => $studentFee->tenant_id,
                    'student_fee_id' => $studentFee->id,
                    'reminder_type' => 'email',
                    'sent_to' => $recipientEmail,
                    'sent_at' => now(),
                    'status' => 'sent',
                ]);

            } catch (\Exception $e) {
                $errorEmail = $recipientEmail ?? 'unknown';
                $this->error("✗ Failed to send reminder to: {$errorEmail}");
                $this->error("Error: " . $e->getMessage());
                $failedCount++;

                // Log failed reminder
                \App\Models\FeeReminder::create([
                    'tenant_id' => $studentFee->tenant_id,
                    'student_fee_id' => $studentFee->id,
                    'reminder_type' => 'email',
                    'sent_to' => $recipientEmail ?? null,
                    'sent_at' => now(),
                    'status' => 'failed',
                    'notes' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("========================================");
        $this->info("Fee Reminder Process Completed");
        $this->info("========================================");
        $this->info("Total reminders sent: {$sentCount}");
        $this->warn("Failed reminders: {$failedCount}");
        $this->info("========================================");

        return Command::SUCCESS;
    }
}

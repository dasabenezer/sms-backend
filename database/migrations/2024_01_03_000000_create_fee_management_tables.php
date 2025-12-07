<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fee Categories
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Tuition, Admission, Transport, etc.
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Fee Structures
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Annual Fee Structure"
            $table->decimal('total_amount', 10, 2);
            $table->string('frequency')->default('annual'); // annual, monthly, term
            $table->date('effective_from');
            $table->date('effective_till')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Fee Structure Details
        Schema::create('fee_structure_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_category_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('due_date_type')->default('fixed'); // fixed, monthly
            $table->date('due_date')->nullable();
            $table->integer('due_day')->nullable(); // For monthly fees
            $table->timestamps();
        });

        // Student Fee Assignments
        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_structure_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2);
            $table->string('status')->default('pending'); // pending, partial, paid, overdue
            $table->timestamps();
            
            $table->index(['student_id', 'academic_year_id']);
        });

        // Fee Payments
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_fee_id')->constrained()->onDelete('cascade');
            $table->string('receipt_number')->unique();
            $table->date('payment_date');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method'); // cash, online, cheque, bank_transfer
            $table->string('transaction_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('success'); // pending, success, failed, refunded
            $table->foreignId('collected_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['student_id', 'payment_date']);
        });

        // Fee Concessions/Discounts
        Schema::create('fee_concessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_category_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('concession_type'); // percentage, fixed
            $table->decimal('concession_value', 10, 2);
            $table->string('reason');
            $table->date('effective_from');
            $table->date('effective_till')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Fee Reminders
        Schema::create('fee_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_fee_id')->constrained()->onDelete('cascade');
            $table->string('reminder_type'); // sms, email, whatsapp
            $table->text('message');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_reminders');
        Schema::dropIfExists('fee_concessions');
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('student_fees');
        Schema::dropIfExists('fee_structure_details');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_categories');
    }
};

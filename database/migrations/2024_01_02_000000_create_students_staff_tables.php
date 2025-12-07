<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Students
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            
            // Personal Information
            $table->string('admission_number')->unique();
            $table->date('admission_date');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->string('blood_group', 5)->nullable();
            $table->string('aadhar_number', 12)->nullable();
            $table->text('address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('pincode', 10);
            $table->string('nationality')->default('Indian');
            $table->string('religion')->nullable();
            $table->string('caste_category')->nullable(); // General, OBC, SC, ST
            
            // Academic Information
            $table->string('roll_number')->nullable();
            $table->date('previous_school_name')->nullable();
            $table->string('previous_class')->nullable();
            
            // Contact Information
            $table->string('phone', 15)->nullable();
            $table->string('alternate_phone', 15)->nullable();
            
            // Documents
            $table->string('photo')->nullable();
            $table->string('birth_certificate')->nullable();
            $table->string('transfer_certificate')->nullable();
            $table->string('aadhar_card')->nullable();
            
            // Status
            $table->string('status')->default('active'); // active, inactive, transferred, passed_out
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'class_id', 'section_id']);
        });

        // Parents/Guardians
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Father Details
            $table->string('father_name');
            $table->string('father_phone', 15);
            $table->string('father_email')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('father_annual_income')->nullable();
            $table->string('father_aadhar', 12)->nullable();
            
            // Mother Details
            $table->string('mother_name');
            $table->string('mother_phone', 15)->nullable();
            $table->string('mother_email')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->string('mother_annual_income')->nullable();
            $table->string('mother_aadhar', 12)->nullable();
            
            // Guardian Details (if different)
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation')->nullable();
            $table->string('guardian_phone', 15)->nullable();
            $table->string('guardian_email')->nullable();
            
            $table->timestamps();
        });

        // Student-Parent Relationship
        Schema::create('student_parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->constrained()->onDelete('cascade');
            $table->string('relation'); // father, mother, guardian
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->unique(['student_id', 'parent_id']);
        });

        // Staff Members
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('employee_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('designation');
            $table->string('department')->nullable();
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->string('blood_group', 5)->nullable();
            $table->string('aadhar_number', 12)->nullable();
            $table->string('pan_number', 10)->nullable();
            
            // Contact
            $table->string('phone', 15);
            $table->string('alternate_phone', 15)->nullable();
            $table->text('address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('pincode', 10);
            
            // Employment Details
            $table->date('joining_date');
            $table->date('leaving_date')->nullable();
            $table->string('employment_type')->default('permanent'); // permanent, contract, temporary
            $table->decimal('salary', 10, 2)->nullable();
            
            // Qualifications
            $table->json('qualifications')->nullable();
            $table->json('experience')->nullable();
            
            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            
            // Documents
            $table->string('photo')->nullable();
            $table->json('documents')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('student_parents');
        Schema::dropIfExists('parents');
        Schema::dropIfExists('students');
    }
};

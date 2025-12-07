<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Exam Types
        Schema::create('exam_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Unit Test, Mid-term, Final, etc.
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Exams
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Exam Schedules
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->string('room_number')->nullable();
            $table->integer('max_marks')->default(100);
            $table->integer('min_passing_marks')->default(33);
            $table->text('instructions')->nullable();
            $table->timestamps();
            
            $table->unique(['exam_id', 'class_id', 'subject_id']);
        });

        // Exam Marks
        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->decimal('theory_marks', 5, 2)->nullable();
            $table->decimal('practical_marks', 5, 2)->nullable();
            $table->decimal('total_marks', 5, 2);
            $table->string('grade')->nullable();
            $table->string('remarks')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->foreignId('entered_by')->constrained('users');
            $table->timestamp('entered_at');
            $table->timestamps();
            
            $table->unique(['exam_schedule_id', 'student_id']);
            $table->index(['student_id', 'exam_schedule_id']);
        });

        // Grade Scales
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "CBSE Grading System"
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Grade Scale Details
        Schema::create('grade_scale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_scale_id')->constrained()->onDelete('cascade');
            $table->string('grade'); // A+, A, B, etc.
            $table->decimal('min_percentage', 5, 2);
            $table->decimal('max_percentage', 5, 2);
            $table->decimal('grade_point', 3, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Report Cards
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->decimal('total_marks_obtained', 10, 2);
            $table->decimal('total_max_marks', 10, 2);
            $table->decimal('percentage', 5, 2);
            $table->string('overall_grade')->nullable();
            $table->decimal('cgpa', 3, 2)->nullable();
            $table->integer('rank')->nullable();
            $table->integer('total_students')->nullable();
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->text('teacher_remarks')->nullable();
            $table->text('principal_remarks')->nullable();
            $table->string('result_status'); // pass, fail, promoted, detained
            $table->date('issue_date')->nullable();
            $table->string('pdf_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->unique(['student_id', 'exam_id', 'academic_year_id']);
        });

        // Assignments
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users');
            $table->string('title');
            $table->text('description');
            $table->date('assign_date');
            $table->date('due_date');
            $table->integer('max_marks')->default(0);
            $table->string('attachment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Assignment Submissions
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->text('submission_text')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamp('submitted_at');
            $table->decimal('marks_obtained', 5, 2)->nullable();
            $table->text('teacher_remarks')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('report_cards');
        Schema::dropIfExists('grade_scale_details');
        Schema::dropIfExists('grade_scales');
        Schema::dropIfExists('exam_marks');
        Schema::dropIfExists('exam_schedules');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('exam_types');
    }
};

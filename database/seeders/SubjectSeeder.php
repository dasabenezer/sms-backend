<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        // Create subjects
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH', 'type' => 'core'],
            ['name' => 'Science', 'code' => 'SCI', 'type' => 'core'],
            ['name' => 'English', 'code' => 'ENG', 'type' => 'core'],
            ['name' => 'Social Studies', 'code' => 'SST', 'type' => 'core'],
            ['name' => 'Computer Science', 'code' => 'CS', 'type' => 'elective'],
            ['name' => 'Physical Education', 'code' => 'PE', 'type' => 'core']
        ];

        foreach ($subjects as $subjectData) {
            $exists = DB::table('subjects')
                ->where('tenant_id', $tenantId)
                ->where('name', $subjectData['name'])
                ->exists();
            
            if (!$exists) {
                DB::table('subjects')->insert([
                    'tenant_id' => $tenantId,
                    'name' => $subjectData['name'],
                    'code' => $subjectData['code'],
                    'type' => $subjectData['type'],
                    'max_marks' => 100,
                    'min_passing_marks' => 33,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Get active academic year
        $academicYear = DB::table('academic_years')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$academicYear) {
            echo "No active academic year found. Please create one first.\n";
            return;
        }

        // Assign all subjects to all classes
        $classes = DB::table('classes')->where('tenant_id', $tenantId)->get();
        $allSubjects = DB::table('subjects')->where('tenant_id', $tenantId)->get();

        foreach ($classes as $class) {
            foreach ($allSubjects as $subject) {
                $exists = DB::table('class_subjects')
                    ->where('tenant_id', $tenantId)
                    ->where('class_id', $class->id)
                    ->where('subject_id', $subject->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->exists();
                
                if (!$exists) {
                    DB::table('class_subjects')->insert([
                        'tenant_id' => $tenantId,
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'academic_year_id' => $academicYear->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }

        echo "Subjects created and assigned to all classes successfully!\n";
    }
}

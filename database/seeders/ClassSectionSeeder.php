<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class ClassSectionSeeder extends Seeder
{
    public function run(): void
    {
        // Get all tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Create classes for each tenant
            $classes = [
                ['name' => 'Nursery', 'order' => 1],
                ['name' => 'LKG', 'order' => 2],
                ['name' => 'UKG', 'order' => 3],
                ['name' => 'Class 1', 'order' => 4],
                ['name' => 'Class 2', 'order' => 5],
                ['name' => 'Class 3', 'order' => 6],
                ['name' => 'Class 4', 'order' => 7],
                ['name' => 'Class 5', 'order' => 8],
                ['name' => 'Class 6', 'order' => 9],
                ['name' => 'Class 7', 'order' => 10],
                ['name' => 'Class 8', 'order' => 11],
                ['name' => 'Class 9', 'order' => 12],
                ['name' => 'Class 10', 'order' => 13],
                ['name' => 'Class 11', 'order' => 14],
                ['name' => 'Class 12', 'order' => 15],
            ];

            $classIds = [];
            foreach ($classes as $class) {
                $classId = DB::table('classes')->insertGetId([
                    'tenant_id' => $tenant->id,
                    'name' => $class['name'],
                    'order' => $class['order'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $classIds[] = $classId;
            }

            // Get or create default academic year
            $academicYear = DB::table('academic_years')
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->first();

            if (!$academicYear) {
                $academicYearId = DB::table('academic_years')->insertGetId([
                    'tenant_id' => $tenant->id,
                    'name' => date('Y') . '-' . (date('Y') + 1),
                    'start_date' => date('Y') . '-04-01',
                    'end_date' => (date('Y') + 1) . '-03-31',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $academicYearId = $academicYear->id;
            }

            // Create sections (A, B, C, D) for each class
            $sections = ['A', 'B', 'C', 'D'];
            foreach ($classIds as $classId) {
                foreach ($sections as $index => $sectionName) {
                    DB::table('sections')->insert([
                        'tenant_id' => $tenant->id,
                        'class_id' => $classId,
                        'academic_year_id' => $academicYearId,
                        'name' => $sectionName,
                        'capacity' => 40,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('Classes and sections created successfully for all tenants!');
    }
}

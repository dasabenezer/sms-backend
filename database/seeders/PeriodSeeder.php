<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class PeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all tenants
        $tenants = Tenant::all();

        // Default periods structure
        $defaultPeriods = [
            [
                'name' => 'Period 1',
                'start_time' => '08:00:00',
                'end_time' => '08:45:00',
                'order_number' => 1,
                'is_break' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Period 2',
                'start_time' => '08:45:00',
                'end_time' => '09:30:00',
                'order_number' => 2,
                'is_break' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Period 3',
                'start_time' => '09:30:00',
                'end_time' => '10:15:00',
                'order_number' => 3,
                'is_break' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Short Break',
                'start_time' => '10:15:00',
                'end_time' => '10:30:00',
                'order_number' => 4,
                'is_break' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Period 4',
                'start_time' => '10:30:00',
                'end_time' => '11:15:00',
                'order_number' => 5,
                'is_break' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Period 5',
                'start_time' => '11:15:00',
                'end_time' => '12:00:00',
                'order_number' => 6,
                'is_break' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Lunch Break',
                'start_time' => '12:00:00',
                'end_time' => '12:45:00',
                'order_number' => 7,
                'is_break' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Period 6',
                'start_time' => '12:45:00',
                'end_time' => '01:30:00',
                'order_number' => 8,
                'is_break' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Period 7',
                'start_time' => '01:30:00',
                'end_time' => '02:15:00',
                'order_number' => 9,
                'is_break' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Period 8',
                'start_time' => '02:15:00',
                'end_time' => '03:00:00',
                'order_number' => 10,
                'is_break' => false,
                'is_active' => true,
            ],
        ];

        // Insert periods for each tenant
        foreach ($tenants as $tenant) {
            // Check if periods already exist for this tenant
            $existingCount = DB::table('periods')
                ->where('tenant_id', $tenant->id)
                ->count();

            // Only seed if no periods exist
            if ($existingCount === 0) {
                $periodsToInsert = [];
                
                foreach ($defaultPeriods as $period) {
                    $periodsToInsert[] = array_merge($period, [
                        'tenant_id' => $tenant->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('periods')->insert($periodsToInsert);
                
                $count = count($periodsToInsert);
                $this->command->info("Created {$count} periods for tenant: {$tenant->name}");
            } else {
                $this->command->info("Periods already exist for tenant: {$tenant->name}, skipping...");
            }
        }

        $this->command->info('Period seeding completed successfully!');
    }
}

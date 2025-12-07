<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TenantRegistrationController extends Controller
{
    /**
     * Register a new school (tenant)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:63|unique:tenants,subdomain|regex:/^[a-z0-9-]+$/',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',
            'board_affiliation' => 'required|in:CBSE,ICSE,State Board,IB,Others',
            'total_students' => 'required|integer|min:0',
            'subscription_plan' => 'nullable|in:trial,basic,standard,premium',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create tenant
            $tenant = Tenant::create([
                'name' => $request->school_name,
                'subdomain' => $request->subdomain,
                'email' => $request->admin_email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'board' => $request->board_affiliation,
                'subscription_plan' => $request->subscription_plan ?? 'trial',
                'trial_ends_at' => now()->addDays(env('TRIAL_PERIOD_DAYS', 14)),
                'max_students' => $this->getMaxStudents($request->subscription_plan ?? 'trial'),
                'is_active' => true,
            ]);

            // Create admin user
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'role' => 'admin',
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School registered successfully! You can now login.',
                'data' => [
                    'tenant_id' => $tenant->id,
                    'school_name' => $tenant->name,
                    'subdomain' => $tenant->subdomain,
                    'admin_email' => $user->email,
                    'trial_ends_at' => $tenant->trial_ends_at->format('Y-m-d'),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if subdomain is available
     */
    public function checkSubdomain(Request $request)
    {
        $subdomain = $request->input('subdomain');
        
        if (!$subdomain) {
            return response()->json(['available' => false, 'message' => 'Subdomain is required']);
        }

        // Validate subdomain format
        if (!preg_match('/^[a-z0-9-]+$/', $subdomain)) {
            return response()->json([
                'available' => false,
                'message' => 'Subdomain can only contain lowercase letters, numbers, and hyphens'
            ]);
        }

        // Check if subdomain is available
        $exists = Tenant::where('subdomain', $subdomain)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'This subdomain is already taken' : 'Subdomain is available',
            'suggested_url' => "http://{$subdomain}.localhost:3000"
        ]);
    }

    /**
     * Get subscription plans
     */
    public function getPlans()
    {
        $plans = [
            [
                'id' => 'trial',
                'name' => 'Free Trial',
                'price' => 0,
                'duration' => '14 days',
                'max_students' => 50,
                'features' => [
                    'Student Management',
                    'Fee Management',
                    'Attendance Tracking',
                    'Basic Reports',
                    'Email Support'
                ]
            ],
            [
                'id' => 'basic',
                'name' => 'Basic Plan',
                'price' => 2999,
                'duration' => 'per month',
                'max_students' => 200,
                'features' => [
                    'All Trial Features',
                    'Examination Module',
                    'Timetable Management',
                    'SMS Notifications',
                    'Priority Support'
                ]
            ],
            [
                'id' => 'standard',
                'name' => 'Standard Plan',
                'price' => 5999,
                'duration' => 'per month',
                'max_students' => 500,
                'features' => [
                    'All Basic Features',
                    'Library Management',
                    'Transport Management',
                    'Hostel Management',
                    'Advanced Reports',
                    '24/7 Support'
                ]
            ],
            [
                'id' => 'premium',
                'name' => 'Premium Plan',
                'price' => 9999,
                'duration' => 'per month',
                'max_students' => 'Unlimited',
                'features' => [
                    'All Standard Features',
                    'HR & Payroll',
                    'Biometric Integration',
                    'Custom Branding',
                    'API Access',
                    'Dedicated Account Manager'
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'plans' => $plans
        ]);
    }

    /**
     * Get max students based on plan
     */
    private function getMaxStudents($plan)
    {
        return match($plan) {
            'trial' => 50,
            'basic' => 200,
            'standard' => 500,
            'premium' => 99999,
            default => 50,
        };
    }
}

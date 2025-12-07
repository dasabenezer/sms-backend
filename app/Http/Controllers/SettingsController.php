<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $query = Setting::where('tenant_id', $tenantId);

        // Filter by group
        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        $settings = $query->orderBy('group')->orderBy('key')->get();

        // Group settings by their group
        $groupedSettings = $settings->groupBy('group');

        return response()->json([
            'success' => true,
            'data' => $groupedSettings
        ]);
    }

    public function show(Request $request, $key)
    {
        $tenantId = $request->user()->tenant_id;
        
        $setting = Setting::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $tenantId = $request->user()->tenant_id;
        $updated = 0;

        foreach ($request->settings as $settingData) {
            $setting = Setting::where('tenant_id', $tenantId)
                ->where('key', $settingData['key'])
                ->first();

            if ($setting) {
                $setting->update([
                    'value' => $settingData['value']
                ]);
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$updated} settings updated successfully",
            'count' => $updated
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|unique:settings,key',
            'value' => 'required',
            'type' => 'required|in:string,integer,boolean,json,text',
            'group' => 'required|string',
            'description' => 'nullable|string',
            'is_public' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $setting = Setting::create([
            'tenant_id' => $request->user()->tenant_id,
            'key' => $request->key,
            'value' => $request->value,
            'type' => $request->type,
            'group' => $request->group,
            'description' => $request->description,
            'is_public' => $request->is_public ?? false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Setting created successfully',
            'data' => $setting
        ], 201);
    }

    public function destroy(Request $request, $key)
    {
        $tenantId = $request->user()->tenant_id;
        
        $setting = Setting::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully'
        ]);
    }

    public function initializeDefaults(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $defaults = [
            // General Settings
            [
                'key' => 'school_name',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'description' => 'School name'
            ],
            [
                'key' => 'school_address',
                'value' => '',
                'type' => 'text',
                'group' => 'general',
                'description' => 'School address'
            ],
            [
                'key' => 'school_phone',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'description' => 'School phone number'
            ],
            [
                'key' => 'school_email',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'description' => 'School email address'
            ],
            // Academic Settings
            [
                'key' => 'current_academic_year',
                'value' => '',
                'type' => 'integer',
                'group' => 'academic',
                'description' => 'Current academic year ID'
            ],
            [
                'key' => 'enable_attendance',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'academic',
                'description' => 'Enable attendance module'
            ],
            // Notification Settings
            [
                'key' => 'enable_email_notifications',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'Enable email notifications'
            ],
            [
                'key' => 'enable_sms_notifications',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'Enable SMS notifications'
            ],
            // Fee Settings
            [
                'key' => 'late_fee_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'fee',
                'description' => 'Enable late fee charges'
            ],
            [
                'key' => 'late_fee_amount',
                'value' => '0',
                'type' => 'integer',
                'group' => 'fee',
                'description' => 'Late fee amount'
            ],
            // Exam Settings
            [
                'key' => 'passing_percentage',
                'value' => '40',
                'type' => 'integer',
                'group' => 'exam',
                'description' => 'Minimum passing percentage'
            ],
            [
                'key' => 'enable_grade_system',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'exam',
                'description' => 'Enable grade-based marking'
            ],
        ];

        $created = 0;
        foreach ($defaults as $default) {
            $exists = Setting::where('tenant_id', $tenantId)
                ->where('key', $default['key'])
                ->exists();

            if (!$exists) {
                Setting::create(array_merge($default, ['tenant_id' => $tenantId]));
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$created} default settings initialized",
            'count' => $created
        ]);
    }
}

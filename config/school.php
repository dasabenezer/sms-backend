<?php

return [
    'tenancy' => [
        'enabled' => env('TENANCY_ENABLED', true),
        'database' => env('TENANCY_DATABASE', 'tenants'),
        'central_domains' => [
            env('CENTRAL_DOMAIN', 'schoolsms.test'),
        ],
    ],

    'features' => [
        'student_management' => true,
        'fee_management' => true,
        'attendance' => true,
        'examination' => true,
        'timetable' => true,
        'transport' => true,
        'hostel' => false, // Optional feature
        'library' => true,
        'inventory' => true,
        'payroll' => true,
        'sms' => true,
        'whatsapp' => true,
        'biometric' => false, // Paid add-on
        'online_learning' => false, // Advanced plan
    ],

    'subscription' => [
        'trial_days' => env('TRIAL_PERIOD_DAYS', 14),
        'onboarding_fee' => env('ONBOARDING_FEE_PER_STUDENT', 5),
        
        'plans' => [
            'starter' => [
                'name' => 'Starter Plan',
                'student_range' => [0, 200],
                'monthly_price' => 1499,
                'yearly_price' => 14999,
                'features' => [
                    'student_management',
                    'fee_management',
                    'attendance',
                    'examination',
                    'sms' => 500, // 500 SMS/month
                ],
            ],
            'professional' => [
                'name' => 'Professional Plan',
                'student_range' => [201, 500],
                'monthly_price' => 2499,
                'yearly_price' => 25999,
                'features' => [
                    'all_starter_features',
                    'transport',
                    'library',
                    'sms' => 1500, // 1500 SMS/month
                    'whatsapp' => 500,
                ],
            ],
            'enterprise' => [
                'name' => 'Enterprise Plan',
                'student_range' => [501, 1000],
                'monthly_price' => 3999,
                'yearly_price' => 39999,
                'features' => [
                    'all_professional_features',
                    'hostel',
                    'inventory',
                    'payroll',
                    'sms' => 3000,
                    'whatsapp' => 1500,
                    'priority_support',
                ],
            ],
        ],
    ],

    'sms' => [
        'default_provider' => env('SMS_PROVIDER', 'msg91'),
        
        'msg91' => [
            'auth_key' => env('MSG91_AUTH_KEY'),
            'sender_id' => env('MSG91_SENDER_ID'),
            'route' => env('MSG91_ROUTE', 4),
            'country_code' => '91',
        ],
        
        'textlocal' => [
            'api_key' => env('TEXTLOCAL_API_KEY'),
            'sender' => env('TEXTLOCAL_SENDER'),
        ],
    ],

    'payment' => [
        'razorpay' => [
            'key' => env('RAZORPAY_KEY'),
            'secret' => env('RAZORPAY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
            'currency' => 'INR',
        ],
    ],

    'gst' => [
        'number' => env('GST_NUMBER'),
        'rate' => env('GST_RATE', 18),
    ],

    'academic' => [
        'boards' => [
            'cbse' => 'Central Board of Secondary Education',
            'icse' => 'Indian Certificate of Secondary Education',
            'state' => 'State Board',
        ],
        
        'fee_types' => [
            'admission' => 'Admission Fee',
            'tuition' => 'Tuition Fee',
            'exam' => 'Examination Fee',
            'computer' => 'Computer Fee',
            'library' => 'Library Fee',
            'sports' => 'Sports Fee',
            'transport' => 'Transport Fee',
            'hostel' => 'Hostel Fee',
            'miscellaneous' => 'Miscellaneous Fee',
        ],
        
        'attendance_statuses' => [
            'present' => 'Present',
            'absent' => 'Absent',
            'late' => 'Late',
            'half_day' => 'Half Day',
            'on_leave' => 'On Leave',
        ],
    ],
];

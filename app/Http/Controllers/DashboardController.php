<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        // Total students
        $totalStudents = Student::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        // Total staff
        $totalStaff = Staff::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        // Total revenue (current month)
        $currentMonthRevenue = DB::table('fee_payments')
            ->where('tenant_id', $tenantId)
            ->whereYear('payment_date', Carbon::now()->year)
            ->whereMonth('payment_date', Carbon::now()->month)
            ->sum('amount');

        // Pending fees
        $pendingFees = DB::table('student_fees')
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->sum('balance_amount');

        // Today's attendance
        $todayAttendance = DB::table('student_attendances')
            ->where('tenant_id', $tenantId)
            ->whereDate('attendance_date', Carbon::today())
            ->where('status', 'present')
            ->count();

        // Attendance percentage (last 7 days)
        $weekAttendance = DB::table('student_attendances')
            ->where('tenant_id', $tenantId)
            ->whereBetween('attendance_date', [Carbon::now()->subDays(7), Carbon::now()])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $presentCount = $weekAttendance->where('status', 'present')->first()->count ?? 0;
        $totalCount = $weekAttendance->sum('count') ?: 1;
        $attendancePercentage = round(($presentCount / $totalCount) * 100, 1);

        // Recent students (last 5)
        $recentStudents = Student::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with(['class:id,name', 'section:id,name'])
            ->orderBy('admission_date', 'desc')
            ->limit(5)
            ->select('id', 'admission_number', 'first_name', 'last_name', 'class_id', 'section_id', 'admission_date')
            ->get();

        // Monthly revenue trend (last 6 months)
        $revenueChart = DB::table('fee_payments')
            ->where('tenant_id', $tenantId)
            ->where('payment_date', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('TO_CHAR(payment_date, \'YYYY-MM\') as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Class-wise student distribution
        $classDistribution = Student::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('class:id,name')
            ->select('class_id', DB::raw('count(*) as count'))
            ->groupBy('class_id')
            ->orderBy('class_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_students' => $totalStudents,
                    'total_staff' => $totalStaff,
                    'monthly_revenue' => $currentMonthRevenue,
                    'pending_fees' => $pendingFees,
                    'today_attendance' => $todayAttendance,
                    'attendance_percentage' => $attendancePercentage,
                ],
                'recent_students' => $recentStudents,
                'revenue_chart' => $revenueChart,
                'class_distribution' => $classDistribution,
            ]
        ]);
    }
}

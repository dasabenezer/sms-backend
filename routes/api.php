<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantRegistrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\TenantController;

Route::get('/', function () {
    return response()->json([
        'message' => 'School Management System API',
        'version' => '1.0',
        'status' => 'running'
    ]);
});

// Public routes for tenant registration
Route::post('/register-school', [TenantRegistrationController::class, 'register']);
Route::get('/check-subdomain', [TenantRegistrationController::class, 'checkSubdomain']);
Route::get('/subscription-plans', [TenantRegistrationController::class, 'getPlans']);

// Authentication routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // Students
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/students/{id}', [StudentController::class, 'show']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);
    Route::get('/students/filters/classes', [StudentController::class, 'getClasses']);
    Route::get('/students/filters/sections', [StudentController::class, 'getSections']);
    
    // Fee Categories
    Route::get('/fees/categories', [FeeController::class, 'getCategories']);
    Route::post('/fees/categories', [FeeController::class, 'storeCategory']);
    Route::put('/fees/categories/{id}', [FeeController::class, 'updateCategory']);
    Route::delete('/fees/categories/{id}', [FeeController::class, 'deleteCategory']);
    
    // Fee Structures
    Route::get('/fees/structures', [FeeController::class, 'getStructures']);
    Route::get('/fees/structures/{id}', [FeeController::class, 'getStructure']);
    Route::post('/fees/structures', [FeeController::class, 'storeStructure']);
    Route::put('/fees/structures/{id}', [FeeController::class, 'updateStructure']);
    Route::delete('/fees/structures/{id}', [FeeController::class, 'deleteStructure']);
    
    // Student Fees
    Route::get('/fees/student-fees', [FeeController::class, 'getStudentFees']);
    Route::post('/fees/assign', [FeeController::class, 'assignFeeToStudents']);
    Route::get('/fees/pending', [FeeController::class, 'getPendingFees']);
    
    // Fee Payments
    Route::get('/fees/payments', [FeeController::class, 'getPayments']);
    Route::post('/fees/payments', [FeeController::class, 'storePayment']);
    Route::get('/fees/payments/{id}/receipt', [FeeController::class, 'downloadReceipt']);
    
    // Fee Concessions
    Route::get('/fees/concessions', [FeeController::class, 'getConcessions']);
    Route::post('/fees/concessions', [FeeController::class, 'storeConcession']);
    
    // Fee Reports
    Route::get('/fees/reports', [FeeController::class, 'getReports']);
    
    // Fee Filters
    Route::get('/fees/filters/classes', [FeeController::class, 'getClasses']);
    Route::get('/fees/filters/academic-years', [FeeController::class, 'getAcademicYears']);
    
    // Attendance
    Route::get('/attendance/dashboard-stats', [AttendanceController::class, 'getDashboardStats']);
    Route::post('/attendance/mark', [AttendanceController::class, 'markAttendance']);
    Route::get('/attendance', [AttendanceController::class, 'getAttendance']);
    Route::get('/attendance/for-date', [AttendanceController::class, 'getAttendanceForDate']);
    Route::get('/attendance/summary', [AttendanceController::class, 'getAttendanceSummary']);
    Route::get('/attendance/reports/student-wise', [AttendanceController::class, 'getStudentWiseReport']);
    Route::get('/attendance/reports/date-wise', [AttendanceController::class, 'getDateWiseReport']);
    Route::get('/attendance/reports/student-wise/pdf', [AttendanceController::class, 'exportStudentWisePDF']);
    Route::get('/attendance/reports/date-wise/pdf', [AttendanceController::class, 'exportDateWisePDF']);
    Route::get('/attendance/filters/classes', [AttendanceController::class, 'getClasses']);
    
    // Examinations
    Route::get('/exams', [ExamController::class, 'index']);
    Route::post('/exams', [ExamController::class, 'store']);
    Route::put('/exams/{id}', [ExamController::class, 'update']);
    Route::delete('/exams/{id}', [ExamController::class, 'destroy']);
    Route::get('/exams/{examId}/schedules', [ExamController::class, 'getSchedules']);
    Route::post('/exams/{examId}/schedules', [ExamController::class, 'storeSchedule']);
    Route::put('/exams/{examId}/schedules/{scheduleId}', [ExamController::class, 'updateSchedule']);
    Route::delete('/exams/{examId}/schedules/{scheduleId}', [ExamController::class, 'deleteSchedule']);
    Route::get('/exams/schedules/{scheduleId}/marks', [ExamController::class, 'getMarksEntry']);
    Route::post('/exams/schedules/{scheduleId}/marks', [ExamController::class, 'storeMarks']);
    Route::get('/exams/{examId}/marksheet/{studentId}', [ExamController::class, 'getMarksheet']);
    Route::get('/exams/{examId}/marksheet/{studentId}/download', [ExamController::class, 'downloadMarksheet']);
    Route::get('/exams/filters', [ExamController::class, 'getFilters']);
    Route::get('/exams/subjects', [ExamController::class, 'getSubjects']);
    
    // Subjects
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::post('/subjects', [SubjectController::class, 'store']);
    Route::put('/subjects/{id}', [SubjectController::class, 'update']);
    Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);
    Route::post('/subjects/assign-to-class', [SubjectController::class, 'assignToClass']);
    Route::get('/subjects/class/{classId}', [SubjectController::class, 'getClassSubjects']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Timetable
    Route::get('/periods', [TimetableController::class, 'getPeriods']);
    Route::post('/periods', [TimetableController::class, 'storePeriod']);
    Route::put('/periods/{id}', [TimetableController::class, 'updatePeriod']);
    Route::delete('/periods/{id}', [TimetableController::class, 'deletePeriod']);
    Route::get('/timetable', [TimetableController::class, 'getTimetable']);
    Route::post('/timetable', [TimetableController::class, 'storeTimetable']);
    Route::put('/timetable/{id}', [TimetableController::class, 'updateTimetable']);
    Route::delete('/timetable/{id}', [TimetableController::class, 'deleteTimetable']);
    Route::get('/timetable/filters', [TimetableController::class, 'getFilters']);
    Route::get('/timetable/teacher/{teacherId}/download', [TimetableController::class, 'downloadTeacherTimetable']);
    Route::get('/timetable/class/{classId}/section/{sectionId}/download', [TimetableController::class, 'downloadClassTimetable']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);

    // Settings
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::get('/settings/{key}', [SettingsController::class, 'show']);
    Route::post('/settings', [SettingsController::class, 'store']);
    Route::put('/settings', [SettingsController::class, 'update']);
    Route::delete('/settings/{key}', [SettingsController::class, 'destroy']);
    Route::post('/settings/initialize-defaults', [SettingsController::class, 'initializeDefaults']);

    // Tenant/School Management
    Route::get('/tenant', [TenantController::class, 'show']);
    Route::put('/tenant', [TenantController::class, 'update']);
    Route::post('/tenant/logo', [TenantController::class, 'updateLogo']);
    Route::delete('/tenant/logo', [TenantController::class, 'deleteLogo']);

    // Global Search
    Route::get('/search', [GlobalSearchController::class, 'search']);
});

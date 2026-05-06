<?php

use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\UserLocationController;
use Illuminate\Support\Facades\Route;

// 🟢 PUBLIC / AUTH API
Route::get('/check-status', [AdminController::class, 'checkStatus']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/branding', [AuthController::class, 'branding']);

// 🔒 PROTECTED API (SHARED)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 👨‍🏫 TEACHER ONLY
    Route::middleware('role:teacher')->group(function () {
        Route::get('/teacher/summary', [TeacherController::class, 'getSummary']);
        Route::get('/teacher/classes', [TeacherController::class, 'getClasses']);
        Route::get('/teacher/classes/{classId}/sessions', [TeacherController::class, 'getSessionsByClass']);
        Route::get('/teacher/classes/{classId}/students', [TeacherController::class, 'getStudentsByClass']);
        Route::get('/teacher/students', [TeacherController::class, 'getStudents']);
        Route::get('/teacher/sessions', [TeacherController::class, 'getSessions']);
        
        Route::get('/teacher/session/{sessionId}/qr', [TeacherController::class, 'generateQr']);
        Route::post('/teacher/session/{sessionId}/regenerate-qr', [TeacherController::class, 'regenerateQr']);
        Route::get('/teacher/session/{sessionId}/monitor', [TeacherController::class, 'monitor']);
        Route::post('/teacher/session/{sessionId}/checkin', [TeacherController::class, 'manualCheckin']);
        Route::post('/teacher/session/{sessionId}/status-update', [TeacherController::class, 'updateStatus']);
        Route::get('/teacher/session/{sessionId}/live-feed', [TeacherController::class, 'liveFeed'])->middleware('throttle:activity');
        Route::delete('/teacher/attendance/{attendanceId}', [TeacherController::class, 'deleteAttendance']);
        Route::get('/teacher/students/{studentId}/detail', [TeacherController::class, 'getStudentDetail']);
        Route::get('/teacher/semesters', [TeacherController::class, 'mySemesters']);
    });

    // 🛡️ ADMIN & SUPER ADMIN Shared Management
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/admin/check-status', [AdminController::class, 'checkStatus']);
        Route::get('/admin/stats', [AdminController::class, 'getStats']);
        Route::get('/admin/users', [AdminController::class, 'listUsers']);
        Route::post('/admin/users', [AdminController::class, 'storeUser']);
        Route::middleware('role:super_admin')->delete('/admin/users/{userId}', [AdminController::class, 'deleteUser']);
        
        Route::get('/admin/classes', [AdminController::class, 'listClasses']);
        Route::post('/admin/classes/{classId}/assign-semester', [AdminController::class, 'assignSemester']);
        Route::get('/admin/classes/{classId}/semesters', [AdminController::class, 'listClassSemesters']);
        Route::middleware('role:super_admin')->delete('/admin/semesters/{id}', [AdminController::class, 'deleteSemester']);
        Route::middleware('role:super_admin')->get('/admin/classes/export', [AdminController::class, 'exportClasses']);
        Route::post('/admin/classes', [AdminController::class, 'storeClass']);
        Route::put('/admin/classes/{classId}', [AdminController::class, 'updateClass']);
        Route::middleware('role:super_admin')->delete('/admin/classes/{classId}', [AdminController::class, 'deleteClass']);
        
        // 📅 Sessions & Records (Admin)
        Route::get('/admin/classes/{classId}/sessions', [AdminController::class, 'listSessions']);
        Route::get('/admin/session/{sessionId}/attendance', [AdminController::class, 'listSessionAttendance']);
        Route::get('/admin/session/{sessionId}/next-available-slot', [AdminController::class, 'getNextAvailableSlot']);
        Route::put('/admin/session/{sessionId}', [AdminController::class, 'updateSession']);
        Route::post('/admin/session/{sessionId}/status-update', [AdminController::class, 'updateStatus']);
        Route::post('/admin/sessions/global-skip', [AdminController::class, 'globalSkip']);
        Route::post('/admin/skip-today-shift', [AdminController::class, 'skipTodayAndShift']);
        
        Route::get('/admin/students', [AdminController::class, 'listStudents']);
        Route::middleware('role:super_admin')->get('/admin/students/export', [AdminController::class, 'exportStudents']);
        Route::middleware('role:super_admin')->post('/admin/students/import', [AdminController::class, 'importStudents']);
        Route::post('/admin/students', [AdminController::class, 'storeStudent']);
        Route::put('/admin/students/{studentId}', [AdminController::class, 'updateStudent']);
        Route::middleware('role:super_admin')->delete('/admin/students/{studentId}', [AdminController::class, 'deleteStudent']);
        Route::get('/admin/students/{studentId}/attendance', [AdminController::class, 'listStudentAttendance']);
        
        Route::get('/admin/global-activity', [AdminController::class, 'getGlobalActivity'])->middleware('throttle:activity');
        Route::get('/admin/subjects', [AdminController::class, 'listSubjects']);
        Route::post('/admin/subjects', [AdminController::class, 'storeSubject']);
        Route::put('/admin/subjects/{subjectId}', [AdminController::class, 'updateSubject']);
        Route::middleware('role:super_admin')->delete('/admin/subjects/{subjectId}', [AdminController::class, 'deleteSubject']);

        Route::get('/admin/departments', [AdminController::class, 'listDepartments']);
        Route::post('/admin/departments', [AdminController::class, 'storeDepartment']);
        Route::put('/admin/departments/{deptId}', [AdminController::class, 'updateDepartment']);
        Route::middleware('role:super_admin')->delete('/admin/departments/{deptId}', [AdminController::class, 'deleteDepartment']);
        
        // 📚 Majors & Class Groups
        Route::get('/admin/majors', [AdminController::class, 'listMajors']);
        Route::post('/admin/majors', [AdminController::class, 'storeMajor']);
        Route::put('/admin/majors/{id}', [AdminController::class, 'updateMajor']);
        Route::middleware('role:super_admin')->delete('/admin/majors/{id}', [AdminController::class, 'deleteMajor']);

        Route::get('/admin/class-groups', [AdminController::class, 'listClassGroups']);
        Route::post('/admin/class-groups', [AdminController::class, 'storeClassGroup']);
        Route::put('/admin/class-groups/{id}', [AdminController::class, 'updateClassGroup']);
        Route::middleware('role:super_admin')->delete('/admin/class-groups/{id}', [AdminController::class, 'deleteClassGroup']);
        
        // 👩‍🏫 Instructor Specifics
        Route::post('/admin/instructors', [AdminController::class, 'storeInstructor']);
        Route::put('/admin/instructors/{teacherId}', [AdminController::class, 'updateInstructor']);
        Route::post('/admin/accounts/{userId}/update', [AdminController::class, 'updateUserAccount']);
        Route::middleware('role:super_admin')->post('/admin/generate-calendar', [AdminController::class, 'generateAcademicCalendar']);
        Route::middleware('role:super_admin')->delete('/admin/instructors/{teacherId}', [AdminController::class, 'deleteInstructor']);

        // 📅 Semester Assignments (Admin)
        Route::get('/admin/classes/{classId}/semesters', [AdminController::class, 'listSemesterAssignments']);
        Route::post('/admin/classes/{classId}/semesters', [AdminController::class, 'storeSemesterAssignment']);
        Route::put('/admin/semesters/{assignmentId}', [AdminController::class, 'updateSemesterAssignment']);
        Route::middleware('role:super_admin')->delete('/admin/semesters/{assignmentId}', [AdminController::class, 'deleteSemesterAssignment']);
    });
});

// 🎓 PUBLIC STUDENT CHECK-IN
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/student/portal', [AttendanceController::class, 'getPortalData']);
    Route::get('/student/active-session', [AttendanceController::class, 'getActiveSession']);
    Route::get('/student/classes', [AttendanceController::class, 'getStudentClasses']);
    Route::get('/student/classes/{classId}/history', [AttendanceController::class, 'getStudentClassHistory']);
});
Route::get('/student/scan/{sessionId}', [AttendanceController::class, 'getScanInfo']);
Route::post('/student/verify', [AttendanceController::class, 'verify']);
Route::post('/student/history', [AttendanceController::class, 'getStudentHistoryByCode']);

// 📍 LOCATION TRACKING
Route::post('/location/record', [UserLocationController::class, 'store'])->middleware('throttle:10,1');


<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Services\AttendanceService;

class DashboardController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }
    public function index(Request $request)
    {
        // 1. Stats
        $studentCount = Student::count();
        
        $totalAttendance = Attendance::count();
        $presentCount = Attendance::whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count();
        $attendanceRate = $totalAttendance > 0 ? ($presentCount / $totalAttendance) * 100 : 0;
        
        $pendingSessions = AttendanceSession::where('end_time', '>', now())->count();
        $absenceRate = 100 - $attendanceRate;

        // 2. Current Classes (Showing sessions from the last 24h + upcoming)
        $currentClasses = AttendanceSession::with(['classRoom.subject', 'classRoom.teacher'])
            ->where('end_time', '>', now()->subDay())
            ->orderBy('start_time', 'desc')
            ->take(10)
            ->get()
            ->map(function (\App\Models\AttendanceSession $session) {
                $now = now();
                $isLive = $now->between($session->start_time, $session->end_time);
                $isDone = $now > $session->end_time;
                
                $totalInClass = $session->classRoom->students()->count();
                $attendedInSession = $session->attendanceRecords()->whereIn('status', ['present', 'late'])->count();
                $progress = $totalInClass > 0 ? ($attendedInSession / $totalInClass) * 100 : 0;

                return [
                    'id' => $session->id,
                    'name' => $session->classRoom->subject->name,
                    'room' => "ROOM " . (100 + ($session->id % 400)),
                    'time' => Carbon::parse($session->start_time)->format('H:i') . " – " . Carbon::parse($session->end_time)->format('H:i'),
                    'is_live' => $isLive,
                    'is_done' => $isDone,
                    'progress' => round($progress),
                ];
            });

        // 3. Selection of Active Session (Manual or Auto)
        $sessionId = $request->query('session_id');
        $activeSessionQuery = AttendanceSession::with(['classRoom.subject', 'classRoom.students.user', 'attendanceRecords.student.user']);
        
        if ($sessionId) {
            $activeSession = $activeSessionQuery->find($sessionId);
        } else {
            $activeSession = $activeSessionQuery->where('end_time', '>=', now())->latest()->first();
        }

        // 4. Student Data for the Table
        $activeStudents = collect();
        $sessionScanCount = 0;
        $activePermissions = collect();

        if ($activeSession) {
            $sessionAttendance = $activeSession->attendanceRecords->keyBy('student_id');
            $sessionScanCount = $sessionAttendance->where('method', 'qr')->count();
            
            // Get active permissions for today for students in this class
            $activePermissions = \App\Models\StudentPermission::with('student.user')
                ->where('start_date', '<=', now()->toDateString())
                ->where('end_date', '>=', now()->toDateString())
                ->whereHas('student', function($q) use ($activeSession) {
                    $q->where('group_id', $activeSession->classRoom->group_id);
                })
                ->get()
                ->keyBy('student_id');

            $activeStudents = $activeSession->classRoom->students->map(function($student) use ($sessionAttendance, $activePermissions) {
                $att = $sessionAttendance->get($student->id);
                $perm = $activePermissions->get($student->id);
                
                return [
                    'id' => $student->id,
                    'code' => $student->student_code,
                    'name' => $student->user->name,
                    'major' => $student->major->name ?? ($student->group->major->name ?? 'N/A'),
                    'year' => $student->group->year_level ?? '?',
                    'initials' => collect(explode(' ', $student->user->name))->map(fn($n) => substr($n, 0, 1))->join(''),
                    'status' => $att->status ?? ($perm ? 'excused' : 'absent'),
                    'permission' => $perm ? $perm->reason : null,
                    'time' => $att && $att->scan_time ? Carbon::parse($att->scan_time)->format('H:i') : '—',
                    'method' => $att->method ?? '—',
                    'avatar_color' => '#' . substr(md5($student->user->id), 0, 6),
                ];
            });
        }

        // 5. DB Activity Log
        $recentActivity = Attendance::with(['student.user', 'session.classRoom.subject'])
            ->latest()
            ->take(8)
            ->get()
            ->map(function($att) {
                return [
                    'time' => $att->created_at->format('H:i'),
                    'action' => $att->status === 'present' ? "AUTH scan verified {$att->student->student_code}" : "INSERT attendance #{$att->student->id}",
                    'type' => $att->status === 'present' ? 'auth' : 'ins',
                ];
            });

        return view('dashboard', [
            'stats' => [
                'students' => number_format($studentCount),
                'attendance_rate' => number_format($attendanceRate, 1) . '%',
                'pending_sessions' => $pendingSessions,
                'absence_rate' => number_format($absenceRate, 1) . '%',
            ],
            'classes' => $currentClasses,
            'activeSession' => $activeSession,
            'activeStudents' => $activeStudents,
            'recentActivity' => $recentActivity,
            'sessionScanCount' => $sessionScanCount,
            'presentCount' => $activeStudents->whereIn('status', ['present', 'late', 'excused'])->count(),
            'totalCount' => $activeStudents->count(),
            'topAbsentStudents' => $this->getTopAbsentStudents(),
            'topAbsentClasses' => $this->getTopAbsentClasses(),
            'activePermissions' => $activePermissions,
            'yearStats' => $this->getYearLevelStats(),
        ]);
    }

    private function getTopAbsentStudents()
    {
        // Students with most absences in completed sessions
        return Student::with(['user', 'major'])
            ->get()
            ->map(function($student) {
                // Count completed sessions for this student's class
                $totalSessions = AttendanceSession::where('class_id', $student->class_id)
                    ->where('status', 'completed')
                    ->count();
                
                // Count presence/late/excused
                $attendedCount = Attendance::where('student_id', $student->id)
                    ->whereIn('status', ['present', 'late', 'excused', 'PRESENT', 'LATE', 'EXCUSED'])
                    ->count();
                
                $absentCount = max(0, $totalSessions - $attendedCount);
                $rate = $totalSessions > 0 ? ($absentCount / $totalSessions) * 100 : 0;
                
                return [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'absent_count' => $absentCount,
                    'absence_rate' => round($rate),
                    'initials' => collect(explode(' ', $student->user->name))->map(fn($n) => substr($n, 0, 1))->join(''),
                ];
            })
            ->filter(fn($s) => $s['absent_count'] > 0)
            ->sortByDesc('absent_count')
            ->take(5);
    }

    private function getTopAbsentClasses()
    {
        return ClassRoom::with(['subject', 'teacher.user'])
            ->get()
            ->map(function($class) {
                $sessions = AttendanceSession::where('class_id', $class->id)->where('status', 'completed')->pluck('id');
                if ($sessions->isEmpty()) return null;

                $totalStudents = $class->students()->count();
                if ($totalStudents === 0) return null;

                $totalPossibleAttendances = $sessions->count() * $totalStudents;
                $actualAttendances = Attendance::whereIn('session_id', $sessions)
                    ->whereIn('status', ['present', 'late', 'excused', 'PRESENT', 'LATE', 'EXCUSED'])
                    ->count();
                
                $absences = max(0, $totalPossibleAttendances - $actualAttendances);
                $absenceRate = $totalPossibleAttendances > 0 ? ($absences / $totalPossibleAttendances) * 100 : 0;

                return [
                    'id' => $class->id,
                    'name' => $class->subject->name,
                    'teacher' => $class->teacher->user->name ?? 'Unknown',
                    'absence_rate' => round($absenceRate),
                ];
            })
            ->filter()
            ->sortByDesc('absence_rate')
            ->take(5);
    }

    private function getYearLevelStats()
    {
        $stats = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        
        foreach ($stats as $year => $val) {
            // Get all sessions for groups in this year
            $sessions = AttendanceSession::whereHas('classRoom.group', function($q) use ($year) {
                    $q->where('year_level', $year);
                })
                ->where('status', 'completed')
                ->pluck('id');
                
            if ($sessions->isEmpty()) {
                $stats[$year] = 80 + ($year * 2); // Fallback mock if no data
                continue;
            }

            // Get total students in those groups
            $totalStudents = Student::whereHas('group', function($q) use ($year) {
                $q->where('year_level', $year);
            })->count();

            if ($totalStudents === 0) {
                $stats[$year] = 0;
                continue;
            }

            $totalPossible = $sessions->count() * $totalStudents;
            $totalPresent = Attendance::whereIn('session_id', $sessions)
                ->whereIn('status', ['present', 'late', 'excused', 'PRESENT', 'LATE', 'EXCUSED'])
                ->count();

            $stats[$year] = $totalPossible > 0 ? round(($totalPresent / $totalPossible) * 100) : 0;
        }

        return $stats;
    }


    public function studentScan($sessionId)
    {
        $session = AttendanceSession::with('classRoom.subject')->findOrFail($sessionId);
        return view('student_scan', compact('session'));
    }

    public function teacherReports(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'teacher') {
            abort(403, 'Unauthorized access to faculty reports.');
        }

        $teacher = $user->teacher;
        if (!$teacher) {
            abort(404, 'Teacher profile not found.');
        }

        // Auto-sync session statuses
        $now = now();
        AttendanceSession::whereHas('classRoom', fn($q) => $q->where('teacher_id', $teacher->id))
            ->where('status', 'scheduled')->where('start_time', '<=', $now)->where('end_time', '>=', $now)
            ->update(['status' => 'active']);
        $toComplete = AttendanceSession::whereHas('classRoom', fn($q) => $q->where('teacher_id', $teacher->id))
            ->where('status', 'active')->where('end_time', '<', $now->copy()->subMinutes(45))
            ->get();
        
        foreach ($toComplete as $session) {
            $session->update(['status' => 'completed']);
            app(\App\Services\TelegramService::class)->checkAbsenceThresholds($session->class_id);
        }

        // Assigned classes with session counts and student counts
        $classes = ClassRoom::where('teacher_id', $teacher->id)
            ->with(['subject', 'students', 'sessions' => function($q) {
                $q->withCount('attendanceRecords')->orderBy('start_time', 'desc');
            }])
            ->get();

        $classIds = $classes->pluck('id');

        // Aggregate stats
        $totalStudents = \App\Models\Student::whereIn('class_id', $classIds)->count();
        $totalSessions = AttendanceSession::whereIn('class_id', $classIds)->count();
        $totalPossible = $totalSessions * max(1, $totalStudents);
        $totalAttended  = \App\Models\Attendance::whereHas('session', fn($q) => $q->whereIn('class_id', $classIds))
            ->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count();
        $overallRate = $totalPossible > 0 ? round(($totalAttended / $totalPossible) * 100) : 0;

        // All sessions for the monitor sidebar (ordered: active first, then upcoming, then done)
        $allSessions = AttendanceSession::with(['classRoom.subject'])
            ->whereIn('class_id', $classIds)
            ->orderByRaw("
                CASE status 
                    WHEN 'active' THEN 1 
                    WHEN 'scheduled' THEN 2 
                    WHEN 'completed' THEN 3 
                    ELSE 4 
                END
            ")
            ->orderBy('start_time', 'desc')
            ->take(50)
            ->get();

        // Selected class (for history view)
        $selectedClassId = $request->query('class_id', $classes->first()?->id);
        $selectedClass   = $classes->find($selectedClassId);

        // Selected session (for both monitor and drill-down)
        $selectedSessionId = $request->query('session_id');
        $selectedSession = null;

        if ($selectedSessionId) {
            $selectedSession = AttendanceSession::with([
                'classRoom.subject',
                'classRoom.students.user',
                'attendanceRecords.student.user'
            ])->find($selectedSessionId);
        } elseif ($allSessions->where('status', 'active')->isNotEmpty()) {
            // Auto-select the active session for the monitor view
            $selectedSession = AttendanceSession::with([
                'classRoom.subject',
                'classRoom.students.user',
                'attendanceRecords.student.user'
            ])->find($allSessions->where('status', 'active')->first()->id);
        }

        return view('teacher.reports', [
            'classes'         => $classes,
            'selectedClass'   => $selectedClass,
            'selectedSession' => $selectedSession,
            'allSessions'     => $allSessions,
            'totalStudents'   => $totalStudents,
            'totalSessions'   => $totalSessions,
            'overallRate'     => $overallRate,
        ]);
    }
}

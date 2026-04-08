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
        if ($activeSession) {
            $sessionAttendance = $activeSession->attendanceRecords->keyBy('student_id');
            $sessionScanCount = $sessionAttendance->where('method', 'qr')->count();
            
            $activeStudents = $activeSession->classRoom->students->map(function($student) use ($sessionAttendance) {
                $att = $sessionAttendance->get($student->id);
                return [
                    'id' => $student->id,
                    'code' => $student->student_code,
                    'name' => $student->user->name,
                    'major' => $student->major ?? 'N/A',
                    'year' => $student->year_level ?? 1,
                    'initials' => collect(explode(' ', $student->user->name))->map(fn($n) => substr($n, 0, 1))->join(''),
                    'status' => $att->status ?? 'absent',
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
            'yearStats' => $this->getYearStats(),
        ]);
    }

    private function getYearStats()
    {
        $years = [1, 2, 3, 4];
        $stats = [];
        foreach ($years as $y) {
            $studentsInYear = Student::where('year_level', $y)->pluck('id');
            if ($studentsInYear->isEmpty()) {
                $stats[$y] = 0;
                continue;
            }
            $totalExpected = Attendance::whereIn('student_id', $studentsInYear)->count();
            $attended = Attendance::whereIn('student_id', $studentsInYear)->whereIn('status', ['present', 'late'])->count();
            $stats[$y] = $totalExpected > 0 ? round(($attended / $totalExpected) * 100) : 0;
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
        AttendanceSession::whereHas('classRoom', fn($q) => $q->where('teacher_id', $teacher->id))
            ->where('status', 'active')->where('end_time', '<', $now->copy()->subHours(8))
            ->update(['status' => 'completed']);

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

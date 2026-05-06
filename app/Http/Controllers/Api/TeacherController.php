<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AttendanceSession;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\SemesterAssignment;
use Carbon\Carbon;
use App\Services\TelegramService;

class TeacherController extends Controller
{
    protected $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }
    private function syncSessionStatuses($teacherId)
    {
        $now = now();
        
        // 1. Scheduled -> Active (Auto-start if time matches)
        AttendanceSession::whereHas('classRoom', fn($q) => $q->where('teacher_id', $teacherId))
            ->where('status', 'scheduled')
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->update(['status' => 'active']);

        // 2. Scheduled -> Completed (If time passed and it never started)
        AttendanceSession::whereHas('classRoom', fn($q) => $q->where('teacher_id', $teacherId))
            ->where('status', 'scheduled')
            ->where(function($q) use ($now) {
                // If there's a close time, wait for it. Otherwise, end_time + 20.
                $q->whereNotNull('checkin_close_time')->where('checkin_close_time', '<', $now)
                  ->orWhereNull('checkin_close_time')->where('end_time', '<', $now->subMinutes(20));
            })
            ->update(['status' => 'completed']);

        // 3. Active -> Completed (Only if time passed by a reasonable buffer, e.g. 45 minutes)
        // This allows teachers to finish their class and get the report promptly.
        AttendanceSession::whereHas('classRoom', fn($q) => $q->where('teacher_id', $teacherId))
            ->where('status', 'active')
            ->where('end_time', '<', $now->subMinutes(45))
            ->update(['status' => 'completed']);

        // 4. Auto-send Telegram for newly completed sessions
        $pendingReports = AttendanceSession::whereHas('classRoom', fn($q) => $q->where('teacher_id', $teacherId))
            ->where('status', 'completed')
            ->where('telegram_sent', false)
            ->get();

        foreach ($pendingReports as $session) {
            $this->telegram->sendAttendanceReport($session->id);
        }
    }

    public function getSessions(Request $request)
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) return response()->json(['error' => 'Teacher record not found'], 404);

        $this->syncSessionStatuses($teacher->id);

        $query = AttendanceSession::with(['classRoom.subject'])
            ->whereHas('classRoom', fn($q) => $q->where('teacher_id', $teacher->id));

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        } else {
            // Default: Fetch active, upcoming (48h), and completed
            $query->where(function ($q) {
                $q->where('status', 'active')
                  ->orWhere(function ($sq) {
                      $sq->where('status', 'scheduled')
                        ->where('start_time', '<=', now()->addDays(2));
                  })
                  ->orWhere('status', 'completed');
            });
        }

        $sessions = $query->orderByRaw("
                CASE status 
                    WHEN 'active' THEN 1 
                    WHEN 'scheduled' THEN 2 
                    WHEN 'completed' THEN 3 
                    ELSE 4 
                END
            ")
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json($sessions->map(fn($s) => $this->formatSession($s)));
    }

    public function getSessionsByClass(Request $request, $classId)
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) return response()->json(['error' => 'Teacher record not found'], 404);

        $this->syncSessionStatuses($teacher->id);

        $sessions = AttendanceSession::with(['classRoom.subject'])
            ->where('class_id', $classId)
            ->whereHas('classRoom', fn($q) => $q->where('teacher_id', $teacher->id))
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json($sessions->map(fn($s) => $this->formatSession($s)));
    }

    private function formatSession($session)
    {
        return [
            'id' => $session->id,
            'class_id' => $session->class_id,
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'status' => $session->status,
            'room' => $session->classRoom->room_number ?? 'TBD',
            'subject' => [
                'name' => $session->classRoom->subject->name,
                'code' => $session->classRoom->subject->code,
            ],
            'presence_count' => Attendance::where('session_id', $session->id)->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count(),
            'total_students_count' => $session->classRoom->group_id ? \App\Models\Student::where('group_id', $session->classRoom->group_id)->count() : 0,
        ];
    }

    public function monitor($sessionId)
    {
        $session = AttendanceSession::with('classRoom.subject')->findOrFail($sessionId);
        $attendances = Attendance::where('session_id', $sessionId)->get()->keyBy('student_id');
        $allStudents = collect();
        if ($session->classRoom && $session->classRoom->group_id) {
            $allStudents = \App\Models\Student::where('group_id', $session->classRoom->group_id)->get();
        }

        $sessionsCount = AttendanceSession::where('class_id', $session->class_id)->count();

        $rows = $allStudents->map(function ($student) use ($attendances) {
            $att = $attendances->get($student->id);
            $names = explode(' ', $student->name);
            $initials = (isset($names[0]) ? substr($names[0], 0, 1) : '') . (isset($names[1]) ? substr($names[1], 0, 1) : '');
            
            return [
                'id' => $student->id,
                'attendance_id' => $att?->id,
                'initials' => strtoupper($initials),
                'name' => $student->name,
                'student_code' => $student->student_code,
                'status' => $att ? strtoupper($att->status) : 'ABSENT',
                'check_in_time' => $att && $att->scan_time ? Carbon::parse($att->scan_time)->format('H:i') : '—',
                'method' => $att ? strtoupper($att->method) : '—',
                'avatar_color' => '#' . substr(md5($student->user_id), 0, 6)
            ];
        });

        return response()->json([
            'session_name' => $session->classRoom->subject->name,
            'sessions_count' => $sessionsCount,
            'present_count' => $attendances->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count(),
            'total_count' => $allStudents->count(),
            'data' => $rows
        ]);
    }

    public function generateQr(Request $request, $sessionId)
    {
        $session = AttendanceSession::findOrFail($sessionId);
        $user = $request->user();
        
        // 🔒 SECURITY: For University-grade (HRU) security, we can rotate the token
        // to prevent students from sharing static photos of the QR code.
        $now = now();
        $open = Carbon::parse($session->checkin_open_time);
        $close = $session->checkin_close_time ? Carbon::parse($session->checkin_close_time) : Carbon::parse($session->end_time)->addMinutes(20);

        if ($session->status !== 'active') {
            if ($now->lt($open) || $now->gt($close)) {
                 return response()->json(['success' => false, 'message' => 'Attendance window closed.'], 403);
            }
        }

        // Rotate token (Dynamic QR to prevent photo sharing)
        $session->update(['qr_token' => bin2hex(random_bytes(8))]);

        return response()->json([
            'success'   => true,
            'qr_token'  => $session->qr_token,
            'scan_url'  => url("/scan/{$session->id}"),
            'expires_at' => $close->format('H:i'),
            'refresh_in' => 60 // Client should re-fetch every 60s
        ]);
    }

    public function manualCheckin(Request $request, $sessionId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'status'     => 'sometimes|in:present,late,absent'
        ]);
 
        $user = $request->user();
        $session = AttendanceSession::findOrFail($sessionId);
        $status  = $request->get('status', 'present');
 
        if ($status === 'absent') {
            Attendance::where('student_id', $request->student_id)->where('session_id', $sessionId)->delete();
            return response()->json(['success' => true, 'message' => 'Attendance removed (Manual Clear).']);
        }
        
        $attendance = Attendance::updateOrCreate(
            ['student_id' => $request->student_id, 'session_id' => $sessionId],
            [
                'status'    => $status,
                'scan_time' => now(),
                'method'    => 'manual'
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Student marked as ' . strtoupper($status) . ' manually.',
            'attendance' => $attendance
        ]);
    }

    /**
     * Delete/Reset attendance record
     */
    public function deleteAttendance($attendanceId)
    {
        $attendance = Attendance::findOrFail($attendanceId);
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance record removed.'
        ]);
    }


    /**
     * Get summary stats for the teacher
     */
    public function getSummary(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'teacher') return response()->json(['error' => 'Forbidden'], 403);

        $teacher = \App\Models\Teacher::where('user_id', $user->id)->first();
        if (!$teacher) return response()->json(['error' => 'Teacher not found'], 404);

        $this->syncSessionStatuses($teacher->id);

        $classes = \App\Models\ClassRoom::where('teacher_id', $teacher->id)->get();
        $classIds = $classes->pluck('id');
        $sessions = AttendanceSession::whereIn('class_id', $classIds)->get();

        $totalStudents = 0;
        $totalPossible = 0;
        foreach ($classes as $class) {
            if ($class->group_id) {
                $count = \App\Models\Student::where('group_id', $class->group_id)->count();
                $totalStudents += $count;
                $totalPossible += $count * $sessions->where('class_id', $class->id)->count();
            }
        }
        $totalAttendance = Attendance::whereIn('session_id', $sessions->pluck('id'))->count();
        $rate = $totalPossible > 0 ? round(($totalAttendance / $totalPossible) * 100) : 0;

        return response()->json([
            'teacher' => $teacher->user->name,
            'total_classes' => $classes->count(),
            'total_students' => $totalStudents,
            'total_sessions' => $sessions->count(),
            'total_scans' => $totalAttendance,
            'attendance_rate' => $rate,
            'active_sessions' => $sessions->where('status', 'active')->count(),
        ]);
    }

    /**
     * List all students in teacher's classes with attendance stats
     */
    public function getStudents(Request $request)
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) return response()->json(['error' => 'Teacher not found'], 404);

        $classes = \App\Models\ClassRoom::where('teacher_id', $teacher->id)->get();
        $groupIds = $classes->pluck('group_id')->filter()->unique();
        $students = \App\Models\Student::with(['user', 'group', 'major.department'])
            ->whereIn('group_id', $groupIds)
            ->get();

        return response()->json($students->map(function ($student) use ($classes) {
            $myClassIds = $classes->where('group_id', $student->group_id)->pluck('id');
            $totalSessions = \App\Models\AttendanceSession::whereIn('class_id', $myClassIds)->count();
            $attended = \App\Models\Attendance::where('student_id', $student->id)
                ->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])
                ->count();
            
            $percentage = $totalSessions > 0 ? round(($attended / $totalSessions) * 100) : 0;
            
            return [
                'id' => $student->id,
                'name' => $student->user->name ?? $student->name ?? 'Unknown',
                'student_code' => $student->student_code,
                'attendance_percentage' => $percentage,
                'status' => $percentage > 85 ? 'Excellent' : ($percentage > 70 ? 'Good' : 'Warning'),
                'group' => [
                    'id' => $student->group_id,
                    'name' => $student->group->name ?? 'Unknown',
                ],
                'major' => [
                    'id' => $student->major_id,
                    'name' => $student->major->name ?? 'Unknown',
                ],
                'department' => [
                    'name' => $student->major->department->name ?? 'Unknown',
                ]
            ];
        }));
    }

    /**
     * List all classes assigned to teacher
     */
    public function getClasses(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'teacher') return response()->json(['error' => 'Forbidden'], 403);

        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) return response()->json(['error' => 'Teacher record not found'], 404);

        $classes = \App\Models\ClassRoom::with('subject')
            ->where('teacher_id', $teacher->id)
            ->get();

        $classes->transform(function ($class) {
            $sessions = AttendanceSession::where('class_id', $class->id)->get();
            $sessionsCount = $sessions->count();
            $totalStudents = $class->group_id ? \App\Models\Student::where('group_id', $class->group_id)->count() : 0;
            
            $attended = Attendance::whereIn('session_id', $sessions->pluck('id'))->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count();
            $totalPossible = $sessionsCount * $totalStudents;
            $efficacy = $totalPossible > 0 ? round(($attended / $totalPossible) * 100) : 0;

            return [
                'id' => $class->id,
                'name' => $class->subject->name ?? 'N/A',
                'code' => $class->subject->code ?? 'N/A',
                'room' => $class->room_number,
                'schedule' => $class->schedule,
                'sessions_count' => $sessionsCount,
                'total_students_count' => $totalStudents,
                'presence_count' => $attended,
                'efficacy' => $efficacy
            ];
        });

        return response()->json($classes);
    }

    /**
     * Regenerate QR Token for a session
     */
    public function regenerateQr(Request $request, $sessionId)
    {
        $session = AttendanceSession::findOrFail($sessionId);
        
        $newToken = bin2hex(random_bytes(8));
        $session->update(['qr_token' => $newToken]);

        return response()->json([
            'success' => true,
            'qr_token' => $newToken,
            'scan_url' => url("/scan/{$session->id}"),
            'expires_at' => Carbon::parse($session->end_time)->format('H:i')
        ]);
    }

    /**
     * Update session status manually (Teacher override)
     */
    public function updateStatus(Request $request, $sessionId)
    {
        $request->validate(['status' => 'required|in:active,scheduled,completed,passed,skipped']);
        
        $session = AttendanceSession::findOrFail($sessionId);
        
        $status = $request->status;
        if ($status === 'passed') $status = 'completed';

        $data = ['status' => $status];
        
        // 🚀 Logic Fix: If manually activating a session that is technically past its end time,
        // we extend the end time by 60 minutes. This prevents the auto-sync logic
        // from immediately switching it back to 'completed' on the next refresh.
        if ($status === 'active' && now()->gt($session->end_time)) {
             $data['end_time'] = now()->addMinutes(60);
        }

        $session->update($data);

        // Notify Telegram if completed
        if ($status === 'completed') {
            $this->telegram->sendAttendanceReport($session->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Session status updated to ' . $status . ($status === 'completed' ? ' and report sent to Telegram.' : ''),
            'session' => $session
        ]);
    }
    /**
     * Get the current teacher's semester assignments
     */
    public function mySemesters(Request $request)
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) return response()->json(['error' => 'Teacher not found'], 404);

        // Uses the newly added hasManyThrough relation
        $assignments = $teacher->semesterAssignments()
            ->with('classRoom.subject')
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'asc')
            ->get()
            ->map(function (SemesterAssignment $a) {
                $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : null;
                return [
                    'id'            => $a->id,
                    'academic_year' => $a->academic_year,
                    'semester'      => $a->semester,
                    'start_date'    => $fmt($a->start_date),
                    'end_date'      => $fmt($a->end_date),
                    'holiday_start' => $fmt($a->holiday_start),
                    'holiday_end'   => $fmt($a->holiday_end),
                    'status'        => $a->status,
                    'notes'         => $a->notes,
                    'progress'      => $a->progress, // Model accessor
                    'active_days'   => $a->active_days, // Model accessor
                    'in_holiday'    => $a->in_holiday, // Model accessor
                    'class_name'    => $a->classRoom->subject->name ?? 'Unknown',
                ];
            });

        return response()->json(['success' => true, 'data' => $assignments]);
    }

    /**
     * Get detailed student attendance history for teacher
     */
    public function getStudentDetail(Request $request, $studentId)
    {
        $user = $request->user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) return response()->json(['error' => 'Teacher record not found'], 404);

        $student = \App\Models\Student::findOrFail($studentId);
        
        // Ensure student is in one of teacher's class groups
        $isMyStudent = \App\Models\ClassRoom::where('group_id', $student->group_id)
            ->where('teacher_id', $teacher->id)
            ->exists();
            
        if (!$isMyStudent) return response()->json(['error' => 'Unauthorized'], 403);

        $sessions = AttendanceSession::whereHas('classRoom', function($q) use ($student) {
                $q->where('group_id', $student->group_id);
            })
            ->with('classRoom.subject')
            ->orderBy('id', 'desc')
            ->get();

        $attendance = Attendance::where('student_id', $student->id)->get()->keyBy('session_id');

        $history = $sessions->map(function ($session) use ($attendance) {
            $record = $attendance->get($session->id);
            $status = 'ABSENT';
            $isFuture = Carbon::parse($session->start_time)->isFuture();

            if ($record) {
                $status = strtoupper($record->status);
            } elseif ($session->status === 'scheduled' || $isFuture) {
                $status = 'SCHEDULED';
            }

            return [
                'session_id' => $session->id,
                'subject' => $session->classRoom->subject->name ?? 'N/A',
                'date' => $session->start_time,
                'status' => $status,
                'scan_time' => $record ? Carbon::parse($record->scan_time)->format('H:i') : null,
                'method' => $record ? strtoupper($record->method) : null,
            ];
        });

        $totalSessions = $sessions->count();
        $attendedCount = $attendance->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count();
        $rate = $totalSessions > 0 ? round(($attendedCount / $totalSessions) * 100) : 0;

        return response()->json([
            'success' => true,
            'student' => $student->load(['user', 'group', 'major.department']),
            'stats' => [
                'total_sessions' => $totalSessions,
                'attended_count' => $attendedCount,
                'absent_count' => $totalSessions - $attendedCount,
                'attendance_rate' => $rate,
            ],
            'history' => $history
        ]);
    }

    /**
     * Live Attendance Feed for real-time monitoring
     */
    public function liveFeed(Request $request, $sessionId)
    {
        $lastId = $request->get('last_id', 0);
        $session = AttendanceSession::findOrFail($sessionId);
        
        $newRecords = Attendance::with('student.user')
            ->where('session_id', $sessionId)
            ->where('id', '>', $lastId)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function($att) {
                return [
                    'id' => $att->id,
                    'student_name' => $att->student->user->name ?? 'Unknown',
                    'student_code' => $att->student->student_code,
                    'status' => strtoupper($att->status),
                    'time' => Carbon::parse($att->scan_time)->format('H:i:s'),
                    'method' => strtoupper($att->method),
                ];
            });

        $stats = [
            'present_count' => Attendance::where('session_id', $sessionId)->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count(),
            'total_students' => $session->classRoom->group_id ? \App\Models\Student::where('group_id', $session->classRoom->group_id)->count() : 0,
        ];

        return response()->json([
            'success' => true,
            'new_records' => $newRecords,
            'stats' => $stats
        ]);
    }
}

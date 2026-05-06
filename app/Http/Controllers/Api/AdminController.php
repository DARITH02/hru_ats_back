<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Department;
use App\Models\AttendanceSession;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\SemesterAssignment;
use App\Models\Major;
use App\Models\ClassGroup;
use Illuminate\Support\Facades\Hash;
use DB;

class AdminController extends Controller
{
    public function checkStatus()
    {
        return response()->json(['status' => 'OK', 'controller' => 'Api\AdminController', 'time' => now()]);
    }

    public function getStats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_teachers' => Teacher::count(),
            'total_students' => Student::count(),
            'total_classes' => ClassRoom::count(),
            'total_sessions' => AttendanceSession::count(),
            'total_scans' => Attendance::count(),
            'attendance_rate' => $this->getGlobalAttendanceRate(),
            'active_sessions_now' => AttendanceSession::where('status', 'active')->count()
        ]);
    }

    private function getGlobalAttendanceRate()
    {
        $sessions = AttendanceSession::with('classRoom')
            ->where('status', '!=', 'skipped')
            ->get();
        $totalPossible = 0;
        foreach ($sessions as $s) {
            if ($s->classRoom && $s->classRoom->group_id) {
                $totalPossible += Student::where('group_id', $s->classRoom->group_id)->count();
            }
        }
        $scans = Attendance::count();
        return $totalPossible === 0 ? 0 : round(($scans / $totalPossible) * 100, 1);
    }

    public function listUsers()
    {
        return response()->json(User::orderBy('created_at', 'desc')->get());
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,teacher,student'
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);
            if ($request->role === 'teacher') {
                Teacher::create(['user_id' => $user->id, 'name' => $user->name]);
            }
            DB::commit();
            return response()->json(['success' => true, 'user' => $user]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();
        return response()->json(['success' => true]);
    }

    // ── CLASS GROUPS (BATCHES) ─────────────────────────
    public function listClassGroups()
    {
        return response()->json(ClassGroup::with(['major.department'])->get());
    }

    public function storeClassGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:class_groups,name',
            'major_id' => 'required|exists:majors,id',
            'year_level' => 'required|integer|min:1|max:5'
        ]);

        $group = ClassGroup::create($request->all());
        return response()->json(['success' => true, 'group' => $group->load('major.department')]);
    }

    public function updateClassGroup(Request $request, $id)
    {
        $group = ClassGroup::findOrFail($id);
        $group->update($request->all());
        return response()->json(['success' => true, 'group' => $group->load('major.department')]);
    }

    public function deleteClassGroup($id)
    {
        $group = ClassGroup::findOrFail($id);
        $group->delete();
        return response()->json(['success' => true]);
    }

    // ── MAJORS ─────────────────────────────────────────
    public function listMajors()
    {
        return response()->json(Major::with('department')->get());
    }

    public function storeMajor(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:majors,name',
            'department_id' => 'required|exists:departments,id'
        ]);

        $major = Major::create($request->all());
        return response()->json(['success' => true, 'major' => $major->load('department')]);
    }

    public function updateMajor(Request $request, $id)
    {
        $major = Major::findOrFail($id);
        $major->update($request->all());
        return response()->json(['success' => true, 'major' => $major->load('department')]);
    }

    public function deleteMajor($id)
    {
        $major = Major::findOrFail($id);
        $major->delete();
        return response()->json(['success' => true]);
    }

    public function listClasses()
    {
        return response()->json(ClassRoom::with(['subject', 'teacher.user'])->get());
    }

    public function assignSemester(Request $request, $classId)
    {
        $class = ClassRoom::findOrFail($classId);

        $request->validate([
            'academic_year'  => 'required',
            'semester'       => 'required',
            'start_date'     => 'required|date',
            'holiday_start'  => 'nullable|date',
            'sessions_count' => 'nullable|integer|min:1|max:100',
            'schedule_days'  => 'nullable|string',
            'time_start'     => 'nullable|string',
            'time_end'       => 'nullable|string',
            'time_start2'    => 'nullable|string',
            'time_end2'      => 'nullable|string',
        ]);

        $holidayStart = $request->holiday_start ? \Carbon\Carbon::parse($request->holiday_start) : null;
        $holidayEnd   = $holidayStart ? SemesterAssignment::computeHolidayEnd($holidayStart) : null;
        $endDate      = SemesterAssignment::computeEndDate($request->start_date);
        
        $sessionsTarget = $request->sessions_count ?? 30;

        DB::beginTransaction();
        try {
            // Update Class Schedule if new times/days provided
            if ($request->time_start && $request->time_end) {
                $daysPart = $request->schedule_days ?? 'Mon-Fri';
                
                $slots = [];
                $slots[] = "{$request->time_start}-{$request->time_end}";
                if ($request->time_start2 && $request->time_end2) {
                    $slots[] = "{$request->time_start2}-{$request->time_end2}";
                }
                
                $class->update([
                    'schedule' => "$daysPart (" . implode(', ', $slots) . ")",
                    'semester' => $request->semester,
                    'academic_year' => $request->academic_year
                ]);
                $class->refresh(); // CRITICAL: Refresh memory model to pick up the newly updated schedule string
            } else {
                $class->update([
                    'semester' => $request->semester,
                    'academic_year' => $request->academic_year
                ]);
            }

            $assignment = SemesterAssignment::create([
                'class_id'      => $classId,
                'academic_year' => $request->academic_year,
                'semester'      => $request->semester,
                'start_date'    => $request->start_date,
                'end_date'      => $endDate,
                'holiday_start' => $holidayStart,
                'holiday_end'   => $holidayEnd,
                'status'        => 'upcoming',
                'notes'         => $request->notes,
            ]);

            // GENERATE SESSIONS (UNIFIED LOGIC)
            $sessionsCreated = $this->generateAcademicSessions(
                $class,
                $assignment->academic_year,
                $assignment->semester,
                \Carbon\Carbon::parse($request->start_date),
                $request->sessions_count ?? 30,
                $holidayStart,
                $holidayEnd
            );

            DB::commit();
            return response()->json(['success' => true, 'assignment' => $assignment, 'sessions_count' => $sessionsCreated]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Unified Session Generator
     * Handles days parsing, time-slot parsing (multiple sessions per day),
     * holiday skipping, and target counting.
     */
    private function parseSchedule($schedStr)
    {
        $schedStr = strtolower($schedStr ?? '');
        if (!$schedStr) return [[], []];

        // 1. Parse Days
        $daysMap = ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6];
        $allowedDays = [];

        if (str_contains($schedStr, 'mon-fri') || str_contains($schedStr, 'weekday')) {
            $allowedDays = [1, 2, 3, 4, 5];
        } else if (str_contains($schedStr, 'sat/sun') || str_contains($schedStr, 'weekend')) {
            $allowedDays = [6, 0];
        } else if (str_contains($schedStr, 'everyday') || str_contains($schedStr, 'full-week')) {
            $allowedDays = [0, 1, 2, 3, 4, 5, 6];
        } else {
            if (preg_match('/(mon|tue|wed|thu|fri|sat|sun)\s?[-–—]\s?(mon|tue|wed|thu|fri|sat|sun)/i', $schedStr, $matches)) {
                $start = $daysMap[strtolower($matches[1])];
                $end = $daysMap[strtolower($matches[2])];
                if ($start <= $end) {
                    for ($i = $start; $i <= $end; $i++) $allowedDays[] = $i;
                } else {
                    for ($i = $start; $i <= 6; $i++) $allowedDays[] = $i;
                    for ($i = 0; $i <= $end; $i++) $allowedDays[] = $i;
                }
            } else {
                foreach ($daysMap as $dStr => $dNum) {
                    if (str_contains($schedStr, $dStr)) $allowedDays[] = $dNum;
                }
            }
        }
        if (empty($allowedDays)) $allowedDays = [1, 2, 3, 4, 5];

        // 2. Parse Time Slots
        preg_match_all('/(\d{1,2}:\d{2}(?::\d{2})?)\s?([AP]M)?\s?[-–—]\s?(\d{1,2}:\d{2}(?::\d{2})?)\s?([AP]M)?/i', $schedStr, $matches, PREG_SET_ORDER);
        $timeSlots = [];
        foreach ($matches as $m) {
            try {
                $timeSlots[] = [
                    'start' => \Carbon\Carbon::parse($m[1] . ($m[2] ?? ''))->format('H:i'),
                    'end'   => \Carbon\Carbon::parse($m[3] . ($m[4] ?? ''))->format('H:i')
                ];
            } catch (\Exception $e) { continue; }
        }

        return [$allowedDays, $timeSlots];
    }

    private function generateAcademicSessions($class, $year, $semester, $startDate, $targetCount, $holidayStart = null, $holidayEnd = null)
    {
        list($allowedDays, $timeSlots) = $this->parseSchedule($class->schedule);
        if (empty($timeSlots)) return 0;

        // 3. Clear existing scheduled (future) sessions
        \App\Models\AttendanceSession::where('class_id', $class->id)
            ->where('academic_year', $year)
            ->where('semester', (int)$semester)
            ->where('status', 'scheduled')
            ->delete();

        $currentDate = $startDate->copy();
        $created = 0;
        $maxIter = 400; // Safety break

        while ($created < $targetCount && $maxIter > 0) {
            $maxIter--;
            if (in_array($currentDate->dayOfWeek, $allowedDays)) {
                $inHoliday = false;
                if ($holidayStart && $holidayEnd) {
                    $inHoliday = $currentDate->between($holidayStart, $holidayEnd);
                }

                if (!$inHoliday) {
                    foreach ($timeSlots as $slot) {
                        if ($created >= $targetCount) break;

                        $sTime = $currentDate->copy()->setTimeFromTimeString($slot['start']);
                        $eTime = $currentDate->copy()->setTimeFromTimeString($slot['end']);
                        
                        \App\Models\AttendanceSession::create([
                            'class_id'           => $class->id,
                            'start_time'         => $sTime,
                            'end_time'           => $eTime,
                            'checkin_open_time'  => $sTime->copy()->subMinutes(20),
                            'checkin_close_time' => $sTime->copy()->addMinutes(20),
                            'semester'           => (int)$semester,
                            'academic_year'      => $year,
                            'status'             => $sTime->isPast() ? 'completed' : 'scheduled',
                            'qr_token'           => bin2hex(random_bytes(8))
                        ]);
                        $created++;
                    }
                }
            }
            $currentDate->addDay();
        }
        return $created;
    }

    public function listClassSemesters($classId)
    {
        return response()->json(SemesterAssignment::where('class_id', $classId)->orderBy('start_date', 'desc')->get());
    }

    public function deleteSemester($id)
    {
        $assignment = SemesterAssignment::findOrFail($id);
        
        DB::beginTransaction();
        try {
            // Purge all generated sessions for this class and semester
            \App\Models\AttendanceSession::where('class_id', $assignment->class_id)
                ->where('academic_year', $assignment->academic_year)
                ->where('semester', (int)$assignment->semester)
                ->delete();

            $assignment->delete();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeClass(Request $request)
    {
        $data = $request->all();
        if ($request->has('schedule_days') && $request->has('time_start')) {
            $data['schedule'] = $request->schedule_days . ' (' . $request->time_start . '-' . $request->time_end . ')';
        }
        $class = ClassRoom::create($data);
        return response()->json(['success' => true, 'class' => $class]);
    }

    public function updateClass(Request $request, $classId)
    {
        $class = ClassRoom::findOrFail($classId);
        $data = $request->all();
        if ($request->has('schedule_days') && $request->has('time_start')) {
            $data['schedule'] = $request->schedule_days . ' (' . $request->time_start . '-' . $request->time_end . ')';
        }
        $class->update($data);
        ActivityLog::create([
            'action' => 'UPDATE',
            'target' => "catalog.classes#{$classId}"
        ]);
        return response()->json(['success' => true, 'class' => $class]);
    }

    public function deleteClass($classId)
    {
        $class = ClassRoom::findOrFail($classId);
        $class->delete();
        ActivityLog::create([
            'action' => 'DELETE',
            'target' => "catalog.classes#{$classId}"
        ]);
        return response()->json(['success' => true]);
    }

    public function listSessions($classId)
    {
        $sessions = AttendanceSession::with(['classRoom.subject'])
            ->where('class_id', $classId)
            ->orderBy('start_time', 'asc')
            ->get();

        $formatted = $sessions->map(function ($s) {
            $class = $s->classRoom;
            $subject = $class ? $class->subject : null;
            
            return [
                'id'         => $s->id,
                'class_id'   => $s->class_id,
                'start_time' => $s->start_time,
                'end_time'   => $s->end_time,
                'status'     => $s->status,
                'room'       => $class->room_number ?? 'TBD',
                'subject'    => [
                    'name' => $subject->name ?? 'N/A',
                    'code' => $subject->code ?? 'N/A',
                ],
                'presence_count'       => Attendance::where('session_id', $s->id)->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count(),
                'total_students_count' => $class && $class->group_id ? Student::where('group_id', $class->group_id)->count() : 0,
            ];
        });

        return response()->json($formatted);
    }

    public function updateStatus(Request $request, $sessionId)
    {
        $request->validate([
            'status' => 'required|in:active,scheduled,completed,passed,skipped',
            'reschedule' => 'sometimes|boolean'
        ]);

        $session = AttendanceSession::findOrFail($sessionId);
        
        if ($request->status === 'skipped' && $request->reschedule) {
            $nextSlot = $this->calculateNextSlotData($session);
            if ($nextSlot) {
                $session->update([
                    'start_time'         => $nextSlot['start_time'],
                    'end_time'           => $nextSlot['end_time'],
                    'checkin_open_time'  => $nextSlot['checkin_open_time'],
                    'checkin_close_time' => $nextSlot['checkin_close_time'],
                    'status'             => 'scheduled' // Re-schedule it to the end
                ]);

                ActivityLog::create([
                    'action' => 'UPDATE',
                    'target' => "session#{$session->id}.move_to_end"
                ]);

                return response()->json(['success' => true, 'message' => 'Session moved to end of semester', 'session' => $session]);
            } else {
                return response()->json(['success' => false, 'error' => 'Could not find a future slot.'], 400);
            }
        }

        $session->update(['status' => $request->status]);
        
        ActivityLog::create([
            'action' => 'UPDATE',
            'target' => "session#{$session->id}.status_{$request->status}"
        ]);

        if ($request->status === 'completed') {
            app(\App\Services\TelegramService::class)->checkAbsenceThresholds($session->class_id);
        }

        return response()->json(['success' => true, 'session' => $session]);
    }

    public function getNextAvailableSlot($sessionId)
    {
        $session = AttendanceSession::findOrFail($sessionId);
        $nextSlot = $this->calculateNextSlotData($session);
        if ($nextSlot) {
            return response()->json([
                'success' => true,
                'date' => $nextSlot['start_time']->format('Y-m-d'),
                'time' => $nextSlot['start_time']->format('H:i'),
                'start_time' => $nextSlot['start_time']->toDateTimeString()
            ]);
        }
        return response()->json(['success' => false, 'error' => 'No more slots available.'], 404);
    }

    private function calculateNextSlotData($session)
    {
        $class = $session->classRoom;
        if (!$class) return null;

        // Find the last session for this class/semester
        $lastSession = AttendanceSession::where('class_id', $class->id)
            ->where('academic_year', $session->academic_year)
            ->where('semester', $session->semester)
            ->orderBy('start_time', 'desc')
            ->first();

        if (!$lastSession) return null;

        list($allowedDays, $timeSlots) = $this->parseSchedule($class->schedule);
        if (empty($allowedDays) || empty($timeSlots)) return null;

        $currentDate = \Carbon\Carbon::parse($lastSession->start_time)->startOfDay();
        
        // Find which slot the last session was
        $lastSlotIndex = -1;
        $lastStartTimeStr = \Carbon\Carbon::parse($lastSession->start_time)->format('H:i');
        foreach ($timeSlots as $idx => $slot) {
            if ($slot['start'] === $lastStartTimeStr) {
                $lastSlotIndex = $idx;
                break;
            }
        }

        // Determine next slot
        $nextSlotIndex = $lastSlotIndex + 1;
        if ($nextSlotIndex >= count($timeSlots)) {
            $nextSlotIndex = 0;
            $currentDate->addDay();
        }

        // Find next available day
        $maxIter = 60; // Safety (increased to handle longer breaks)
        while ($maxIter > 0) {
            $maxIter--;
            if (in_array($currentDate->dayOfWeek, $allowedDays)) {
                // Check if in holiday
                $assignment = SemesterAssignment::where('class_id', $class->id)
                    ->where('academic_year', $session->academic_year)
                    ->where('semester', $session->semester)
                    ->first();
                
                $inHoliday = false;
                if ($assignment && $assignment->holiday_start && $assignment->holiday_end) {
                    $inHoliday = $currentDate->between($assignment->holiday_start, $assignment->holiday_end);
                }

                if (!$inHoliday) {
                    $slot = $timeSlots[$nextSlotIndex];
                    $sTime = $currentDate->copy()->setTimeFromTimeString($slot['start']);
                    $eTime = $currentDate->copy()->setTimeFromTimeString($slot['end']);

                    return [
                        'start_time'         => $sTime,
                        'end_time'           => $eTime,
                        'checkin_open_time'  => $sTime->copy()->subMinutes(20),
                        'checkin_close_time' => $sTime->copy()->addMinutes(20),
                    ];
                }
            }
            $currentDate->addDay();
            $nextSlotIndex = 0; // After moving to a new day, start from first slot
        }

        return null;
    }

    public function listSessionAttendance($sessionId)
    {
        $session = AttendanceSession::with('classRoom.subject')->findOrFail($sessionId);
        $attendances = Attendance::where('session_id', $sessionId)->get()->keyBy('student_id');
        
        $allStudents = collect();
        if ($session->classRoom && $session->classRoom->group_id) {
            $allStudents = Student::with('user')->where('group_id', $session->classRoom->group_id)->get();
        }

        $rows = $allStudents->map(function ($student) use ($attendances) {
            $att = $attendances->get($student->id);
            return [
                'id' => $student->id,
                'attendance_id' => $att?->id,
                'name' => $student->user->name ?? 'Unknown Student',
                'student_code' => $student->student_code,
                'status' => $att ? strtoupper($att->status) : 'ABSENT',
                'check_in_time' => $att && $att->scan_time ? \Carbon\Carbon::parse($att->scan_time)->format('H:i') : '—',
                'method' => $att ? strtoupper($att->method) : '—',
            ];
        });

        return response()->json([
            'success' => true,
            'session_name' => $session->classRoom->subject->name ?? 'N/A',
            'present_count' => $attendances->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count(),
            'total_count' => $allStudents->count(),
            'data' => $rows
        ]);
    }

    public function updateSession(Request $request, $sessionId)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time'   => 'required|date|after:start_time',
            'status'     => 'sometimes|string'
        ]);

        $session = AttendanceSession::findOrFail($sessionId);
        
        $data = $request->only(['start_time', 'end_time', 'status']);
        
        // Auto-update checkin windows based on new start_time
        $sTime = \Carbon\Carbon::parse($request->start_time);
        $data['checkin_open_time'] = $sTime->copy()->subMinutes(20);
        $data['checkin_close_time'] = $sTime->copy()->addMinutes(20);

        $session->update($data);

        ActivityLog::create([
            'action' => 'UPDATE',
            'target' => "session#{$sessionId}.reschedule_man"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session updated successfully',
            'session' => $session
        ]);
    }

    public function globalSkip(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time'   => 'required|date|after:start_time',
            'reschedule' => 'sometimes|boolean'
        ]);

        $sessions = AttendanceSession::whereBetween('start_time', [
                \Carbon\Carbon::parse($request->start_time), 
                \Carbon\Carbon::parse($request->end_time)
            ])
            ->where('status', '!=', 'completed')
            ->get();

        $affected = 0;
        foreach ($sessions as $session) {
            if ($request->reschedule) {
                $nextSlot = $this->calculateNextSlotData($session);
                if ($nextSlot) {
                    $session->update([
                        'start_time'         => $nextSlot['start_time'],
                        'end_time'           => $nextSlot['end_time'],
                        'checkin_open_time'  => $nextSlot['checkin_open_time'],
                        'checkin_close_time' => $nextSlot['checkin_close_time'],
                        'status'             => 'scheduled'
                    ]);
                    $affected++;

                    ActivityLog::create([
                        'action' => 'UPDATE',
                        'target' => "session#{$session->id}.auto_move"
                    ]);
                }
            } else {
                $session->update(['status' => 'skipped']);
                $affected++;

                ActivityLog::create([
                    'action' => 'UPDATE',
                    'target' => "session#{$session->id}.status_skipped"
                ]);
            }
        }

        if ($affected > 0) {
            ActivityLog::create([
                'action' => 'UPDATE',
                'target' => "global.batch_skip#{$affected}"
            ]);
        }

        $msg = $request->reschedule 
            ? "Successfully moved $affected sessions to the end of the semester."
            : "Successfully skipped $affected sessions across all subjects.";

        return response()->json([
            'success' => true,
            'message' => $msg,
            'affected_count' => $affected
        ]);
    }

    public function skipTodayAndShift(Request $request)
    {
        $today = now()->toDateString();
        $dayOfWeek = now()->dayOfWeek; // 0 (Sun) to 6 (Sat)
        $sqlDay = $dayOfWeek + 1; // MySQL DAYOFWEEK is 1-indexed (1=Sun, 2=Mon...)

        // 1. Identify all sessions scheduled for today
        $sessionsToday = AttendanceSession::whereDate('start_time', $today)
            ->where('status', 'scheduled')
            ->get();

        if ($sessionsToday->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No scheduled sessions found for today.']);
        }

        \DB::beginTransaction();
        try {
            foreach ($sessionsToday as $session) {
                $classId = $session->class_id;

                // 2. We shift THIS session and ALL FUTURE sessions for this class
                // that occur on the SAME DAY OF WEEK.
                $futureAndToday = AttendanceSession::where('class_id', $classId)
                    ->where('start_time', '>=', $session->start_time)
                    ->where('status', 'scheduled')
                    ->whereRaw("DAYOFWEEK(start_time) = ?", [$sqlDay])
                    ->orderBy('start_time', 'desc') // Shift from the furthest future backwards to avoid any logic overlaps
                    ->get();

                foreach ($futureAndToday as $fs) {
                    $newStart = $fs->start_time->addWeek();
                    $newEnd = $fs->end_time->addWeek();
                    
                    $fs->update([
                        'start_time' => $newStart,
                        'end_time' => $newEnd,
                        'checkin_open_time' => $newStart->copy()->subMinutes(20),
                        'checkin_close_time' => $newStart->copy()->addMinutes(20),
                    ]);
                }
            }

            \DB::commit();
            return response()->json(['success' => true, 'message' => 'Today\'s sessions shifted to next week successfully.']);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function listStudents()
    {
        return response()->json(Student::with(['user', 'classRoom.subject'])->get());
    }

    public function storeStudent(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string',
            'student_code' => 'required|unique:students,student_code',
            'group_id' => 'required|exists:class_groups,id',
            'major_id' => 'required|exists:majors,id',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'student_code' => $request->student_code,
                'group_id' => $request->group_id,
                'major_id' => $request->major_id,
                'year_level' => $request->year_level,
                'status' => $request->status ?? 'active',
            ]);

            DB::commit();
            return response()->json(['success' => true, 'student' => $student->load('user', 'classRoom.subject')]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStudent(Request $request, $studentId)
    {
        $student = Student::with('user')->findOrFail($studentId);
        
        $request->validate([
            'name'         => 'sometimes|required',
            'email'        => 'sometimes|nullable|email|unique:users,email,' . $student->user_id,
            'phone'        => 'sometimes|nullable|string',
            'student_code' => 'sometimes|required|unique:students,student_code,' . $student->id,
            'group_id'     => 'sometimes|required|exists:class_groups,id',
            'major_id'     => 'sometimes|required|exists:majors,id',
            'status'       => 'sometimes|required|in:active,suspended,graduated'
        ]);

        DB::beginTransaction();
        try {
            $userUpdates = $request->only(['name', 'email', 'phone']);
            if (!empty($userUpdates)) {
                $student->user->update($userUpdates);
            }
            
            $studentUpdates = $request->only(['student_code', 'group_id', 'major_id', 'year_level', 'status', 'class_id']);
            
            if ($request->has('class_id') && $request->class_id) {
                $class = \App\Models\ClassRoom::find($request->class_id);
                if ($class && $class->group_id) {
                    $studentUpdates['group_id'] = $class->group_id;
                }
            }
            
            $student->update($studentUpdates);
            
            DB::commit();
            return response()->json(['success' => true, 'student' => $student->load('user', 'classRoom.subject')]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteStudent($studentId)
    {
        $student = Student::with('user')->findOrFail($studentId);
        $user = $student->user;
        
        DB::beginTransaction();
        try {
            $student->delete();
            $user->delete();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listStudentAttendance($studentId)
    {
        $student = Student::with(['user', 'classRoom.subject'])->findOrFail($studentId);
        
        // Fetch the most recent 10 sessions for the student's group
        $sessions = AttendanceSession::whereHas('classRoom', function($q) use ($student) {
                $q->where('group_id', $student->group_id);
            })
            ->where('start_time', '<=', now())
            ->latest('start_time')
            ->limit(10)
            ->get();

        $history = $sessions->map(function($session) use ($studentId) {
            $att = Attendance::where('student_id', $studentId)
                ->where('session_id', $session->id)
                ->first();

            return [
                'id'          => $session->id,
                'subject'     => $session->classRoom->subject->name ?? 'N/A',
                'date'        => \Carbon\Carbon::parse($session->start_time)->format('M d, Y'),
                'time'        => $att && $att->scan_time ? \Carbon\Carbon::parse($att->scan_time)->format('H:i') : '—',
                'status'      => $att ? strtoupper($att->status) : 'ABSENT',
                'method'      => $att ? strtoupper($att->method ?? 'QR') : '—',
            ];
        });

        $presentCount = Attendance::where('student_id', $studentId)->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count();
        $absentCount  = $sessions->count() - $presentCount;
        $totalSessions = AttendanceSession::whereHas('classRoom', function($q) use ($student) {
                $q->where('group_id', $student->group_id);
            })->where('start_time', '<=', now())->count();
        $rate = $totalSessions === 0 ? 0 : round(($presentCount / $totalSessions) * 100);

        return response()->json([
            'success' => true,
            'student' => [
                'id'           => $student->id,
                'name'         => $student->user->name ?? 'N/A',
                'email'        => $student->user->email ?? 'N/A',
                'phone'        => $student->user->phone ?? 'N/A',
                'student_code' => $student->student_code,
                'major'        => $student->major->name ?? 'Technology',
                'year_level'   => $student->year_level ?? '1',
                'status'       => $student->status ?? 'active',
                'joined_at'    => $student->created_at->format('M Y'),
                'attendance_rate' => $rate
            ],
            'summary' => [
                'present' => $presentCount,
                'absent'  => $totalSessions - $presentCount,
            ],
            'history' => $history
        ]);
    }

    public function listSubjects()
    {
        return response()->json(Subject::with('department')->get());
    }

    public function storeSubject(Request $request)
    {
        $subject = Subject::create($request->all());
        return response()->json(['success' => true, 'subject' => $subject->load('department')]);
    }

    public function updateSubject(Request $request, $subjectId)
    {
        $subject = Subject::findOrFail($subjectId);
        $subject->update($request->all());
        return response()->json(['success' => true, 'subject' => $subject->load('department')]);
    }

    public function deleteSubject($subjectId)
    {
        $subject = Subject::findOrFail($subjectId);
        $subject->delete();
        return response()->json(['success' => true]);
    }

    public function listDepartments()
    {
        return response()->json(Department::withCount(['teachers', 'subjects'])->get());
    }

    public function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|unique:departments,name']);
        $dept = Department::create($request->all());
        return response()->json(['success' => true, 'department' => $dept->loadCount(['teachers', 'subjects'])]);
    }

    public function updateDepartment(Request $request, $deptId)
    {
        $dept = Department::findOrFail($deptId);
        $dept->update($request->all());
        return response()->json(['success' => true, 'department' => $dept->loadCount(['teachers', 'subjects'])]);
    }

    public function deleteDepartment($deptId)
    {
        $dept = Department::findOrFail($deptId);
        $dept->delete();
        return response()->json(['success' => true]);
    }

    public function storeInstructor(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'password' => 'required|min:6',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'teacher',
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'department_id' => $request->department_id,
                'specialization' => $request->specialization,
                'status' => $request->status ?? 'active',
            ]);

            DB::commit();
            return response()->json(['success' => true, 'teacher' => $teacher->load('user')]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateInstructor(Request $request, $teacherId)
    {
        $teacher = Teacher::with('user')->findOrFail($teacherId);
        
        DB::beginTransaction();
        try {
            $teacher->user->update($request->only(['name', 'email', 'phone']));
            if ($request->password) {
                $teacher->user->update(['password' => Hash::make($request->password)]);
            }
            
            $teacher->update($request->only(['department_id', 'specialization', 'status']));
            
            DB::commit();
            return response()->json(['success' => true, 'teacher' => $teacher->load('user')]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteInstructor($teacherId)
    {
        $teacher = Teacher::with('user')->findOrFail($teacherId);
        $user = $teacher->user;
        
        DB::beginTransaction();
        try {
            $teacher->delete();
            $user->delete();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function updateUserAccount(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        $payload = [];
        if ($request->role) {
            $payload['role'] = $request->role;
        }
        if ($request->password) {
            $payload['password'] = Hash::make($request->password);
        }

        if (empty($payload)) {
            return response()->json(['error' => 'No updates provided.'], 400);
        }

        $user->update($payload);
        return response()->json(['success' => true, 'user' => $user]);
    }

    public function exportStudents()
    {
        $students = Student::with(['user', 'group', 'major'])->get();
        $fileName = 'student_registry_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Name', 'Email', 'Student Code', 'Major', 'Year', 'Group', 'Status'];

        $callback = function() use($students, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($students as $s) {
                fputcsv($file, [
                    $s->id,
                    $s->user->name ?? 'N/A',
                    $s->user->email ?? 'N/A',
                    $s->student_code ?? 'N/A',
                    $s->major->name ?? 'N/A',
                    $s->year_level ?? 'N/A',
                    $s->group->name ?? 'Unassigned',
                    $s->status ?? 'active',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importStudents(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");
        $header = fgetcsv($handle, 1000, ","); // Skip header

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // name, email, student_code, group_id, major_id, year_level
                if (count($data) < 3) continue;

                $email = !empty($data[1]) ? $data[1] : null;

                $user = User::create([
                    'name' => $data[0],
                    'email' => $email,
                    'password' => Hash::make('student123'),
                    'role' => 'student',
                ]);

                Student::create([
                    'user_id' => $user->id,
                    'student_code' => $data[2],
                    'group_id' => isset($data[3]) && is_numeric($data[3]) ? $data[3] : null,
                    'major_id' => isset($data[4]) && is_numeric($data[4]) ? $data[4] : null,
                    'year_level' => $data[5] ?? 1,
                    'status' => 'active',
                ]);
            }
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
        } finally {
            fclose($handle);
        }
    }

    public function exportClasses()
    {
        $classes = ClassRoom::with(['subject', 'teacher.user'])->get();
        $fileName = 'academic_catalog_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Subject', 'Instructor', 'Room', 'Schedule', 'Status'];

        $callback = function() use($classes, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($classes as $class) {
                fputcsv($file, [
                    $class->id,
                    $class->subject->name ?? 'N/A',
                    $class->teacher->user->name ?? 'N/A',
                    $class->room_number ?? 'N/A',
                    $class->schedule ?? 'N/A',
                    $class->status ?? 'N/A',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function generateAcademicCalendar(Request $request)
    {
        $request->validate([
            'academic_year' => 'required',
            'semester' => 'required|in:1,2',
            'start_date' => 'required|date',
            'sessions_count' => 'integer|min:1|max:60'
        ]);

        $year = $request->academic_year;
        $semester = $request->semester;
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $count = $request->sessions_count ?? 30;

        $classes = \App\Models\ClassRoom::all();
        $generated = 0;

        \DB::beginTransaction();
        try {
            foreach ($classes as $class) {
                // Update class metadata
                $class->update(['academic_year' => $year, 'semester' => $semester]);

                // Clear existing sessions for this class and semester to avoid duplicates
                \App\Models\AttendanceSession::where('class_id', $class->id)
                    ->where('semester', $semester)
                    ->where('academic_year', $year)
                    ->delete();

                // Generate N sessions using the unified generator
                $sessionsCreated = $this->generateAcademicSessions(
                    $class, 
                    $year, 
                    $semester, 
                    $startDate, 
                    $count, 
                    null, 
                    null
                );
                
                $generated += $sessionsCreated;
            }

            \DB::commit();
            return response()->json(['success' => true, 'generated' => $generated]);
        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // ─────────────────────────────────────────────────────
    // SEMESTER ASSIGNMENTS
    // ─────────────────────────────────────────────────────

    public function listSemesterAssignments($classId)
    {
        $assignments = SemesterAssignment::where('class_id', $classId)
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'asc')
            ->get();

        return response()->json([
            'success' => true, 
            'data' => $assignments->map(fn(SemesterAssignment $a) => $this->formatAssignment($a))
        ]);
    }

    public function storeSemesterAssignment(Request $request, $classId)
    {
        $request->validate([
            'academic_year'  => 'required|string|max:20',
            'semester'       => 'required|in:1,2',
            'start_date'     => 'required|date',
            'holiday_start'  => 'nullable|date',
            'notes'          => 'nullable|string|max:500',
            'sessions_count' => 'nullable|integer|min:1|max:100',
            'time_start'     => 'nullable|string',
            'time_end'       => 'nullable|string',
        ]);

        $class = ClassRoom::findOrFail($classId);

        $startDate    = \Carbon\Carbon::parse($request->start_date);
        $endDate      = $startDate->copy()->addMonths(4);
        $holidayStart = $request->holiday_start ? \Carbon\Carbon::parse($request->holiday_start) : null;
        $holidayEnd   = $holidayStart ? $holidayStart->copy()->addWeeks(3) : null;

        // Determine status
        $now = now();
        $status = 'upcoming';
        if ($now->between($startDate, $endDate)) $status = 'active';
        if ($now->gt($endDate)) $status = 'completed';

        DB::beginTransaction();
        try {
            // 2. Upsert Semester Assignment Record
            $assignment = SemesterAssignment::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'academic_year' => $request->academic_year,
                    'semester' => (int) $request->semester,
                ],
                [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'holiday_start' => $holidayStart?->toDateString(),
                    'holiday_end' => $holidayEnd?->toDateString(),
                    'status' => $status,
                    'notes' => $request->notes
                ]
            );

            // 1. Update Class Schedule if times provided
            if ($request->time_start && $request->time_end) {
                // Determine days (fallback to first space-separated part or Mon/Wed/Fri)
                $daysPart = "Mon/Wed/Fri";
                if ($class->schedule) {
                    $parts = explode(' ', $class->schedule);
                    if ($parts[0]) $daysPart = $parts[0];
                }
                $class->update([
                    'schedule' => "$daysPart ($request->time_start-$request->time_end)",
                    'semester' => (int) $request->semester,
                    'academic_year' => $request->academic_year
                ]);
            } else {
                $class->update([
                    'semester' => (int) $request->semester,
                    'academic_year' => $request->academic_year
                ]);
            }

            // 3. GENERATE SESSIONS
            $sessionsTarget = $request->sessions_count ?? 30;
            $schedStr = $class->schedule;
            if (!$schedStr) throw new \Exception("Class schedule not defined.");

            // Parse Days
            $daysMap = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 0];
            $allowedDays = [];
            
            // Check for explicit "weekday" or "mon-fri" keywords
            if (stripos($schedStr, 'mon-fri') !== false || stripos($schedStr, 'weekday') !== false) {
                $allowedDays = [1, 2, 3, 4, 5];
            } else if (stripos($schedStr, 'sat/sun') !== false || stripos($schedStr, 'weekend') !== false) {
                $allowedDays = [6, 0];
            } else {
                // Otherwise, check for individual days
                foreach ($daysMap as $dStr => $dNum) {
                    if (stripos($schedStr, $dStr) !== false) $allowedDays[] = $dNum;
                }
            }
            if (empty($allowedDays)) $allowedDays = [1, 2, 3, 4, 5]; 

            // GENERATE SESSIONS (Unified)
            $sessionsCreated = $this->generateAcademicSessions(
                $class,
                $request->academic_year,
                $request->semester,
                $startDate,
                $sessionsTarget,
                $holidayStart,
                $holidayEnd
            );

            DB::commit();
            return response()->json(['success' => true, 'data' => $this->formatAssignment($assignment)]);
        } catch (\Exception $e) {
            DB::rollback();
            if (str_contains($e->getMessage(), 'uniq_class_sem')) {
                return response()->json(['error' => 'This class already has a Semester '.$request->semester.' assignment for '.$request->academic_year.'.'], 422);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateSemesterAssignment(Request $request, $assignmentId)
    {
        $assignment = SemesterAssignment::findOrFail($assignmentId);
        $request->validate([
            'holiday_start' => 'nullable|date',
            'notes'         => 'nullable|string|max:500',
            'status'        => 'nullable|in:upcoming,active,completed',
            'academic_year' => 'nullable|string',
            'semester'      => 'nullable|integer'
        ]);

        if ($request->filled('holiday_start')) {
            $holidayStart = \Carbon\Carbon::parse($request->holiday_start);
            $assignment->holiday_start = $holidayStart->toDateString();
            $assignment->holiday_end   = $holidayStart->copy()->addWeeks(3)->toDateString();
        }
        if ($request->filled('notes')) $assignment->notes = $request->notes;
        if ($request->filled('status')) $assignment->status = $request->status;
        if ($request->filled('academic_year')) $assignment->academic_year = $request->academic_year;
        if ($request->filled('semester')) $assignment->semester = $request->semester;
        $assignment->save();

        // Sync with ClassRoom if status is active
        if ($assignment->status === 'active') {
            $assignment->classRoom->update([
                'semester' => $assignment->semester,
                'academic_year' => $assignment->academic_year
            ]);
        }

        return response()->json(['success' => true, 'data' => $this->formatAssignment($assignment)]);
    }

    public function deleteSemesterAssignment($assignmentId)
    {
        $assignment = SemesterAssignment::findOrFail($assignmentId);
        
        DB::beginTransaction();
        try {
            // Purge all generated sessions for this class and semester
            \App\Models\AttendanceSession::where('class_id', $assignment->class_id)
                ->where('academic_year', $assignment->academic_year)
                ->where('semester', (int)$assignment->semester)
                ->delete();

            $assignment->delete();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function formatAssignment(SemesterAssignment $a): array
    {
        $fmt = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : null;
        return [
            'id'            => $a->id,
            'class_id'      => $a->class_id,
            'academic_year' => $a->academic_year,
            'semester'      => $a->semester,
            'start_date'    => $fmt($a->start_date),
            'end_date'      => $fmt($a->end_date),
            'holiday_start' => $fmt($a->holiday_start),
            'holiday_end'   => $fmt($a->holiday_end),
            'status'        => $a->status,
            'notes'         => $a->notes,
            'progress'      => $a->progress,
            'active_days'   => $a->active_days,
            'in_holiday'    => $a->in_holiday,
            'created_at'    => $a->created_at?->format('Y-m-d'),
        ];
    }

    public function getUserRoles()
    {
        return response()->json([
            'success' => true,
            'roles' => ['admin', 'teacher', 'student']
        ]);
    }

    /**
     * Get global activity for system-wide notifications (for admin & monitoring)
     */
    public function getGlobalActivity(Request $request)
    {
        try {
            $logs = ActivityLog::orderBy("id", "desc")->limit(10)->get()->map(function($log) {
                return [
                    "id" => $log->id,
                    "action" => $log->action,
                    "target" => $log->target,
                    "time" => $log->created_at->format("h:i A"),
                    "type" => "system"
                ];
            });

            if ($logs->isNotEmpty()) {
                return response()->json(["success" => true, "activity" => $logs]);
            }

            $newAttendances = \App\Models\Attendance::with(["student.user", "session.classRoom.subject"])
                ->orderBy("id", "desc")
                ->limit(10)
                ->get()
                ->map(function($att) {
                    return [
                        "id" => $att->id,
                        "action" => "INSERT",
                        "target" => ($att->student && $att->student->user ? $att->student->user->name : "Unknown") . " @ " . ($att->session && $att->session->classRoom && $att->session->classRoom->subject ? $att->session->classRoom->subject->name : "Unknown"),
                        "time" => $att->created_at->format("h:i A"),
                        "type" => "attendance"
                    ];
                });
            
            return response()->json(["success" => true, "activity" => $newAttendances]);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }
}

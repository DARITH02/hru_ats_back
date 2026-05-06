<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Public Student Check-in
     */
    public function verify(Request $request)
    {
        $request->validate([
            'session_id'   => 'required',
            'student_code' => 'required',
            'qr_token'     => 'required',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
            'accuracy'     => 'nullable|numeric',
        ]);

        try {
            $this->attendanceService->processCheckin(
                $request->session_id, 
                $request->student_code,
                $request->qr_token,
                $request->latitude,
                $request->longitude,
                $request->accuracy
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful!',
                'status' => 'Present'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get Session details for student API scan
     */
    public function getScanInfo($sessionId)
    {
        try {
            $session = \App\Models\AttendanceSession::with('classRoom.subject')->findOrFail($sessionId);

            return response()->json([
                'success' => true,
                'session' => [
                    'id' => $session->id,
                    'room' => 100 + ($session->id % 400),
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'subject' => $session->classRoom->subject->name ?? 'Unknown',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or unavailable'
            ], 404);
        }
    }

    public function getPortalData(Request $request)
    {
        $user = $request->user();
        $student = \App\Models\Student::with(['group', 'user'])->where('user_id', $user->id)->first();
        
        if (!$student) {
            return response()->json(['message' => 'Student record not found'], 404);
        }

        // 1. Active/Scheduled Session
        $session = \App\Models\AttendanceSession::whereHas('classRoom', function ($q) use ($student) {
                $q->where('group_id', $student->group_id);
            })
            ->whereIn('status', ['active', 'scheduled'])
            ->with(['classRoom.subject', 'classRoom.teacher.user'])
            ->latest('id')
            ->first();

        $activeSession = null;
        if ($session) {
            $activeSession = [
                'id' => $session->id,
                'subject' => $session->classRoom->subject->name ?? 'N/A',
                'teacher' => $session->classRoom->teacher->user->name ?? 'N/A',
                'room' => $session->location ?? 'TBD',
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
                'status' => $session->status,
            ];
        }

        // 2. Stats
        $allSessions = \App\Models\AttendanceSession::whereHas('classRoom', function($q) use ($student) {
                $q->where('group_id', $student->group_id);
            })
            ->get();

        $attendance = \App\Models\Attendance::where('student_id', $student->id)->get();
        
        $total = $allSessions->count();
        $present = $attendance->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count();
        $rate = $total > 0 ? round(($present / $total) * 100) : 0;

        return response()->json([
            'student' => [
                'name' => $student->user->name ?? $student->name,
                'code' => $student->student_code,
                'group' => $student->group->name ?? 'N/A',
                'major' => $student->major->name ?? 'N/A',
            ],
            'active_session' => $activeSession,
            'stats' => [
                'total' => $total,
                'present' => $present,
                'absent' => $total - $present,
                'rate' => $rate,
                'remaining' => max(0, 30 - $total), // Example logic: 30 sessions per semester
            ]
        ]);
    }

    public function getActiveSession(Request $request)
    {
        $user = $request->user();
        $student = \App\Models\Student::where('user_id', $user->id)->first();
        if (!$student) return response()->json(['message' => 'Student record not found'], 404);

        $session = \App\Models\AttendanceSession::whereHas('classRoom', function ($q) use ($student) {
                $q->where('group_id', $student->group_id);
            })
            ->whereIn('status', ['active', 'scheduled'])
            ->with(['classRoom.subject', 'classRoom.teacher.user'])
            ->latest('id')
            ->first();

        if (!$session) return response()->json(null);

        return response()->json([
            'id' => $session->id,
            'subject' => $session->classRoom->subject->name ?? 'N/A',
            'teacher' => $session->classRoom->teacher->user->name ?? 'N/A',
            'room' => $session->location ?? 'TBD',
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'status' => $session->status,
        ]);
    }

    public function getStudentHistoryByCode(Request $request)
    {
        $request->validate(['student_code' => 'required']);
        
        $student = \App\Models\Student::with(['group', 'user'])->where('student_code', $request->student_code)->first();
        if (!$student) return response()->json(['success' => false, 'message' => 'Student not found'], 404);

        $sessions = \App\Models\AttendanceSession::whereHas('classRoom', function($q) use ($student) {
                $q->where('group_id', $student->group_id);
            })
            ->with(['classRoom.subject', 'classRoom.teacher.user'])
            ->orderBy('id', 'desc')
            ->get();

        $attendance = \App\Models\Attendance::where('student_id', $student->id)->get()->keyBy('session_id');

        $history = $sessions->map(function ($session) use ($attendance) {
            $record = $attendance->get($session->id);
            
            $status = 'ABSENT';
            $isFuture = \Carbon\Carbon::parse($session->start_time)->isFuture();

            if ($record) {
                $status = strtoupper($record->status);
            } elseif ($session->status === 'scheduled' || $isFuture) {
                $status = 'SCHEDULED';
            }

            return [
                'subject' => $session->classRoom->subject->name ?? 'N/A',
                'teacher' => $session->classRoom->teacher->user->name ?? 'N/A',
                'date' => $session->start_time,
                'status' => $status,
                'session_status' => $session->status,
                'scan_time' => $record ? \Carbon\Carbon::parse($record->scan_time)->format('H:i') : null,
                'method' => $record ? strtoupper($record->method) : null,
            ];
        });

        $total = $sessions->count();
        $present = $attendance->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])->count();

        return response()->json([
            'success' => true,
            'student_name' => $student->user->name ?? 'Student',
            'student_id' => $student->id,
            'student_code' => $student->student_code,
            'class_name' => $student->group->name ?? 'N/A',
            'stats' => [
                'total' => $total,
                'present' => $present,
                'absent' => $total - $present,
                'rate' => $total > 0 ? round(($present / $total) * 100) : 0
            ],
            'history' => $history
        ]);
    }
}

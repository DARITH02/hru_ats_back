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

    public function getStudentHistoryByCode(Request $request)
    {
        $request->validate(['student_code' => 'required']);
        
        $student = \App\Models\Student::with('group')->where('student_code', $request->student_code)->first();
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
            return [
                'subject' => $session->classRoom->subject->name ?? 'N/A',
                'teacher' => $session->classRoom->teacher->user->name ?? 'N/A',
                'date' => $session->start_time,
                'status' => $record ? strtoupper($record->status) : 'ABSENT',
                'scan_time' => $record ? \Carbon\Carbon::parse($record->scan_time)->format('H:i') : null,
                'method' => $record ? strtoupper($record->method) : null,
            ];
        });

        $total = $sessions->count();
        $present = $attendance->whereIn('status', ['present', 'late'])->count();

        return response()->json([
            'success' => true,
            'student_name' => $student->name,
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

<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $sessionId;

    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $session = \App\Models\AttendanceSession::with('classRoom.group')->find($this->sessionId);
        $groupId = $session->classRoom->group_id ?? null;
        
        $attendances = Attendance::where('session_id', $this->sessionId)->get()->keyBy('student_id');
        
        if ($groupId) {
            $students = \App\Models\Student::with(['user', 'major', 'group'])
                ->where('group_id', $groupId)
                ->get();
                
            return $students->map(function($student) use ($attendances, $session) {
                $att = $attendances->get($student->id);
                return (object)[
                    'id'          => $student->student_code ?? $student->id,
                    'name'        => $student->user->name ?? $student->name,
                    'status'      => $att ? $att->status : 'absent',
                    'time'        => $att ? $att->scan_time : 'N/A',
                    'major'       => $student->major->name ?? 'N/A',
                    'year'        => $student->group->year_level ?? 'N/A',
                    'group'       => $student->group->name ?? 'N/A',
                    'room'        => $session->classRoom->room_number ?? 'N/A',
                    'subject'     => $session->classRoom->subject->name ?? 'N/A'
                ];
            });
        }

        return Attendance::with(['student.user', 'student.major', 'student.group', 'session.classRoom.subject'])
            ->where('session_id', $this->sessionId)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Student ID',
            'Student Name',
            'Status',
            'Scan Time',
            'Major',
            'Year Level',
            'Class Group',
            'Room',
            'Subject'
        ];
    }

    /**
    * @var mixed $item
    */
    public function map($item): array
    {
        if ($item instanceof \App\Models\Attendance) {
            return [
                $item->student->student_code ?? $item->student->id ?? 'N/A',
                $item->student->user->name ?? $item->student->name ?? 'Unknown',
                ucfirst($item->status),
                $item->scan_time ?? 'N/A',
                $item->student->major->name ?? 'N/A',
                $item->student->group->year_level ?? 'N/A',
                $item->student->group->name ?? 'N/A',
                $item->session->classRoom->room_number ?? 'N/A',
                $item->session->classRoom->subject->name ?? 'N/A'
            ];
        }

        return [
            $item->id,
            $item->name,
            ucfirst($item->status),
            $item->time,
            $item->major,
            $item->year,
            $item->group,
            $item->room,
            $item->subject
        ];
    }
}

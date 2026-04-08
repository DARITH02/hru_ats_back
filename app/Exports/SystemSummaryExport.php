<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\ClassRoom;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class SystemSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $academicYear;
    protected $semester;
    protected $reportType; // 'full' or 'half'

    public function __construct($academicYear, $semester, $reportType = 'full')
    {
        $this->academicYear = $academicYear;
        $this->semester = $semester;
        $this->reportType = $reportType;
    }

    public function collection()
    {
        // Get all classes for this semester/year
        $classes = ClassRoom::where('academic_year', $this->academicYear)
            ->where('semester', $this->semester)
            ->with(['subject', 'group'])
            ->get();

        $data = collect();

        foreach ($classes as $class) {
            // Get sessions for this class (including active and completed)
            $sessionQuery = AttendanceSession::where('class_id', $class->id)
                ->whereIn('status', ['completed', 'active'])
                ->orderBy('start_time', 'asc');

            // If half semester, only take sessions in the first half of the semester duration
            if ($this->reportType === 'half') {
                $assignment = \App\Models\SemesterAssignment::where('class_id', $class->id)
                    ->where('academic_year', $this->academicYear)
                    ->where('semester', $this->semester)
                    ->first();

                if ($assignment && $assignment->start_date && $assignment->end_date) {
                    $start = Carbon::parse($assignment->start_date);
                    $end = Carbon::parse($assignment->end_date);
                    $midPoint = $start->addDays($start->diffInDays($end) / 2);
                    $sessionQuery->where('start_time', '<=', $midPoint);
                } else {
                    // Fallback: Use 50% of total sessions if date range isn't clear
                    $allSessions = $sessionQuery->get();
                    $limit = ceil($allSessions->count() / 2);
                    $sessionQuery = AttendanceSession::whereIn('id', $allSessions->take($limit)->pluck('id'));
                }
            }

            $sessions = $sessionQuery->get();
            $sessionIds = $sessions->pluck('id');

            if ($sessionIds->isEmpty()) continue;

            // Get students in the group
            $students = Student::where('group_id', $class->group_id)->get();

            foreach ($students as $student) {
                $presentCount = Attendance::where('student_id', $student->id)
                    ->whereIn('session_id', $sessionIds)
                    ->whereIn('status', ['present', 'late', 'PRESENT', 'LATE'])
                    ->count();

                $totalSessions = $sessions->count();
                $absentCount = $totalSessions - $presentCount;
                $rate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 2) : 0;

                $data->push([
                    'student_code' => $student->student_code,
                    'student_name' => $student->name,
                    'subject'      => $class->subject->name ?? 'N/A',
                    'class_name'   => $class->group->name ?? 'N/A',
                    'total'        => $totalSessions,
                    'present'      => $presentCount,
                    'absent'       => $absentCount,
                    'rate'         => $rate . '%'
                ]);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        $type = $this->reportType === 'half' ? 'MID-TERM' : 'FULL SEMESTER';
        return [
            'Report Type: ' . $type,
            'Academic Year: ' . $this->academicYear,
            'Semester: ' . $this->semester,
            '',
            'Student ID',
            'Student Name',
            'Subject',
            'Group/Class',
            'Total Sessions',
            'Present',
            'Absent',
            'Attendance Rate'
        ];
    }

    public function map($row): array
    {
        // Heading logic in Laravel Excel is handled by WithHeadings
        // But the first few rows of collection can be header-like data or we can just return the data.
        return [
            '', '', '', '', // Padding for the extra info in headings
            $row['student_code'],
            $row['student_name'],
            $row['subject'],
            $row['class_name'],
            $row['total'],
            $row['present'],
            $row['absent'],
            $row['rate']
        ];
    }
}

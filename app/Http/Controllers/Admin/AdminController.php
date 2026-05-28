<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\Subject;

use App\Models\Department;
use App\Models\Major;
use App\Models\ClassGroup;
use App\Models\ActivityLog;
use App\Models\Attendance;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\SemesterAttendanceScoreService;
use App\Services\TelegramService;

class AdminController extends Controller
{
    public function instructors(Request $request)
    {
        $query = Teacher::with(['user', 'department'])
            ->withCount('classes')
            ->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'teachers.user_id')
                    ->take(1)
            );

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->whereHas('user', function ($u) use ($q) {
                    $u->where('name', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%");
                })->orWhereHas('department', function ($d) use ($q) {
                    $d->where('name', 'like', "%$q%");
                });
            });
        }

        if ($request->filled('dept')) {
            $query->where('department_id', $request->dept);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $instructors = $query->paginate(10)->appends($request->all());
        $depts = Department::orderBy('name')->get();

        return view('admin.instructors', compact('instructors', 'depts'));
    }

    public function teacherAccounts(Request $request)
    {
        $query = User::query();

        // If super admin, show everything except themselves
        // If normal admin, maybe only teachers? User said "supperadmin can delete all account admin teacher and more"
        if (auth()->user()->isSuperAdmin()) {
            $query->where('id', '!=', auth()->id())->where('role', '!=', 'student');
        } else {
            $query->whereIn('role', ['teacher', 'admin']);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%");
            });
        }
        $users = $query->orderBy('name')->paginate(15)->appends($request->all());
        return view('admin.teacher_accounts', compact('users'));
    }

    public function approveUser($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Only Superadmins can approve accounts.');
        }

        $user = User::findOrFail($id);
        $user->update(['is_approved' => true]);

        return redirect()->back()->with('success', "Account for {$user->name} has been approved.");
    }

    public function destroyUser($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Only Superadmins can delete accounts.');
        }

        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->back()->with('success', "Account for {$name} has been deleted.");
    }

    public function subjects(Request $request)
    {
        $query = Subject::withCount('classes');
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                    ->orWhere('code', 'like', "%$q%");
            });
        }
        if ($request->filled('dept')) {
            $query->where('department_id', $request->dept);
        }
        $subjects = $query->orderBy('name')->paginate(10)->appends($request->all());
        $depts = Department::orderBy('name')->get();
        return view('admin.subjects', compact('subjects', 'depts'));
    }

    public function departments(Request $request)
    {
        $query = Department::withCount(['teachers', 'subjects']);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                    ->orWhere('code', 'like', "%$q%");
            });
        }
        $departments = $query->orderBy('name')->paginate(10)->appends($request->all());
        return view('admin.departments', compact('departments'));
    }

    public function students(Request $request)
    {
        $this->syncDatabaseSchema();
        $query = Student::with(['user', 'group', 'major']);
        $majors = \App\Models\Major::orderBy('name')->get();
        $classGroups = \App\Models\ClassGroup::orderBy('name')->get();

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('student_code', 'like', "%$q%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%$q%")
                            ->orWhere('email', 'like', "%$q%");
                    })
                    ->orWhereHas('group', function ($g) use ($q) {
                        $g->where('name', 'like', "%$q%")
                            ->orWhere('year_level', 'like', "%$q%");
                    });
            });
        }

        if ($request->filled('major')) {
            $query->where('major_id', $request->major);
        }


        $students = $query->latest()->paginate(10)->appends($request->all());
        $classes = ClassRoom::with('subject')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.students', compact('students', 'classes', 'departments', 'majors', 'classGroups'));
    }

    public function coursePreEnd($id)
    {
        $data = $this->getCoursePreEndData($id);

        return view('admin.course_pre_end', [
            'class' => $data['class'],
            'assignment' => $data['assignment'],
            'students' => $data['studentData'],
            'sessions' => $data['sessions'],
            'attendanceGrid' => $data['attendanceGrid'],
            'error' => $data['error'],
            'stats' => $data['stats']
        ]);
    }

    private function getCoursePreEndData($id)
    {
        $class = ClassRoom::with(['subject', 'teacher.user'])->findOrFail($id);

        // Find assignment (can be active, completed, or even upcoming for review purposes)
        $assignment = \App\Models\SemesterAssignment::where('class_id', $id)
            ->whereIn('status', ['active', 'completed', 'upcoming'])
            ->latest()
            ->first();

        $students = $class->all_students;

        // Define filters for sessions based on assignment
        $sessionsQuery = \App\Models\AttendanceSession::where('class_id', $class->id);
        if ($assignment) {
            $sessionsQuery->where('academic_year', $assignment->academic_year)
                ->where('semester', (int) $assignment->semester);
        }
        $sessions = $sessionsQuery->orderBy('start_time')->get();

        $sessionIds = $sessions->pluck('id');
        $totalSessions = $sessionIds->count();
        $scheduledCount = $sessions->where('status', 'scheduled')->count();
        $attendanceScores = app(SemesterAttendanceScoreService::class);

        $studentData = $students->map(function ($student) use ($sessions, $assignment, $attendanceScores) {
            $attendanceResult = $attendanceScores->calculate($student->id, $sessions);

            $midterm = 0;
            $assignment_val = 0;
            $final = 0;
            $total = 0;

            if ($assignment) {
                $savedScore = \DB::table('semester_assignment_scores')
                    ->where('assignment_id', $assignment->id)
                    ->where('student_id', $student->id)
                    ->first();

                $midterm = $savedScore->midterm_score ?? 0;
                $assignment_val = $savedScore->assignment_score ?? 0;
                $final = $savedScore->final_score ?? 0;

                // Total = 20 (Att) + 15 (Mid) + 15 (Asgn) + 50 (Final)
                $total = $attendanceResult['score'] + $midterm + $assignment_val + $final;
            }

            return [
                'id' => $student->id,
                'name' => $student->user->name ?? 'Unknown',
                'code' => $student->student_code,
                'attended' => $attendanceResult['attended_sessions'],
                'permission_sessions' => $attendanceResult['permission_sessions'],
                'rate' => $attendanceResult['rate'],
                'att_score' => $attendanceResult['score'],
                'midterm' => $midterm,
                'assignment' => $assignment_val,
                'final' => $final,
                'total' => round($total, 2)
            ];
        });

        $avgRate = $studentData->count() > 0 ? $studentData->avg('rate') : 0;

        // Fetch detailed session-by-session attendance for the grid
        $attendanceGrid = [];
        foreach ($students as $student) {
            $studentAttendance = [];
            $studentPermissions = \App\Models\StudentPermission::where('student_id', $student->id)->get();
            foreach ($sessions as $session) {
                $record = \App\Models\Attendance::where('student_id', $student->id)
                    ->where('session_id', $session->id)
                    ->first();
                $sessionDate = \Carbon\Carbon::parse($session->start_time)->toDateString();
                $hasPermission = $studentPermissions->contains(function ($permission) use ($sessionDate) {
                    return $permission->start_date <= $sessionDate && $permission->end_date >= $sessionDate;
                });

                $studentAttendance[$session->id] = $record ? strtolower($record->status) : ($hasPermission ? 'excused' : 'absent');
            }
            $attendanceGrid[$student->id] = $studentAttendance;
        }

        $error = null;
        if (!$assignment) {
            $error = 'No semester assignment found for this class. Please assign a semester to enable grading.';
        } elseif ($assignment->status === 'upcoming') {
            $error = 'This semester is currently set to UPCOMING. You can review data, but check-ins may still be pending.';
        }

        return [
            'class' => $class,
            'assignment' => $assignment,
            'studentData' => $studentData,
            'sessions' => $sessions,
            'attendanceGrid' => $attendanceGrid,
            'error' => $error,
            'stats' => [
                'total_students' => $students->count(),
                'total_sessions' => $totalSessions,
                'avg_attendance' => round($avgRate, 1),
                'scheduled_count' => $scheduledCount
            ]
        ];
    }

    public function exportCoursePreEnd($id)
    {
        $data = $this->getCoursePreEndData($id);
        $subjectName = $data['class']->subject->name ?? 'Course Results';

        return Excel::download(
            new \App\Exports\SubjectScoresExport($data['studentData'], $subjectName),
            "Results_{$subjectName}_" . date('Ymd') . ".xlsx"
        );
    }

    protected function getAggregatedResults(Request $request)
    {
        $academicYear = $request->get('academic_year', date('Y') . '-' . (date('Y') + 1));
        $semester = $request->get('semester', 1);
        $yearLevel = $request->get('year_level');

        $query = \DB::table('semester_assignment_scores')
            ->join('students', 'semester_assignment_scores.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('semester_assignments', 'semester_assignment_scores.assignment_id', '=', 'semester_assignments.id')
            ->join('classes', 'semester_assignments.class_id', '=', 'classes.id')
            ->leftJoin('class_groups', 'students.group_id', '=', 'class_groups.id')
            ->where('semester_assignments.academic_year', $academicYear)
            ->where('semester_assignments.semester', (int) $semester);

        if ($yearLevel) {
            $query->where('class_groups.year_level', (int) $yearLevel);
        }

        return $query->select(
            'students.id as student_id',
            'users.name as student_name',
            'students.student_code',
            'class_groups.name as group_name',
            'class_groups.year_level',
            \DB::raw('COUNT(semester_assignment_scores.id) as total_subjects'),
            \DB::raw('AVG(semester_assignment_scores.score) as avg_score'),
            \DB::raw('SUM(semester_assignment_scores.attendance_score) as total_att'),
            \DB::raw('SUM(semester_assignment_scores.midterm_score) as total_mid'),
            \DB::raw('SUM(semester_assignment_scores.assignment_score) as total_asn'),
            \DB::raw('SUM(semester_assignment_scores.final_score) as total_fin')
        )
            ->groupBy('students.id', 'users.name', 'students.student_code', 'class_groups.name', 'class_groups.year_level')
            ->orderByDesc('avg_score')
            ->get()
            ->map(function ($res) {
                $score = $res->avg_score;
                $grade = 'F';
                if ($score >= 90)
                    $grade = 'A';
                elseif ($score >= 80)
                    $grade = 'B';
                elseif ($score >= 70)
                    $grade = 'C';
                elseif ($score >= 60)
                    $grade = 'D';
                elseif ($score >= 50)
                    $grade = 'E';

                return array_merge((array) $res, [
                    'grade' => $grade,
                    'status' => $score >= 50 ? 'PASSED' : 'FAILED'
                ]);
            });
    }

    public function analytics(Request $request)
    {
        $academicYear = $request->get('academic_year', date('Y') . '-' . (date('Y') + 1));
        $semester = $request->get('semester', 1);
        $yearLevel = $request->get('year_level');

        $totalStudents = Student::count();
        $totalClasses = ClassRoom::count();

        $scoreQuery = \DB::table('semester_assignment_scores')
            ->join('semester_assignments', 'semester_assignment_scores.assignment_id', '=', 'semester_assignments.id')
            ->where('semester_assignments.academic_year', $academicYear)
            ->where('semester_assignments.semester', (int) $semester);

        $avgScore = round($scoreQuery->avg('score') ?? 0, 1);

        // Pass rate (score >= 50)
        $passed = (clone $scoreQuery)->where('score', '>=', 50)->count();
        $totalScores = (clone $scoreQuery)->count();
        $passRate = $totalScores > 0 ? round(($passed / $totalScores) * 100) : 0;

        // Results by Department
        $deptStats = Department::all()->map(function ($dept) use ($academicYear, $semester) {
            $subjectIds = $dept->subjects->pluck('id');
            $classIds = ClassRoom::whereIn('subject_id', $subjectIds)->pluck('id');
            $scores = \DB::table('semester_assignment_scores')
                ->join('semester_assignments', 'semester_assignment_scores.assignment_id', '=', 'semester_assignments.id')
                ->whereIn('semester_assignments.class_id', $classIds)
                ->where('semester_assignments.academic_year', $academicYear)
                ->where('semester_assignments.semester', (int) $semester)
                ->get();

            return [
                'name' => $dept->name,
                'avg' => round($scores->avg('score') ?? 0, 1),
                'count' => $scores->count(),
                'pass_rate' => $scores->count() > 0 ? round(($scores->where('score', '>=', 50)->count() / $scores->count()) * 100) : 0
            ];
        });

        $detailedResults = $this->getAggregatedResults($request);
        // Group by group_name for UI display
        $groupedResults = $detailedResults->groupBy('group_name');

        $topStudents = $detailedResults->take(10);

        // Get available academic years for filter
        $academicYears = \App\Models\SemesterAssignment::distinct()->pluck('academic_year');
        if ($academicYears->isEmpty())
            $academicYears = [$academicYear];

        return view('admin.results', compact(
            'totalStudents',
            'totalClasses',
            'avgScore',
            'passRate',
            'deptStats',
            'topStudents',
            'groupedResults',
            'academicYear',
            'semester',
            'academicYears',
            'yearLevel'
        ));
    }

    public function exportResultsExcel(Request $request)
    {
        $fullData = $this->getFullSemesterResults($request);
        $academicYear = $request->get('academic_year', 'Current');
        $semester = $request->get('semester', '1');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\InstitutionalTranscriptExport($fullData['data'], $fullData['subjects'], $academicYear, $semester),
            "Institutional_Transcript_{$academicYear}_Sem{$semester}.xlsx"
        );
    }

    private function getFullSemesterResults(Request $request)
    {
        $academicYear = $request->get('academic_year', date('Y') . '-' . (date('Y') + 1));
        $semester = $request->get('semester', 1);

        // 1. Get all subjects that have assignments in this semester
        $subjects = \DB::table('semester_assignments')
            ->join('classes', 'semester_assignments.class_id', '=', 'classes.id')
            ->join('subjects', 'classes.subject_id', '=', 'subjects.id')
            ->where('semester_assignments.academic_year', $academicYear)
            ->where('semester_assignments.semester', (int) $semester)
            ->select('subjects.id', 'subjects.name', 'subjects.code')
            ->distinct()
            ->orderBy('subjects.name')
            ->get();

        // 2. Get all student scores for these assignments
        $rawScores = \DB::table('semester_assignment_scores')
            ->join('semester_assignments', 'semester_assignment_scores.assignment_id', '=', 'semester_assignments.id')
            ->join('classes', 'semester_assignments.class_id', '=', 'classes.id')
            ->join('subjects', 'classes.subject_id', '=', 'subjects.id')
            ->join('students', 'semester_assignment_scores.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->leftJoin('class_groups', 'students.group_id', '=', 'class_groups.id')
            ->where('semester_assignments.academic_year', $academicYear)
            ->where('semester_assignments.semester', (int) $semester)
            ->select(
                'students.id as student_id',
                'users.name as student_name',
                'students.student_code',
                'class_groups.name as group_name',
                'subjects.id as subject_id',
                'semester_assignment_scores.score'
            )
            ->get();

        // 3. Group scores by student
        $studentsData = [];
        foreach ($rawScores as $rs) {
            if (!isset($studentsData[$rs->student_id])) {
                $studentsData[$rs->student_id] = [
                    'name' => $rs->student_name,
                    'code' => $rs->student_code,
                    'group' => $rs->group_name ?? 'N/A',
                    'scores' => [],
                    'total_score' => 0,
                    'count' => 0
                ];
            }
            $studentsData[$rs->student_id]['scores'][$rs->subject_id] = $rs->score;
            $studentsData[$rs->student_id]['total_score'] += $rs->score;
            $studentsData[$rs->student_id]['count']++;
        }

        // 4. Format for export
        $finalData = [];
        foreach ($studentsData as $sid => $data) {
            $avg = $data['count'] > 0 ? $data['total_score'] / $data['count'] : 0;

            $grade = 'F';
            if ($avg >= 90)
                $grade = 'A';
            elseif ($avg >= 80)
                $grade = 'B';
            elseif ($avg >= 70)
                $grade = 'C';
            elseif ($avg >= 60)
                $grade = 'D';
            elseif ($avg >= 50)
                $grade = 'E';

            $row = [
                'name' => $data['name'],
                'code' => $data['code'],
                'group' => $data['group'],
            ];

            foreach ($subjects as $subj) {
                $row['subj_' . $subj->id] = $data['scores'][$subj->id] ?? 0;
            }

            $row['avg'] = round($avg, 2);
            $row['grade'] = $grade;
            $row['status'] = $avg >= 50 ? 'PASSED' : 'FAILED';

            $finalData[] = $row;
        }

        return [
            'subjects' => $subjects,
            'data' => collect($finalData)->sortByDesc('avg')->values()
        ];
    }

    public function exportResultsPdf(Request $request)
    {
        $data = $this->getAggregatedResults($request);
        $academicYear = $request->get('academic_year', 'Current');
        $semester = $request->get('semester', '1');

        // Group by group_name for better multi-class organization
        $groupedData = $data->groupBy('group_name');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.exports.results_pdf', [
            'groupedData' => $groupedData,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'avgScore' => round($data->avg('avg_score'), 1),
            'passRate' => $data->count() > 0 ? round(($data->where('status', 'PASSED')->count() / $data->count()) * 100) : 0
        ]);

        return $pdf->download("Semester_Results_{$academicYear}_Sem{$semester}.pdf");
    }

    public function sendResultsToTelegram(Request $request)
    {
        $data = $this->getAggregatedResults($request);
        $academicYear = $request->get('academic_year', 'Current');
        $semester = $request->get('semester', '1');

        $groupedData = $data->groupBy('group_name');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.exports.results_pdf', [
            'groupedData' => $groupedData,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'avgScore' => round($data->avg('avg_score'), 1),
            'passRate' => $data->count() > 0 ? round(($data->where('status', 'PASSED')->count() / $data->count()) * 100) : 0
        ]);

        $fileName = "Semester_Results_{$academicYear}_Sem{$semester}.pdf";
        $pdfPath = storage_path("app/public/{$fileName}");
        $pdf->save($pdfPath);

        $bot = \App\Models\TelegramBot::where('is_active', true)->first();
        if (!$bot) {
            return back()->with('error', 'No active Telegram bot found.');
        }

        try {
            $response = \Illuminate\Support\Facades\Http::attach(
                'document',
                file_get_contents($pdfPath),
                $fileName
            )->post("https://api.telegram.org/bot{$bot->bot_token}/sendDocument", [
                        'chat_id' => $bot->chat_id,
                        'caption' => "📊 Institutional Semester Results\nPeriod: {$academicYear} (Sem {$semester})\nGenerated by ATTENDAI Intelligence."
                    ]);

            unlink($pdfPath);
            return back()->with('success', 'Report sent to Telegram successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send Telegram report: ' . $e->getMessage());
        }
    }

    public function courses(Request $request)
    {
        $this->syncDatabaseSchema();
        $query = ClassRoom::with(['subject.department', 'teacher.user', 'groups.major.department']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('room_number', 'like', "%$q%")
                    ->orWhereHas('subject', function ($s) use ($q) {
                        $s->where('name', 'like', "%$q%");
                    })
                    ->orWhereHas('teacher.user', function ($u) use ($q) {
                        $u->where('name', 'like', "%$q%");
                    })
                    ->orWhereHas('groups', function ($g) use ($q) {
                        $g->where('name', 'like', "%$q%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', 'archived');
        }

        if ($request->filled('dept')) {
            $query->whereHas('subject', function ($q) use ($request) {
                $q->where('department_id', $request->dept);
            });
        }

        $allClasses = $query->latest()->get();

        $groupedClasses = [];
        foreach ($allClasses as $class) {
            $deptName = $class->subject->department->name ?? 'Unassigned Dept';
            foreach ($class->groups as $group) {
                $majorName = $group->major->name ?? 'General';
                $yearLevel = 'Year ' . ($group->year_level ?? 'N/A');
                $groupName = $group->name ?? 'Unknown Group';

                $groupedClasses[$deptName][$majorName][$yearLevel][$groupName][] = $class;
            }
            if ($class->groups->isEmpty()) {
                $groupedClasses[$deptName]['General']['N/A']['No Group'][] = $class;
            }
        }

        $subjects = Subject::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $classGroups = \App\Models\ClassGroup::orderBy('name')->get();
        $teachers = Teacher::with('user')->get()->sortBy('user.name');
        $students = Student::with(['user', 'major.department', 'group.major.department'])->get()->sortBy('user.name');

        $recentActivities = ActivityLog::orderBy('id', 'desc')->limit(5)->get()->map(function ($log) {
            return [
                'action' => $log->action,
                'target' => $log->target,
                'time' => $log->created_at->format('h:i A'),
                'type' => 'system'
            ];
        });

        if ($recentActivities->isEmpty()) {
            $recentActivities = Attendance::with(['student.user', 'session.classRoom.subject'])
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($att) {
                    return [
                        'action' => 'INSERT',
                        'target' => ($att->student && $att->student->user ? $att->student->user->name : 'Unknown') . ' @ ' . ($att->session && $att->session->classRoom && $att->session->classRoom->subject ? $att->session->classRoom->subject->name : 'Unknown'),
                        'time' => $att->created_at->format('h:i A'),
                        'type' => 'attendance'
                    ];
                });
        }

        return view('admin.courses', [
            'groupedClasses' => $groupedClasses,
            'classes' => $allClasses,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'students' => $students,
            'classGroups' => $classGroups,
            'departments' => $departments,
            'recentActivities' => $recentActivities
        ]);
    }

    public function classes(Request $request)
    {
        $this->syncDatabaseSchema();
        $query = ClassGroup::with(['major.department'])->withCount('students');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                    ->orWhere('year_level', 'like', "%$q%");
            });
        }

        $classGroups = $query->orderBy('name')->paginate(10)->appends($request->all());
        $majors = Major::with('department')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        return view('admin.groups', compact('classGroups', 'majors', 'departments'));
    }

    public function permissions(Request $request)
    {
        $query = \App\Models\StudentPermission::with(['student.user']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->whereHas('student.user', function ($u) use ($q) {
                $u->where('name', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%");
            })->orWhereHas('student', function ($s) use ($q) {
                $s->where('student_code', 'like', "%$q%");
            });
        }

        $permissions = $query->latest()->paginate(10)->appends($request->all());
        $students = Student::with(['user', 'major.department', 'group.major.department'])->get()->sortBy('user.name');

        return view('admin.permissions', compact('permissions', 'students'));
    }

    public function storePermission(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'type' => 'required|string'
        ]);

        \App\Models\StudentPermission::create($request->all());

        return redirect()->back()->with('success', 'Permission assigned successfully.');
    }

    public function destroyPermission($id)
    {
        \App\Models\StudentPermission::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Permission removed.');
    }

    public function settings()
    {
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        $bots = \App\Models\TelegramBot::latest()->get();

        // Get available years/semesters for reports
        $academicYears = \App\Models\SemesterAssignment::distinct()->pluck('academic_year');
        if ($academicYears->isEmpty())
            $academicYears = [date('Y') . '-' . (date('Y') + 1)];

        return view('admin.settings', compact('settings', 'bots', 'academicYears'));
    }

    public function exportSummaryReport(Request $request)
    {
        $request->validate([
            'academic_year' => 'required',
            'semester' => 'required|in:1,2',
            'type' => 'required|in:full,half',
            'action' => 'sometimes|in:download,telegram'
        ]);

        $action = $request->get('action', 'download');

        if ($action === 'telegram') {
            $telegram = app(TelegramService::class);
            $sent = $telegram->sendSystemSummaryReport(
                $request->academic_year,
                $request->semester,
                $request->type
            );

            if ($sent) {
                return redirect()->back()->with('success', 'Summary report has been sent to the active Telegram bot.');
            } else {
                return redirect()->back()->with('error', 'Failed to send Telegram report. Check bot configuration.');
            }
        }

        // Default: Download
        $fileName = "system_attendance_summary_" . $request->type . "_" . str_replace('/', '-', $request->academic_year) . "_S" . $request->semester . "_" . date('Ymd') . ".xlsx";

        return Excel::download(
            new \App\Exports\SystemSummaryExport($request->academic_year, $request->semester, $request->type),
            $fileName
        );
    }

    public function exportInstructors()
    {
        return Excel::download(new \App\Exports\InstructorsExport, 'instructors_' . date('Ymd') . '.xlsx');
    }

    public function exportStudents()
    {
        return Excel::download(new \App\Exports\StudentsExport, 'students_' . date('Ymd') . '.xlsx');
    }

    public function exportCourses()
    {
        return Excel::download(new \App\Exports\CoursesExport, 'courses_' . date('Ymd') . '.xlsx');
    }

    public function exportSubjects()
    {
        return Excel::download(new \App\Exports\SubjectsExport, 'subjects_' . date('Ymd') . '.xlsx');
    }

    public function exportDepartments()
    {
        return Excel::download(new \App\Exports\DepartmentsExport, 'departments_' . date('Ymd') . '.xlsx');
    }

    public function exportClasses()
    {
        return Excel::download(new \App\Exports\GroupsExport, 'classes_' . date('Ymd') . '.xlsx');
    }

    public function exportGroups()
    {
        return Excel::download(new \App\Exports\GroupsExport, 'groups_' . date('Ymd') . '.xlsx');
    }

    public function updateSettings(Request $request)
    {
        $settings = $request->except(['_token', 'app_logo']);

        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        if ($request->hasFile('app_logo')) {
            try {
                // Delete old local logo if it exists (handles both relative and full URLs)
                $oldLogo = \App\Models\Setting::get('app_logo');
                if ($oldLogo && (!str_starts_with($oldLogo, 'http') || (str_contains($oldLogo, 'localhost') && str_contains($oldLogo, '/branding/')))) {
                    $pathOnly = parse_url($oldLogo, PHP_URL_PATH);
                    $localPath = public_path(ltrim($pathOnly, '/'));
                    if (file_exists($localPath)) {
                        @unlink($localPath);
                    }
                }

                $file = $request->file('app_logo');
                Log::info('Attempting DIRECT Cloudinary upload for branding logo. Path: ' . $file->getRealPath());

                try {
                    // 🚀 DIRECT API CALL (Bypassing buggy SDK)
                    // These are your verified credentials
                    $cloudName = env('CLOUDINARY_CLOUD_NAME', 'dnrblpkal');
                    $apiKey = env('CLOUDINARY_API_KEY', '873455563278351');
                    $apiSecret = env('CLOUDINARY_API_SECRET', 'w0wjtbimWDc7WD1cd1p_Tob0kcc');

                    $timestamp = time();
                    $params = [
                        'folder' => 'branding',
                        'timestamp' => $timestamp,
                    ];

                    // Sort parameters alphabetically to match Cloudinary signature requirements
                    ksort($params);
                    $paramString = "";
                    foreach ($params as $key => $value) {
                        $paramString .= "$key=$value&";
                    }
                    $signatureString = rtrim($paramString, '&') . $apiSecret;
                    $signature = sha1($signatureString);

                    Log::info('Performing direct POST to Cloudinary API...');

                    $response = \Illuminate\Support\Facades\Http::attach(
                        'file',
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    )->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                                'api_key' => $apiKey,
                                'timestamp' => $timestamp,
                                'signature' => $signature,
                                'folder' => 'branding'
                            ]);

                    if (!$response->successful()) {
                        $errorMsg = $response->json()['error']['message'] ?? 'Connection Error';
                        Log::error('Cloudinary API rejected request: ' . $errorMsg);
                        throw new \Exception('Cloudinary says: ' . $errorMsg);
                    }

                    $secureUrl = $response->json()['secure_url'];
                    Log::info('DIRECT upload success: ' . $secureUrl);

                    \App\Models\Setting::updateOrCreate(
                        ['key' => 'app_logo'],
                        ['value' => $secureUrl]
                    );
                } catch (\Throwable $err) {
                    Log::error('Cloudinary DIRECT Core Error: ' . $err->getMessage());
                    throw $err;
                }
            } catch (\Throwable $e) {
                Log::error('Ultimate Upload Process Failed: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Upload failed: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    private function syncDatabaseSchema()
    {
        try {
            if (!\Schema::hasTable('majors')) {
                \Schema::create('majors', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('department_id')->nullable();
                    $table->string('name');
                    $table->string('code', 50)->nullable();
                    $table->timestamps();
                });
            }
            if (!\Schema::hasTable('class_groups')) {
                \Schema::create('class_groups', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('major_id')->nullable();
                    $table->string('name');
                    $table->integer('year_level')->nullable();
                    $table->timestamps();
                });
            }
            if (!\Schema::hasTable('class_class_group')) {
                \Schema::create('class_class_group', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('class_room_id');
                    $table->unsignedBigInteger('class_group_id');
                    $table->timestamps();
                });
            }
            if (!\Schema::hasColumn('students', 'group_id')) {
                \Schema::table('students', function ($table) {
                    $table->unsignedBigInteger('group_id')->nullable();
                });
            }
            if (!\Schema::hasColumn('classes', 'group_id')) {
                \Schema::table('classes', function ($table) {
                    $table->unsignedBigInteger('group_id')->nullable();
                });
            }
            if (!\Schema::hasColumn('classes', 'academic_year')) {
                \Schema::table('classes', function ($table) {
                    $table->string('academic_year')->nullable();
                });
            }
            if (!\Schema::hasColumn('classes', 'semester')) {
                \Schema::table('classes', function ($table) {
                    $table->integer('semester')->nullable();
                });
            }
        } catch (\Exception $e) {
            Log::error("Schema Sync Error: " . $e->getMessage());
        }
    }
}

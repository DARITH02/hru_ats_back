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
            $query->where(function($w) use ($q) {
                $w->whereHas('user', function($u) use ($q) {
                    $u->where('name', 'like', "%$q%")
                      ->orWhere('email', 'like', "%$q%");
                })->orWhereHas('department', function($d) use ($q) {
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
            $query->where(function($w) use ($q) {
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
            $query->where(function($w) use ($q) {
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
            $query->where(function($w) use ($q) {
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
            $query->where(function($w) use ($q) {
                $w->where('student_code', 'like', "%$q%")
                  ->orWhereHas('user', function($u) use ($q) {
                      $u->where('name', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%");
                  })
                  ->orWhereHas('group', function($g) use ($q) {
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

    public function courses(Request $request)
    {
        $this->syncDatabaseSchema();
        $query = ClassRoom::with(['subject', 'teacher.user', 'group'])->withCount('students');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($w) use ($q) {
                $w->where('room_number', 'like', "%$q%")
                  ->orWhereHas('subject', function($s) use ($q) {
                      $s->where('name', 'like', "%$q%");
                  })
                  ->orWhereHas('teacher.user', function($u) use ($q) {
                      $u->where('name', 'like', "%$q%");
                  })
                  ->orWhereHas('group', function($g) use ($q) {
                      $g->where('name', 'like', "%$q%");
                  });
            });
        }

        $classes = $query->latest()->paginate(10)->appends($request->all());
        $subjects = Subject::orderBy('name')->get();
        $classGroups = \App\Models\ClassGroup::orderBy('name')->get();
        $teachers = Teacher::with('user')->get()->sortBy('user.name');
        $students = Student::with(['user', 'major.department', 'group.major.department'])->get()->sortBy('user.name');
        
        // Fetch last 5 activities for sidebar
        $recentActivities = ActivityLog::orderBy('id', 'desc')->limit(5)->get()->map(function($log) {
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
                ->map(function($att) {
                    return [
                        'action' => 'INSERT',
                        'target' => ($att->student && $att->student->user ? $att->student->user->name : 'Unknown') . ' @ ' . ($att->session && $att->session->classRoom && $att->session->classRoom->subject ? $att->session->classRoom->subject->name : 'Unknown'),
                        'time' => $att->created_at->format('h:i A'),
                        'type' => 'attendance'
                    ];
                });
        }
        
        return view('admin.courses', compact('classes', 'subjects', 'teachers', 'students', 'classGroups', 'recentActivities'));
    }

    public function classes(Request $request)
    {
        $this->syncDatabaseSchema();
        $query = ClassGroup::with(['major.department'])->withCount('students');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($w) use ($q) {
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
            $query->whereHas('student.user', function($u) use ($q) {
                $u->where('name', 'like', "%$q%")
                  ->orWhere('email', 'like', "%$q%");
            })->orWhereHas('student', function($s) use ($q) {
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
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string',
            'type'       => 'required|string'
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
        if ($academicYears->isEmpty()) $academicYears = [date('Y') . '-' . (date('Y')+1)];
        
        return view('admin.settings', compact('settings', 'bots', 'academicYears'));
    }

    public function exportSummaryReport(Request $request)
    {
        $request->validate([
            'academic_year' => 'required',
            'semester'      => 'required|in:1,2',
            'type'          => 'required|in:full,half',
            'action'        => 'sometimes|in:download,telegram'
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
                    $apiKey    = env('CLOUDINARY_API_KEY', '873455563278351');
                    $apiSecret = env('CLOUDINARY_API_SECRET', 'w0wjtbimWDc7WD1cd1p_Tob0kcc');
                    
                    $timestamp = time();
                    $params = [
                        'folder'    => 'branding',
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
                        'api_key'   => $apiKey,
                        'timestamp' => $timestamp,
                        'signature' => $signature,
                        'folder'    => 'branding'
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

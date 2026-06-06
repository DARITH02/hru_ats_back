@extends('layouts.app')

@section('content')
    @php
        $sessionRate = $totalCount > 0 ? round(($presentCount / $totalCount) * 100) : 0;
        $absentNow = max(0, $totalCount - $presentCount);
        $criticalStudentCount = $topAbsentStudents->count();
        $criticalClassCount = $topAbsentClasses->count();
        $isDemoUser = Auth::user()?->email === 'demo@example.com';
        $activeSubject = $activeSession?->classRoom?->subject?->name ?? 'No active session';
        $activeGroups = $activeSession?->classRoom?->groups?->pluck('name')->filter()->join(', ') ?: 'No group selected';
        $hasSessionActivity = $presentCount > 0 || $sessionScanCount > 0;
    @endphp

    <section class="dashboard-hero">
        <div class="dashboard-hero__content">
            <div class="breadcrumb">
                <span>OVERVIEW</span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current">ATTENDANCE COMMAND CENTER</span>
            </div>
            <div class="dashboard-hero__title-row">
                <div>
                    <h1 class="page-title dashboard-hero__title">Dashboard Summary</h1>
                    <p class="dashboard-hero__subtitle">
                        Monitor attendance health, active sessions, QR scans, academic risks, and management modules from one workspace.
                    </p>
                </div>
                @if($isDemoUser)
                    <div class="demo-mode-badge">
                        <span class="demo-mode-badge__dot"></span>
                        READ-ONLY DEMO
                    </div>
                @endif
            </div>

            <div class="dashboard-hero__meta">
                <div class="hero-meta-item">
                    <span>ACTIVE SESSION</span>
                    <strong>{{ $activeSubject }}</strong>
                </div>
                <div class="hero-meta-item">
                    <span>ASSIGNED GROUP</span>
                    <strong>{{ $activeGroups }}</strong>
                </div>
                <div class="hero-meta-item">
                    <span>SESSION COVERAGE</span>
                    <strong>{{ $presentCount }}/{{ $totalCount }} marked</strong>
                </div>
            </div>
        </div>

        <div class="dashboard-hero__status">
            <div class="session-ring" style="--value: {{ $sessionRate }};">
                <div class="session-ring__inner">
                    <strong>{{ $sessionRate }}%</strong>
                    <span>{{ $hasSessionActivity ? 'SESSION' : 'WAITING DATA' }}</span>
                </div>
            </div>
            <div class="session-status-list">
                <div>
                    <span class="status-dot status-dot--green"></span>
                    <span>Present or excused</span>
                    <strong>{{ $presentCount }}</strong>
                </div>
                <div>
                    <span class="status-dot status-dot--red"></span>
                    <span>Not marked</span>
                    <strong>{{ $absentNow }}</strong>
                </div>
                <div>
                    <span class="status-dot status-dot--blue"></span>
                    <span>QR scans</span>
                    <strong>{{ $sessionScanCount }}</strong>
                </div>
                @unless($hasSessionActivity)
                    <p class="session-status-note">Updates after the first attendance mark or QR scan.</p>
                @endunless
            </div>
        </div>
    </section>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-glow"></div>
            <div class="stat-label">ACTIVE STUDENTS</div>
            <div class="stat-value" id="stat-students">{{ $stats['students'] }}</div>
            <div class="stat-pill pill-blue">ENROLLED</div>
        </div>
        <div class="stat-card green">
            <div class="stat-glow"></div>
            <div class="stat-label">TOTAL ATTENDANCE</div>
            <div class="stat-value" id="stat-attendance">{{ $stats['attendance_rate'] }}</div>
            <div class="stat-pill pill-up">HEALTH RATE</div>
        </div>
        <div class="stat-card amber">
            <div class="stat-glow"></div>
            <div class="stat-label">PENDING SESSIONS</div>
            <div class="stat-value" id="stat-sessions">{{ $stats['pending_sessions'] }}</div>
            <div class="stat-pill pill-amber">IN QUEUE</div>
        </div>
        <div class="stat-card red">
            <div class="stat-glow"></div>
            <div class="stat-label">ABSENCE RATE</div>
            <div class="stat-value">{{ $stats['absence_rate'] }}</div>
            <div class="stat-pill pill-down">{{ $criticalStudentCount }} STUDENTS FLAGGED</div>
        </div>
    </div>

    <section class="summary-workbench">
        <div class="summary-panel summary-panel--wide">
            <div class="summary-panel__head">
                <div>
                    <span class="summary-eyebrow">Operational Snapshot</span>
                    <h2>Today’s attendance posture</h2>
                </div>
                <span class="summary-chip">{{ $classes->count() }} sessions visible</span>
            </div>
            <div class="summary-metrics">
                <div class="summary-metric">
                    <span>Live or upcoming sessions</span>
                    <strong>{{ $stats['pending_sessions'] }}</strong>
                </div>
                <div class="summary-metric">
                    <span>Students in selected session</span>
                    <strong>{{ $totalCount }}</strong>
                </div>
                <div class="summary-metric">
                    <span>Critical students</span>
                    <strong>{{ $criticalStudentCount }}</strong>
                </div>
                <div class="summary-metric">
                    <span>Critical classes</span>
                    <strong>{{ $criticalClassCount }}</strong>
                </div>
            </div>
        </div>

        <div class="summary-panel">
            <div class="summary-panel__head">
                <div>
                    <span class="summary-eyebrow">Modules</span>
                    <h2>Quick access</h2>
                </div>
            </div>
            <div class="summary-actions">
                <a href="{{ route('admin.students') }}">Students</a>
                <a href="{{ route('admin.instructors') }}">Instructors</a>
                <a href="{{ route('admin.courses') }}">Classes</a>
                <a href="{{ route('admin.attendance-issues') }}">Attendance Issues</a>
            </div>
        </div>
    </section>

    <div class="main-grid">
        <!-- LEFT: CLASSES & TABLE -->
        <div class="left-col">
            <div class="panel">
                <div class="panel-head">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:8px; height:8px; border-radius:50%; background:var(--green); box-shadow:0 0 8px var(--green); animation:blink 2s infinite;"></div>
                        <span class="panel-title">LIVE MONITORING</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <span style="font-family:var(--font-mono); font-size:9px; color:var(--muted2);">{{ strtoupper($activeSession?->classRoom?->subject?->name ?? 'NO SESSION') }}</span>
                        <div style="display:flex; align-items:center; gap:5px; background:var(--surface3); padding:2px 8px; border-radius:4px; border:1px solid var(--border);">
                             <span style="font-family:var(--font-mono); font-size:9px; color:var(--green); font-weight:800;">{{ $presentCount }}/{{ $totalCount }}</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="att-table">
                        <thead>
                            <tr>
                                <th>STUDENT IDENTITY</th>
                                <th>CODE</th>
                                <th>TIME</th>
                                <th>STATUS</th>
                                <th style="text-align:right">METHOD</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeStudents as $student)
                                <tr class="fade-up">
                                    <td>
                                        <div class="subject-cell">
                                            <div class="subject-avatar" style="background:{{ $student['avatar_color'] }}22; color:{{ $student['avatar_color'] }}; width:32px; height:32px; font-size:10px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; border:1px solid {{ $student['avatar_color'] }}44;">
                                                {{ $student['initials'] }}
                                            </div>
                                            <div>
                                                <div class="subject-name" style="font-size:13px; font-weight:700;">{{ $student['name'] }}</div>
                                                <div class="subject-id" style="font-size:9px; color:var(--muted2);">YEAR {{ $student['year'] }} · {{ $student['major'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-family:var(--font-mono); font-size:10px; color:var(--accent); font-weight:700;">{{ $student['code'] }}</td>
                                    <td style="font-family:var(--font-mono); font-size:10px; color:var(--text2);">{{ $student['time'] }}</td>
                                    <td>
                                        @if($student['status'] === 'present' || $student['status'] === 'PRESENT')
                                            <span class="status-tag tag-active">PRESENT</span>
                                        @elseif($student['status'] === 'late' || $student['status'] === 'LATE')
                                            <span class="status-tag" style="background:var(--amber)22; color:var(--amber); border:1px solid var(--amber)44">LATE</span>
                                        @elseif($student['status'] === 'excused' || $student['status'] === 'EXCUSED')
                                            <span class="status-tag" style="background:var(--accent)22; color:var(--accent); border:1px solid var(--accent)44; cursor:help;" title="REASON: {{ $student['permission'] ?? 'Excused' }}">EXCUSED</span>
                                        @else
                                            <span class="status-tag" style="background:var(--red)22; color:var(--red); border:1px solid var(--red)44">ABSENT</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right; font-family:var(--font-mono); font-size:9px; color:var(--muted2); font-weight:700;">{{ strtoupper($student['method']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align:center; padding:50px 0; color:var(--muted2); font-size:11px; font-family:var(--font-mono); letter-spacing:0.05em;">NO STUDENTS REGISTERED IN THIS SESSION</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel monitor-panel" style="margin-top: 20px;">
                @php
                    $monitorClasses = collect($monitorSubjects);
                    $monitorAvg = $monitorClasses->count() ? round($monitorClasses->avg('progress')) : 0;
                    $monitorStrongest = $monitorClasses->sortByDesc('progress')->first();
                    $monitorNeedsAttention = $monitorClasses->filter(fn($class) => $class['progress'] < 60)->count();
                    $selectedMajor = $selectedMajorId ? $majorOptions->firstWhere('id', $selectedMajorId) : null;
                @endphp
                <div class="monitor-head">
                    <div>
                        <div class="monitor-kicker">
                            <span class="monitor-pulse"></span>
                            MONITOR DATA
                        </div>
                        <h2>Overall class progress</h2>
                    </div>
                    <div class="monitor-score">
                        <span>Average</span>
                        <strong>{{ $monitorAvg }}%</strong>
                    </div>
                </div>

                <form method="GET" action="{{ route('dashboard') }}" class="monitor-filter">
                    <label for="major_id">Progress by major</label>
                    <select id="major_id" name="major_id" onchange="this.form.submit()">
                        <option value="">All majors</option>
                        @foreach($majorOptions as $major)
                            <option value="{{ $major->id }}" @selected($selectedMajorId === $major->id)>
                                {{ $major->name }}{{ $major->code ? ' · ' . $major->code : '' }}
                            </option>
                        @endforeach
                    </select>
                    @if($selectedMajor)
                        <a href="{{ route('dashboard') }}">Clear</a>
                    @endif
                </form>

                <div class="monitor-insights">
                    <div class="monitor-insight">
                        <span>Subjects tracked</span>
                        <strong>{{ $monitorClasses->count() }}</strong>
                    </div>
                    <div class="monitor-insight">
                        <span>Strongest subject</span>
                        <strong>{{ $monitorStrongest['name'] ?? 'None' }}</strong>
                    </div>
                    <div class="monitor-insight monitor-insight--warn">
                        <span>Needs attention</span>
                        <strong>{{ $monitorNeedsAttention }}</strong>
                    </div>
                </div>

                <div class="monitor-legend">
                    <span><i class="legend-dot legend-dot--present"></i> Attended</span>
                    <span><i class="legend-dot legend-dot--missing"></i> Missing</span>
                    <span><i class="legend-line"></i> Target 80%</span>
                </div>

                <div class="monitor-chart-wrap">
                    <canvas id="monitor-chart"></canvas>
                </div>

                <div class="subject-monitor">
                    <div class="subject-monitor__head">
                        <span>Subject monitor</span>
                        <strong>{{ $selectedMajor?->name ?? 'All majors' }}</strong>
                    </div>
                    <div class="subject-monitor__list">
                        @forelse($monitorClasses as $subject)
                            <div class="subject-monitor__row">
                                <div>
                                    <strong>{{ $subject['name'] }}</strong>
                                    <span>{{ $subject['majors'] }} · {{ $subject['teacher'] }}</span>
                                </div>
                                <em>{{ $subject['sessions'] }} sessions</em>
                                <div class="subject-monitor__progress">
                                    <i style="width: {{ min(100, max(0, $subject['progress'])) }}%;"></i>
                                </div>
                                <b>{{ $subject['progress'] }}%</b>
                            </div>
                        @empty
                            <div class="subject-monitor__empty">No subjects found for this major.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="panel" style="margin-top: 20px;">
                <div class="panel-head">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div
                            style="width:8px; height:8px; border-radius:50%; background:var(--red); box-shadow:0 0 8px var(--red);">
                        </div>
                        <span class="panel-title">HIGH ABSENCE STUDENTS</span>
                    </div>
                    <span style="font-family:var(--font-mono); font-size:9px; color:var(--muted2);">TOP 5 CRITICAL</span>
                </div>
                <div style="padding: 10px 0;">
                    @forelse($topAbsentStudents as $student)
                        <div class="class-row" style="cursor: default; border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <div class="row-icon"
                                style="background: rgba(239, 68, 68, 0.1); color: #ef4444; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
                                {{ $student['initials'] }}
                            </div>
                            <div class="row-info">
                                <div class="row-name">{{ $student['name'] }}</div>
                                <div class="row-meta">{{ $student['absent_count'] }} SESSIONS MISSED</div>
                            </div>
                            <div style="text-align: right; padding-right: 15px;">
                                <div style="font-size: 11px; font-weight: 900; color: #ef4444;">{{ $student['absence_rate'] }}%
                                </div>
                                <div style="font-size: 8px; color: var(--muted2); font-family: var(--font-mono);">ABSENCE RATE
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 20px; text-align: center; color: var(--muted2); font-size: 11px;">NO CRITICAL
                            ABSENCES DETECTED</div>
                    @endforelse
                </div>
            </div>

            <div class="panel" style="margin-top: 20px;">
                <div class="panel-head">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div
                            style="width:8px; height:8px; border-radius:50%; background:var(--amber); box-shadow:0 0 8px var(--amber);">
                        </div>
                        <span class="panel-title">HIGH ABSENCE CLASSES</span>
                    </div>
                    <span style="font-family:var(--font-mono); font-size:9px; color:var(--muted2);">CRITICAL SUBJECTS</span>
                </div>
                <div style="padding: 10px 0;">
                    @forelse($topAbsentClasses as $class)
                        <div class="class-row" style="cursor: default; border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <div class="row-icon icon-amber">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="row-info">
                                <div class="row-name">{{ $class['name'] }}</div>
                                <div class="row-meta">{{ $class['teacher'] }}</div>
                            </div>
                            <div style="text-align: right; padding-right: 15px;">
                                <div style="font-size: 11px; font-weight: 900; color: var(--amber);">
                                    {{ $class['absence_rate'] }}%</div>
                                <div style="font-size: 8px; color: var(--muted2); font-family: var(--font-mono);">AVG ABSENCE
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 20px; text-align: center; color: var(--muted2); font-size: 11px;">ALL CLASSES
                            PERFORMING WELL</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RIGHT: QR & LOGS -->
        <div class="right-col">
            <div class="panel year-panel" style="margin-bottom: 20px;">
                @php
                    $yearCollection = collect($yearStats);
                    $yearAverage = $yearCollection->count() ? round($yearCollection->avg()) : 0;
                    $topYear = $yearCollection->sortDesc()->keys()->first();
                    $topYearScore = $topYear ? $yearCollection->get($topYear) : 0;
                @endphp
                <div class="year-head">
                    <div>
                        <div class="year-kicker">
                            <span class="year-pulse"></span>
                            YEAR LEVEL PERFORMANCE
                        </div>
                        <h2>Academic year comparison</h2>
                    </div>
                    <div class="year-score">
                        <span>Average</span>
                        <strong>{{ $yearAverage }}%</strong>
                    </div>
                </div>
                <div class="year-rank">
                    <span>Top cohort</span>
                    <strong>Year {{ $topYear ?? '-' }}</strong>
                    <em>{{ $topYearScore }}%</em>
                </div>
                <div class="year-cards">
                    @foreach($yearStats as $year => $rate)
                        <div class="year-card {{ $rate >= 80 ? 'year-card--strong' : ($rate >= 60 ? 'year-card--steady' : 'year-card--risk') }}">
                            <div>
                                <span>Year {{ $year }}</span>
                                <strong>{{ $rate }}%</strong>
                            </div>
                            <div class="year-card__bar">
                                <i style="width: {{ min(100, max(0, $rate)) }}%;"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="year-chart-wrap">
                    <canvas id="year-chart"></canvas>
                </div>
            </div>

            <div class="panel" style="margin-bottom: 20px;">
                <div class="panel-head">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div class="db-dot" style="background:var(--amber); box-shadow:0 0 8px var(--amber);"></div>
                        <span class="panel-title">GLOBAL SUMMARY</span>
                    </div>
                    <span style="font-family:var(--font-mono); font-size:9px; color:var(--muted2);">OVERALL STATS</span>
                </div>
                <div style="padding:15px; position:relative; min-height:180px; display:flex; justify-content:center;">
                    <canvas id="summary-chart"></canvas>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div class="db-dot" style="background:var(--accent); box-shadow:0 0 8px var(--accent);"></div>
                        <span class="panel-title">LIVE SCAN MONITORING</span>
                    </div>
                    <span style="font-family:var(--font-mono); font-size:9px; color:var(--muted2);">REAL-TIME SCANS</span>
                </div>
                <div style="padding:15px;">
                    <canvas id="sys-chart" style="height:140px !important;"></canvas>
                </div>
            </div>
        </div>
    </div>

    @php
        $majorComparisonCollection = collect($majorComparison);
        $majorTotalStudents = $majorComparisonCollection->sum('students');
        $majorAverageAttendance = $majorComparisonCollection->count() ? round($majorComparisonCollection->avg('attendance_rate')) : 0;
        $majorTop = $majorComparisonCollection->sortByDesc('attendance_rate')->first();
        $majorNeedsAttention = $majorComparisonCollection->where('attendance_rate', '<', 60)->count();
        $sessionGraphStatus = $activeSession
            ? (now()->between($activeSession->start_time, $activeSession->end_time) ? 'Live session' : ucfirst($activeSession->status ?? 'Session selected'))
            : 'No session';
    @endphp

    <section class="dashboard-chat-panel dashboard-graph-console">
        <div class="dashboard-chat-left">
            <div class="dashboard-chat-head">
                <div>
                    <div class="dashboard-chat-kicker">
                        <span></span>
                        SESSION INSIGHTS
                    </div>
                    <h2>{{ $activeSession?->classRoom?->subject?->name ?? 'Attendance overview' }}</h2>
                </div>
                <div class="dashboard-chat-status">{{ $sessionGraphStatus }}</div>
            </div>

            <div class="dashboard-graph-summary">
                <div class="graph-summary-card graph-summary-card--wide">
                    <span>Selected session</span>
                    <strong>{{ $activeSession?->classRoom?->subject?->name ?? 'None' }}</strong>
                    <div class="graph-summary-track">
                        <i style="width: {{ $totalCount > 0 ? round(($presentCount / $totalCount) * 100) : 0 }}%;"></i>
                    </div>
                </div>
                <div class="graph-summary-card">
                    <span>Marked</span>
                    <strong>{{ $presentCount }}/{{ $totalCount }}</strong>
                </div>
                <div class="graph-summary-card">
                    <span>Scans</span>
                    <strong>{{ $sessionScanCount }}</strong>
                </div>
                <div class="graph-summary-card">
                    <span>Total students</span>
                    <strong>{{ $majorTotalStudents }}</strong>
                </div>
                <div class="graph-summary-card">
                    <span>Majors</span>
                    <strong>{{ $majorComparisonCollection->count() }}</strong>
                </div>
                <div class="graph-summary-card">
                    <span>Avg health</span>
                    <strong>{{ $majorAverageAttendance }}%</strong>
                </div>
                <div class="graph-summary-card graph-summary-card--alert">
                    <span>Attention</span>
                    <strong>{{ $majorNeedsAttention }}</strong>
                </div>
            </div>
        </div>

        <div class="dashboard-chat-compare dashboard-chat-compare--full">
            <div class="dashboard-chat-compare__head">
                <div>
                    <span>Major comparison</span>
                    <strong>Attendance health by major</strong>
                </div>
                <div class="dashboard-chat-compare__metrics">
                    <em>{{ $majorAverageAttendance }}% AVG</em>
                    <em>{{ $majorTop['code'] ?? 'TOP' }} {{ $majorTop['attendance_rate'] ?? 0 }}%</em>
                    <em class="is-risk">{{ $majorNeedsAttention }} WATCH</em>
                </div>
            </div>
            <div class="dashboard-graph-board">
                <div class="graph-tile graph-tile--wide graph-tile--success">
                    <span>Major trend</span>
                    <canvas id="major-chat-chart"></canvas>
                </div>
                <div class="graph-tile graph-tile--blue">
                    <span>Subject curve</span>
                    <canvas id="subject-spark-chart"></canvas>
                </div>
                <div class="graph-tile graph-tile--amber">
                    <span>Year radar</span>
                    <canvas id="year-mini-chart"></canvas>
                </div>
                <div class="graph-tile graph-tile--teal">
                    <span>Scan pulse</span>
                    <canvas id="scan-pulse-chart"></canvas>
                </div>
                <div class="graph-tile graph-tile--wide graph-tile--danger">
                    <span>Attention profile</span>
                    <canvas id="attention-profile-chart"></canvas>
                </div>
            </div>
            <div class="dashboard-major-list">
                @forelse($majorComparisonCollection as $major)
                    <div class="dashboard-major-row {{ $major['attendance_rate'] >= 80 ? 'major-row--strong' : ($major['attendance_rate'] >= 60 ? 'major-row--steady' : 'major-row--risk') }}">
                        <div>
                            <strong>{{ $major['name'] }}</strong>
                            <span>{{ $major['students'] }} students · {{ $major['sessions'] }} sessions</span>
                        </div>
                        <div class="dashboard-major-bar">
                            <i style="width: {{ min(100, max(0, $major['attendance_rate'])) }}%;"></i>
                        </div>
                        <b>{{ $major['attendance_rate'] }}%</b>
                    </div>
                @empty
                    <div class="dashboard-major-empty">No major data available.</div>
                @endforelse
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const sessionId = @json($activeSession?->id);

        async function manualCheckin(studentId) {
            if (!sessionId) return;
            try {
                const res = await fetch(`/api/teacher/session/${sessionId}/checkin`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ student_id: studentId })
                });
                const data = await res.json();
                if (data.success) { showToast(data.message); refreshMonitor(); }
            } catch (e) { console.error(e); }
        }

        async function deleteAttendance(attendanceId) {
            if (!attendanceId) return;
            if (!confirm('Permanent delete attendance record?')) return;
            try {
                const res = await fetch(`/api/teacher/attendance/${attendanceId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) { showToast(data.message); refreshMonitor(); }
            } catch (e) { console.error(e); }
        }

        async function globalSkipToday() {
            if (!confirm('DANGER: This will mark ALL scheduled sessions for TODAY as SKIPPED and shift their entire future schedule forward by 1 week. Other days (like tomorrow) will NOT be affected. Proceed?')) return;

            try {
                const res = await fetch('/api/admin/skip-today-shift', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.error || 'Operation failed', 'error');
                }
            } catch (e) {
                showToast('Network error', 'error');
            }
        }

        const majorComparison = @json($majorComparison);
        const monitorSubjects = @json($monitorSubjects);
        const miniChartBase = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111722',
                    titleColor: '#e8eaf2',
                    bodyColor: '#b0b8cc',
                    borderColor: '#263144',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(136,146,164,.1)' },
                    ticks: { color: '#6f7a8e', font: { family: "'IBM Plex Mono', monospace", size: 8 } }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(136,146,164,.12)' },
                    ticks: { color: '#6f7a8e', font: { family: "'IBM Plex Mono', monospace", size: 8 } }
                }
            }
        };

        function makeGradient(ctx, color) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 140);
            gradient.addColorStop(0, color);
            gradient.addColorStop(1, 'rgba(8,10,15,0)');
            return gradient;
        }

        const majorChatCtx = document.getElementById('major-chat-chart');
        if (majorChatCtx) {
            const ctx = majorChatCtx.getContext('2d');
            new Chart(majorChatCtx, {
                type: 'line',
                data: {
                    labels: majorComparison.map(m => m.code || m.name),
                    datasets: [
                        {
                            label: 'Attendance',
                            data: majorComparison.map(m => m.attendance_rate),
                            borderColor: '#34d399',
                            backgroundColor: makeGradient(ctx, 'rgba(52,211,153,.32)'),
                            borderWidth: 2,
                            pointRadius: 3,
                            pointBackgroundColor: '#34d399',
                            tension: .36,
                            fill: true
                        },
                        {
                            label: 'Absence',
                            data: majorComparison.map(m => m.absence_rate),
                            borderColor: '#f25757',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointBackgroundColor: '#f25757',
                            tension: .36,
                            fill: false
                        }
                    ]
                },
                options: miniChartBase
            });
        }

        const subjectSparkCtx = document.getElementById('subject-spark-chart');
        if (subjectSparkCtx) {
            new Chart(subjectSparkCtx, {
                type: 'line',
                data: {
                    labels: monitorSubjects.slice(0, 10).map((s, i) => `S${i + 1}`),
                    datasets: [{
                        data: monitorSubjects.slice(0, 10).map(s => s.progress),
                        borderColor: '#4f8ef7',
                        backgroundColor: 'rgba(79,142,247,.18)',
                        borderWidth: 2,
                        pointRadius: 0,
                        tension: .42,
                        fill: true
                    }]
                },
                options: miniChartBase
            });
        }

        const yearMiniCtx = document.getElementById('year-mini-chart');
        if (yearMiniCtx) {
            new Chart(yearMiniCtx, {
                type: 'radar',
                data: {
                    labels: ['Y1', 'Y2', 'Y3', 'Y4'],
                    datasets: [{
                        data: Object.values(@json($yearStats)),
                        borderColor: '#f0a732',
                        backgroundColor: 'rgba(240,167,50,.18)',
                        pointBackgroundColor: '#f0a732',
                        pointRadius: 3,
                        borderWidth: 2
                    }]
                },
                options: {
                    ...miniChartBase,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { display: false },
                            grid: { color: 'rgba(136,146,164,.16)' },
                            angleLines: { color: 'rgba(136,146,164,.14)' },
                            pointLabels: { color: '#8892a4', font: { family: "'IBM Plex Mono', monospace", size: 8 } }
                        }
                    }
                }
            });
        }

        const scanPulseCtx = document.getElementById('scan-pulse-chart');
        if (scanPulseCtx) {
            const rawTimesMini = {!! json_encode($activeStudents->whereIn('status', ['present', 'late'])->pluck('time')->filter(fn($t) => $t !== '—')->sort()->values()) !!};
            const scanPoints = rawTimesMini.map((_, i) => i + 1);
            new Chart(scanPulseCtx, {
                type: 'line',
                data: {
                    labels: scanPoints.map((_, i) => i + 1),
                    datasets: [{
                        data: scanPoints,
                        borderColor: '#38d9a9',
                        backgroundColor: 'rgba(56,217,169,.14)',
                        borderWidth: 2,
                        pointRadius: 0,
                        tension: .45,
                        fill: true
                    }]
                },
                options: {
                    ...miniChartBase,
                    scales: {
                        x: { display: false },
                        y: { display: false, beginAtZero: true }
                    }
                }
            });
        }

        const attentionCtx = document.getElementById('attention-profile-chart');
        if (attentionCtx) {
            new Chart(attentionCtx, {
                type: 'bar',
                data: {
                    labels: majorComparison.map(m => m.code || m.name),
                    datasets: [{
                        label: 'Attention',
                        data: majorComparison.map(m => m.absence_rate),
                        backgroundColor: majorComparison.map(m => m.absence_rate > 40 ? 'rgba(242,87,87,.75)' : 'rgba(240,167,50,.55)'),
                        borderColor: majorComparison.map(m => m.absence_rate > 40 ? '#f25757' : '#f0a732'),
                        borderWidth: 1,
                        borderRadius: 7
                    }]
                },
                options: miniChartBase
            });
        }

        // MONITOR CHART LOGIC (Replaced Chat)
        const monitorCtx = document.getElementById('monitor-chart');
        if (monitorCtx) {
            const fullLabels = monitorSubjects.map(subject => subject.name);
            const classLabels = fullLabels.map(l => l.length > 20 ? l.substring(0, 20) + '...' : l);
            const attendData = monitorSubjects.map(subject => subject.progress);
            const absentData = attendData.map(p => 100 - p);

            new Chart(monitorCtx, {
                type: 'bar',
                data: {
                    labels: classLabels,
                    datasets: [
                        {
                            label: 'Attended %',
                            data: attendData,
                            backgroundColor: 'rgba(52, 211, 153, 0.88)',
                            borderColor: '#34d399',
                            borderWidth: 1,
                            borderRadius: 8,
                            barPercentage: 0.62,
                            categoryPercentage: 0.72
                        },
                        {
                            label: 'Missing/Absent %',
                            data: absentData,
                            backgroundColor: 'rgba(242, 87, 87, 0.24)',
                            borderColor: 'rgba(242, 87, 87, 0.5)',
                            borderWidth: 1,
                            borderRadius: 8,
                            barPercentage: 0.62,
                            categoryPercentage: 0.72
                        },
                        {
                            label: 'Target',
                            data: attendData.map(() => 80),
                            type: 'line',
                            borderColor: '#f0a732',
                            borderWidth: 2,
                            borderDash: [6, 5],
                            pointRadius: 0,
                            pointHitRadius: 8,
                            tension: 0.2,
                            fill: false,
                            yAxisID: 'targetY',
                            order: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f1117',
                            titleColor: '#e8eaf2',
                            bodyColor: '#b0b8cc',
                            padding: 12,
                            borderColor: '#242c3d',
                            borderWidth: 1,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: {
                                color: '#8892a4',
                                font: { family: "'IBM Plex Mono', monospace", size: 9, weight: '600' },
                                maxRotation: 20,
                                minRotation: 0
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            max: 100,
                            grid: { color: 'rgba(255,255,255,0.06)', drawBorder: false },
                            ticks: {
                                stepSize: 20,
                                color: '#8892a4',
                                font: { family: "'IBM Plex Mono', monospace", size: 9 },
                                callback: function (value) { return value + '%' }
                            }
                        },
                        targetY: {
                            display: false,
                            beginAtZero: true,
                            max: 100,
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // GLOBAL SUMMARY LOGIC
        const summaryCtx = document.getElementById('summary-chart');
        if (summaryCtx) {
            const attRate = parseFloat('{{ $stats['attendance_rate'] }}') || 0;
            const absRate = parseFloat('{{ $stats['absence_rate'] }}') || 0;

            new Chart(summaryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Attendance', 'Absence'],
                    datasets: [{
                        data: [attRate, absRate],
                        backgroundColor: ['#10b981', '#ef4444'], // Solid Emerald and Red
                        borderColor: '#111318',
                        borderWidth: 3,
                        cutout: '75%',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'bottom', labels: { color: '#8892a4', usePointStyle: true, font: { family: "'IBM Plex Mono', monospace", size: 10 } } },
                        tooltip: { backgroundColor: '#111318', titleColor: '#fff', bodyColor: '#8892a4', padding: 12, borderColor: '#1e2330', borderWidth: 1 }
                    }
                }
            });
        }

        // LIVE SCAN MONITORING LOGIC (Real Data)
        const ctx = document.getElementById('sys-chart');
        if (ctx) {
            const rawTimes = {!! json_encode($activeStudents->whereIn('status', ['present', 'late'])->pluck('time')->filter(fn($t) => $t !== '—')->sort()->values()) !!};

            const scanFreq = {};
            rawTimes.forEach(t => { scanFreq[t] = (scanFreq[t] || 0) + 1; });

            const sysChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: Object.keys(scanFreq),
                    datasets: [{
                        label: 'Successful Scans',
                        data: Object.values(scanFreq),
                        borderColor: '#3b82f6', // Solid Azure Blue
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#111318',
                        pointBorderWidth: 2,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#111318', titleColor: '#fff', bodyColor: '#8892a4', padding: 12, borderColor: '#1e2330', borderWidth: 1, displayColors: false }
                    },
                    scales: {
                        x: { display: true, grid: { display: false }, ticks: { color: '#8892a4', maxTicksLimit: 6, font: { family: "'IBM Plex Mono', monospace", size: 9 } } },
                        y: {
                            display: false,
                            beginAtZero: true,
                            suggestedMax: Math.max(0, ...Object.values(scanFreq)) + 2
                        }
                    }
                }
            });

            // Ensure refreshMonitor exists for manual buttons
            window.refreshMonitor = function () { window.location.reload(); };
        }

        // YEAR PERFORMANCE CHART
        const yearCtx = document.getElementById('year-chart');
        if (yearCtx) {
            const yearValues = Object.values(@json($yearStats));
            new Chart(yearCtx, {
                type: 'radar',
                data: {
                    labels: ['Yr 1', 'Yr 2', 'Yr 3', 'Yr 4'],
                    datasets: [{
                        label: 'Attendance Health',
                        data: yearValues,
                        borderColor: '#34d399',
                        backgroundColor: 'rgba(52, 211, 153, 0.16)',
                        borderWidth: 3,
                        pointBackgroundColor: yearValues.map(v => v >= 80 ? '#34d399' : (v >= 60 ? '#f0a732' : '#f25757')),
                        pointBorderColor: '#0f1117',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f1117',
                            titleColor: '#e8eaf2',
                            bodyColor: '#b0b8cc',
                            borderColor: '#242c3d',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return `Attendance health: ${context.parsed.r}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                display: false,
                                stepSize: 20
                            },
                            grid: { color: 'rgba(255,255,255,0.08)' },
                            angleLines: { color: 'rgba(255,255,255,0.08)' },
                            pointLabels: {
                                color: '#b0b8cc',
                                font: { family: "'IBM Plex Mono', monospace", size: 10, weight: '700' }
                            }
                        }
                    }
                }
            });
        }
    </script>

@endpush

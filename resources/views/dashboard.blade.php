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
                    <span>SESSION</span>
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
                    $monitorClasses = collect($classes)->take(7);
                    $monitorAvg = $monitorClasses->count() ? round($monitorClasses->avg('progress')) : 0;
                    $monitorStrongest = $monitorClasses->sortByDesc('progress')->first();
                    $monitorNeedsAttention = $monitorClasses->filter(fn($class) => $class['progress'] < 60)->count();
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

                <div class="monitor-insights">
                    <div class="monitor-insight">
                        <span>Classes tracked</span>
                        <strong>{{ $monitorClasses->count() }}</strong>
                    </div>
                    <div class="monitor-insight">
                        <span>Strongest class</span>
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

    <section class="dashboard-bottom-grid">
        <div class="panel">
            <div class="panel-head">
                <span class="panel-title">CURRENT CLASSES</span>
                <span style="font-family:var(--font-mono); font-size:9px; color:var(--muted2);">SESSION DISTRO</span>
            </div>

            <div id="class-list">
                @foreach($classes as $class)
                    <a href="?session_id={{ $class['id'] }}"
                        class="class-row {{ ($activeSession && $activeSession->id == $class['id']) ? 'active' : '' }}">
                        <div
                            class="row-icon {{ $class['is_live'] ? 'icon-violet' : ($class['is_done'] ? 'icon-green' : 'icon-amber') }}">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 3h10v10H3V3Z" stroke="currentColor" stroke-width="1.3"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div class="row-info">
                            <div class="row-name">{{ $class['name'] }}</div>
                            <div class="row-meta">{{ $class['room'] }} · {{ $class['time'] }}</div>
                        </div>
                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                            @if($class['is_live'])
                                <div class="live-badge">
                                    <div class="live-dot"></div>LIVE
                                </div>
                            @elseif($class['is_done'])
                                <div class="sched-badge">COMPLETED</div>
                            @else
                                <div class="sched-badge">SCHEDULED</div>
                            @endif
                            <div class="prog-row">
                                <div class="prog-track">
                                    <div class="prog-fill"
                                        style="width:{{ $class['progress'] }}%; background:{{ $class['is_live'] ? 'var(--accent)' : ($class['is_done'] ? 'var(--green)' : 'var(--border2)') }}">
                                    </div>
                                </div>
                                <span class="prog-pct">{{ $class['progress'] }}%</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="qr-panel">
            <div class="qr-head">
                <span class="qr-title">QR AUTHENTICATION</span>
                <span class="qr-class-name">{{ $activeSession?->classRoom?->subject?->name ?? 'None' }}</span>
            </div>
            <div class="qr-body">
                @if($activeSession)
                    <div class="qr-frame">
                        <div class="qr-scan-line"></div>
                        <img id="qr-img"
                            src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(url('/scan/' . $activeSession->id)) }}"
                            alt="QR">
                    </div>
                    <div style="width:100%">
                        <div class="qr-timer-row">
                            <span class="timer-label">EXPIRES IN</span>
                            <span class="timer-count" id="qr-timer">00:30</span>
                        </div>
                        <div class="timer-bar" style="margin-top:6px">
                            <div class="timer-fill" id="timer-fill" style="width:100%"></div>
                        </div>
                        <div class="code-box">
                            <span class="code-val" id="qr-code-val">{{ $activeSession->qr_token }}</span>
                            <div style="color:var(--muted2); cursor:pointer;"><svg width="14" height="14" viewBox="0 0 14 14"
                                    fill="none">
                                    <rect x="4" y="4" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.2">
                                    </rect>
                                    <rect x="2" y="2" width="8" height="8" rx="1.5" stroke="currentColor" stroke-width="1.2">
                                    </rect>
                                </svg></div>
                        </div>

                        {{-- Permission Notification --}}
                        @if(isset($activePermissions) && $activePermissions->count() > 0)
                            <div style="margin-top:20px; background:rgba(167, 139, 250, 0.08); border:1px solid rgba(167, 139, 250, 0.2); border-radius:12px; padding:15px; animation: slideInRight 0.4s ease-out;">
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="width:6px; height:6px; border-radius:50%; background:#a78bfa; box-shadow:0 0 8px #a78bfa; animation: blink 2s infinite;"></div>
                                        <span style="font-family:var(--font-mono); font-size:10px; font-weight:800; color:#a78bfa; letter-spacing:0.05em;">ACTIVE PERMISSIONS ({{ $activePermissions->count() }})</span>
                                    </div>
                                    <span style="font-size:8px; color:var(--muted); font-family:var(--font-mono);">EXCUSED TODAY</span>
                                </div>
                                <div style="display:flex; flex-direction:column; gap:10px;">
                                    @foreach($activePermissions as $perm)
                                        <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.1); padding:8px 10px; border-radius:8px; border:1px solid rgba(255,255,255,0.03);">
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div style="width:24px; height:24px; border-radius:50%; background:#a78bfa18; color:#a78bfa; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:9px; border:1px solid rgba(167, 139, 250, 0.2)">{{ strtoupper(substr($perm->student->user->name, 0, 1)) }}</div>
                                                <div style="display:flex; flex-direction:column;">
                                                    <span style="color:var(--text2); font-weight:700; font-size:11px;">{{ $perm->student->user->name }}</span>
                                                    <span style="font-size:9px; color:var(--muted2); font-family:var(--font-mono);">{{ $perm->student->student_code }}</span>
                                                </div>
                                            </div>
                                            <div style="text-align:right;">
                                                <span style="font-family:var(--font-mono); font-size:8px; color:#a78bfa; background:rgba(167, 139, 250, 0.12); padding:3px 8px; border-radius:20px; font-weight:800; border:1px solid rgba(167, 139, 250, 0.2)">{{ strtoupper($perm->type) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="scan-count">QR SCANS THIS SESSION: <span
                            style="color:var(--green)">{{ $sessionScanCount }}</span></div>
                    <button class="btn-primary" id="regen-btn">REGENERATE QR</button>
                @else
                    <div style="padding:40px; text-align:center; color:var(--muted)">No active session.</div>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const sessionId = @json($activeSession?->id);

        let qrExpirySecs = 30;
        setInterval(() => {
            const timer = document.getElementById('qr-timer');
            const fill = document.getElementById('timer-fill');
            if (!timer) return;

            qrExpirySecs = qrExpirySecs <= 0 ? 30 : qrExpirySecs - 1;
            timer.textContent = '00:' + String(qrExpirySecs).padStart(2, '0');
            timer.className = 'timer-count' + (qrExpirySecs <= 8 ? ' urgent' : '');

            if (fill) {
                const pct = (qrExpirySecs / 30) * 100;
                fill.style.width = pct + '%';
                fill.style.background = qrExpirySecs <= 8 ? 'var(--red)' : (qrExpirySecs <= 15 ? 'var(--amber)' : 'var(--accent)');
            }
        }, 1000);

        async function regenerateQr(auto = false) {
            if (!sessionId) return;
            const btn = document.getElementById('regen-btn');
            if (!auto) btn.textContent = 'GENERATING...';

            try {
                const resp = await fetch(`/api/teacher/session/${sessionId}/regenerate-qr`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                });
                const data = await resp.json();
                if (data.success) {
                    document.getElementById('qr-img').src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(data.scan_url)}`;
                    document.getElementById('qr-code-val').textContent = data.qr_token;
                    qrExpirySecs = 30;
                    if (!auto) showToast('QR Token Regenerated');
                }
            } catch (e) { console.error(e); }
            finally { if (!auto) btn.textContent = 'REGENERATE QR'; }
        }

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

        document.getElementById('regen-btn')?.addEventListener('click', () => regenerateQr());

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

        // MONITOR CHART LOGIC (Replaced Chat)
        const monitorCtx = document.getElementById('monitor-chart');
        if (monitorCtx) {
            const fullLabels = {!! json_encode(collect($classes)->take(7)->pluck('name')) !!};
            const classLabels = fullLabels.map(l => l.length > 20 ? l.substring(0, 20) + '...' : l);
            const attendData = {!! json_encode(collect($classes)->take(7)->pluck('progress')) !!};
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
            if (rawTimes.length === 0) {
                const dummyTime = window.getServerTime ? window.getServerTime() : new Date();
                scanFreq[dummyTime.getHours().toString().padStart(2, '0') + ':' + dummyTime.getMinutes().toString().padStart(2, '0')] = 0;
            } else {
                rawTimes.forEach(t => { scanFreq[t] = (scanFreq[t] || 0) + 1; });
            }

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
                            suggestedMax: Math.max(...Object.values(scanFreq)) + 2
                        }
                    }
                }
            });

            // Ensure refreshMonitor exists for manual buttons
            window.refreshMonitor = function () { window.location.reload(); };
        }

        // CLASS DISTRO CHART
        const distroCtx = document.getElementById('class-distro-chart');
        if (distroCtx) {
            new Chart(distroCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Live', 'Scheduled', 'Completed'],
                    datasets: [{
                        data: [
                                {{ collect($classes)->where('is_live', true)->count() }},
                                {{ collect($classes)->where('is_live', false)->where('is_done', false)->count() }},
                            {{ collect($classes)->where('is_done', true)->count() }}
                        ],
                        backgroundColor: ['#a78bfa', '#fbbf24', '#34d399'],
                        borderWidth: 0,
                        hoverOffset: 4,
                        cutout: '80%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
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

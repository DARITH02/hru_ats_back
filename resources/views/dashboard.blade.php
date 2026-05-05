@extends('layouts.app')

@section('content')
    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-glow"></div>
            <div class="stat-label">ACTIVE STUDENTS</div>
            <div class="stat-value" id="stat-students">{{ $stats['students'] }}</div>
            <div class="stat-pill pill-up">↑ +12%</div>
        </div>
        <div class="stat-card green">
            <div class="stat-glow"></div>
            <div class="stat-label">TOTAL ATTENDANCE</div>
            <div class="stat-value" id="stat-attendance">{{ $stats['attendance_rate'] }}</div>
            <div class="stat-pill pill-up">↑ +2.1%</div>
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
            <div class="stat-pill pill-down">↓ -0.4%</div>
        </div>
    </div>

    <div class="main-grid">
        <!-- LEFT: CLASSES & TABLE -->
        <div class="left-col">
            <div class="panel">
                <div class="panel-head">
                    <span class="panel-title">CURRENT CLASSES</span>
                    <span style="font-family:var(--font-mono); font-size:9px; color:var(--muted2);">SESSION DISTRO</span>
                </div>

                <!-- Doughnut Chart Area -->
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

            <div class="panel" style="margin-top: 20px;">
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

            <div class="panel" style="margin-top: 20px;">
                <div class="panel-head">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div
                            style="width:8px; height:8px; border-radius:50%; background:var(--accent); box-shadow:0 0 8px var(--accent);">
                        </div>
                        <span class="panel-title">MONITOR DATA</span>
                    </div>
                    <span style="font-family:var(--font-mono); font-size:9px; color:var(--muted2);">OVERALL CLASS
                        PROGRESS</span>
                </div>
                <!-- Monitor Data Chart Area -->
                <div style="padding: 20px; position:relative; min-height:280px; width:100%;">
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

            <div class="panel" style="margin-bottom: 20px;">
                <div class="panel-head">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div class="db-dot" style="background:var(--green); box-shadow:0 0 8px var(--green);"></div>
                        <span class="panel-title">YEAR LEVEL PERFORMANCE</span>
                    </div>
                </div>
                <div style="padding:15px; min-height:180px;">
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
                            backgroundColor: '#10b981', // Solid vibrant emerald
                            borderColor: '#10b981',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.5
                        },
                        {
                            label: 'Missing/Absent %',
                            data: absentData,
                            backgroundColor: 'rgba(255, 255, 255, 0.15)', // More visible gray
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: { color: '#8892a4', usePointStyle: true, boxWidth: 8, font: { family: "'IBM Plex Mono', monospace", size: 10 } }
                        },
                        tooltip: {
                            backgroundColor: '#111318', titleColor: '#fff', bodyColor: '#8892a4',
                            padding: 12, borderColor: '#1e2330', borderWidth: 1
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: { color: '#8892a4', font: { family: "'IBM Plex Mono', monospace", size: 9 }, maxRotation: 25, minRotation: 0 }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            max: 100,
                            grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                            ticks: { stepSize: 25, color: '#8892a4', callback: function (value) { return value + '%' } }
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
            new Chart(yearCtx, {
                type: 'radar',
                data: {
                    labels: ['Yr 1', 'Yr 2', 'Yr 3', 'Yr 4'],
                    datasets: [{
                        label: 'Attendance %',
                        data: Object.values(@json($yearStats)),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                        borderWidth: 3,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { display: false, stepSize: 25 },
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            angleLines: { color: 'rgba(255,255,255,0.05)' },
                            pointLabels: { color: '#8892a4', font: { family: "'IBM Plex Mono', monospace", size: 9 } }
                        }
                    }
                }
            });
        }
    </script>

@endpush
@extends('layouts.app')

@section('content')

{{-- ════════════════════════════════════════════
     TOAST
════════════════════════════════════════════ --}}
<div id="toast" class="toast">
    <div id="toastIcon" class="toast-icon">✓</div>
    <span id="toastMsg">Message</span>
</div>

{{-- ════════════════════════════════════════════
     DELETE MODAL
════════════════════════════════════════════ --}}
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box" style="max-width:400px">
        <div class="modal-body" style="text-align:center;padding:32px 24px 20px">
            <div class="delete-modal-icon">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div style="font-family:var(--font-display);font-size:16px;font-weight:700;color:var(--text);margin-bottom:8px">Remove Instructor?</div>
            <div id="deleteSubtitle" style="font-family:var(--font-mono);font-size:10px;color:var(--muted);letter-spacing:.06em;line-height:1.7">
                This instructor will be permanently removed.<br>All associated class assignments may be affected.
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal('deleteModal')" class="btn-secondary">CANCEL</button>
            <button id="confirmDeleteBtn"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--radius-md);border:none;background:linear-gradient(135deg,var(--red),#f87a7a);color:#fff;font-family:var(--font-mono);font-size:10px;letter-spacing:.1em;font-weight:600;cursor:pointer;transition:all .2s;box-shadow:0 4px 14px rgba(242,87,87,.25)">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                CONFIRM REMOVE
            </button>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════
     CREATE / EDIT MODAL
════════════════════════════════════════════ --}}
<div id="instructorModal" class="modal-overlay">
    <div class="modal-box" style="max-width:520px">
        <div class="modal-head">
            <div style="display:flex;align-items:center;gap:10px">
                <div id="modalAvatarPreview"
                    style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--violet));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;letter-spacing:.04em">
                    ?
                </div>
                <span id="instructorModalTitle" class="modal-title">Add Instructor</span>
            </div>
            <button onclick="closeModal('instructorModal')" class="modal-close">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="instructorForm">
            @csrf
            <input type="hidden" id="modalInstructorId">
            <input type="hidden" id="modalMode" value="create">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:0">

                {{-- Name --}}
                <div class="form-group">
                    <label class="form-label">Full Name <span class="req">*</span></label>
                    <input id="modalName" class="form-input" type="text" required
                        placeholder="e.g. Dr. Maria Santos"
                        oninput="updateAvatarPreview(this.value)">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Department <span class="req">*</span></label>
                        <select id="modalDept" name="department_id" class="form-input">
                            <option value="">Select dept.</option>
                            @foreach($depts as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Status --}}
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="modalStatus" class="form-input">
                            <option value="active">Active</option>
                            <option value="on_leave">On Leave</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    {{-- Email --}}
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input id="modalEmail" class="form-input" type="email" placeholder="instructor@school.edu">
                    </div>
                    {{-- Phone --}}
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input id="modalPhone" class="form-input" type="text" placeholder="+63 9XX XXX XXXX">
                    </div>
                </div>

                {{-- Specialization --}}
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Specialization</label>
                    <input id="modalSpec" class="form-input" type="text"
                        placeholder="e.g. Machine Learning, Structural Engineering">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal('instructorModal')" class="btn-secondary">CANCEL</button>
                <button type="submit" id="modalSubmitBtn" class="btn-primary">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span id="modalSubmitLabel">ADD INSTRUCTOR</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════
     VIEW PROFILE MODAL
════════════════════════════════════════════ --}}
<div id="profileModal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-head">
            <span class="modal-title">Instructor Profile</span>
            <button onclick="closeModal('profileModal')" class="modal-close">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body" style="padding:0">
            {{-- Profile hero --}}
            <div style="padding:28px 24px;background:var(--surface2);border-bottom:1px solid var(--border);display:flex;align-items:center;gap:18px">
                <div id="profileAvatar"
                    style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--violet));display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff;box-shadow:0 0 20px rgba(79,142,247,.3);flex-shrink:0">
                    A
                </div>
                <div>
                    <div id="profileName" style="font-family:var(--font-display);font-size:17px;font-weight:700;color:var(--text)">—</div>
                    <div id="profileDept" style="font-family:var(--font-mono);font-size:10px;color:var(--muted);letter-spacing:.1em;margin-top:3px">—</div>
                    <div style="margin-top:8px" id="profileStatusWrap">
                        <span id="profileStatus" class="status-tag tag-active">ACTIVE</span>
                    </div>
                </div>
            </div>
            {{-- Details grid --}}
            <div style="padding:20px 24px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:.12em;color:var(--muted);margin-bottom:5px">EMAIL</div>
                    <div id="profileEmail" style="font-size:12px;color:var(--text2)">—</div>
                </div>
                <div>
                    <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:.12em;color:var(--muted);margin-bottom:5px">PHONE</div>
                    <div id="profilePhone" style="font-size:12px;color:var(--text2)">—</div>
                </div>
                <div>
                    <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:.12em;color:var(--muted);margin-bottom:5px">CLASSES ASSIGNED</div>
                    <div id="profileClasses" style="font-family:var(--font-display);font-size:20px;font-weight:700;color:var(--accent)">—</div>
                </div>
                <div>
                    <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:.12em;color:var(--muted);margin-bottom:5px">SPECIALIZATION</div>
                    <div id="profileSpec" style="font-size:12px;color:var(--text2)">—</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal('profileModal')" class="btn-secondary">CLOSE</button>
            <button id="profileEditBtn" class="btn-primary">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                EDIT PROFILE
            </button>
        </div>
    </div>
</div>

    </div>
</div>

{{-- ════════════════════════════════════════════
     PAGE CONTENT
════════════════════════════════════════════ --}}

    {{-- Page Header --}}
    <div class="page-header " style="padding: 0 24px;">
        <div>
            <div class="breadcrumb">
                <span>MANAGEMENT</span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current">INSTRUCTORS</span>
            </div>
            <h1 class="page-title">Instructor Registry</h1>
            <p class="page-subtitle">FACULTY DIRECTORY & CLASS ASSIGNMENTS</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <button onclick="toggleView('table')" id="viewTableBtn"
                class="btn-secondary" style="gap:6px;padding:8px 14px">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M3 6h18M3 14h18M3 18h18"/>
                </svg>
                LIST
            </button>
            <button onclick="toggleView('grid')" id="viewGridBtn"
                class="btn-secondary" style="gap:6px;padding:8px 14px">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                GRID
            </button>
            <button onclick="window.open('{{ route('admin.export.instructors') }}', '_blank')" class="btn-secondary" style="gap:7px; background:var(--surface3); border:1px solid var(--border)">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                EXPORT ALL
            </button>
            <button onclick="openCreateModal()" class="btn-primary" style="gap:7px">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                ADD INSTRUCTOR
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid " style="padding: 24px;">
        <div class="stat-card blue">
            <div class="stat-glow"></div>
            <div class="stat-icon-wrap">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="stat-label">TOTAL INSTRUCTORS</div>
            <div class="stat-value">{{ $instructors->count() }}</div>
            <span class="stat-pill pill-up">↑ Faculty</span>
        </div>
        <div class="stat-card green">
            <div class="stat-glow"></div>
            <div class="stat-icon-wrap">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-label">ACTIVE</div>
            <div class="stat-value">{{ $instructors->count() }}</div>
            <span class="stat-pill pill-up">↑ Verified</span>
        </div>
        <div class="stat-card amber">
            <div class="stat-glow"></div>
            <div class="stat-icon-wrap">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div class="stat-label">CLASSES COVERED</div>
            <div class="stat-value">{{ $instructors->sum(fn($i) => $i->classes_count ?? rand(1,4)) }}</div>
            <span class="stat-pill pill-amber">Assigned</span>
        </div>
        <div class="stat-card red">
            <div class="stat-glow"></div>
            <div class="stat-icon-wrap">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-label">ON LEAVE</div>
            <div class="stat-value">0</div>
            <span class="stat-pill pill-down">None</span>
        </div>
    </div>

    {{-- Toolbar + Table/Grid --}}
    <div style="width: 100%;padding: 0 24px;">
        <div class="panel">

        {{-- Toolbar --}}
        <div class="catalog-toolbar" style="padding: 16px 20px; gap: 15px;">
            <div style="display:flex;align-items:center;gap:10px; flex: 1;">
                <div style="width:8px;height:8px;border-radius:50%;background:var(--green);box-shadow:0 0 10px var(--green)44"></div>
                <span style="font-family:var(--font-mono);font-size:10px;font-weight:700;letter-spacing:.12em;color:var(--text2)">FACULTY REGISTRY</span>
            </div>
            
            <div style="display:flex; align-items:center; gap:10px;">
                <div class="search-wrap" style="width: 240px; height: 36px; background: var(--surface3); border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; padding: 0 12px;">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="var(--muted2)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                    <input id="searchInput" name="search" value="{{ request('search') }}" class="search-input" type="text" placeholder="Search faculty..." onkeyup="filterInstructors(event)" style="border: none; background: transparent; color: var(--text); font-size: 11px; padding-left: 10px; width: 100%; outline: none;">
                </div>

                <select class="filter-select" id="deptFilter" onchange="filterInstructors()" style="height: 36px; background: var(--surface3); border: 1px solid var(--border); border-radius: 10px; color: var(--text2); font-family: var(--font-mono); font-size: 9px; padding: 0 35px 0 15px; cursor: pointer;">
                    <option value="">ALL DEPTS</option>
                    @foreach($depts as $dept)
                        <option value="{{ $dept->id }}" {{ request('dept') == $dept->id ? 'selected' : '' }}>{{ strtoupper($dept->name) }}</option>
                    @endforeach
                </select>

                <select class="filter-select" id="statusFilter" onchange="filterInstructors()" style="height: 36px; background: var(--surface3); border: 1px solid var(--border); border-radius: 10px; color: var(--text2); font-family: var(--font-mono); font-size: 9px; padding: 0 35px 0 15px; cursor: pointer;">
                    <option value="">ALL STATUS</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>ACTIVE</option>
                    <option value="on_leave" {{ request('status') == 'on_leave' ? 'selected' : '' }}>ON LEAVE</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>INACTIVE</option>
                </select>

                <div style="height: 36px; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; padding: 0 15px;">
                    <span style="font-family: var(--font-mono); font-size: 9px; color: var(--muted2); letter-spacing: .05em;">
                        <span id="rowCount" style="color: var(--green); font-weight: 700;">{{ $instructors->count() }}</span> FACULTY
                    </span>
                </div>
            </div>
        </div>

        {{-- ── TABLE VIEW ── --}}
        <div id="tableView">
            <div class="table-responsive">
                <table class="att-table" id="instructorTable">
                <thead>
                    <tr>
                        <th>INSTRUCTOR</th>
                        <th>DEPARTMENT</th>
                        <th>SPECIALIZATION</th>
                        <th>CLASSES</th>
                        <th>STATUS</th>
                        <th style="text-align:right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                @forelse($instructors as $instructor)
                    @php
                        $avatarColors = [['#4f8ef7','#6aaeff'],['#34d399','#6ef0c8'],['#a78bfa','#c4b0ff'],['#f0a732','#f5c567'],['#f25757','#f87a7a'],['#38d9a9','#5ff0c8']];
                        $col   = $avatarColors[$instructor->id % count($avatarColors)];
                        $name = $instructor->user->name ?? 'N/A';
                        $init  = strtoupper(substr($name, 0, 2));
                        $dept  = $instructor->department->name ?? 'Unassigned';
                        $spec  = $instructor->specialization ?? 'Generalist';
                        $classes = $instructor->classes_count ?? 0;
                        $status = $instructor->status ?? 'active';
                        $email = $instructor->user->email ?? 'N/A';
                    @endphp
                    <tr data-id="{{ $instructor->id }}"
                        data-name="{{ strtolower($name) }}"
                        data-dept="{{ $dept }}"
                        data-dept-id="{{ $instructor->department_id }}"
                        data-status="{{ $status }}"
                        data-spec="{{ strtolower($spec) }}"
                        data-email="{{ strtolower($email) }}"
                        data-phone="{{ $instructor->user->phone ?? '—' }}"
                        data-classes="{{ $classes }}"
                        class="fade-up">

                        {{-- Instructor --}}
                        <td>
                            <div class="subject-cell">
                                <div class="subject-avatar"
                                    style="background:{{ $col[0] }}22;color:{{ $col[0] }};border:1px solid {{ $col[0] }}33;font-size:10px;width:36px;height:36px;border-radius:50%">
                                    {{ $init }}
                                </div>
                                <div>
                                    <div class="subject-name">{{ $name }}</div>
                                    <div class="subject-id" style="color:var(--muted)">
                                        #{{ str_pad($instructor->id, 4, '0', STR_PAD_LEFT) }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Department --}}
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:6px;font-family:var(--font-mono);font-size:10px;color:var(--text2);background:var(--surface3);border:1px solid var(--border2);padding:3px 10px;border-radius:var(--radius-sm)">
                                {{ strtoupper($dept) }}
                            </span>
                        </td>

                        {{-- Specialization --}}
                        <td>
                            <span style="font-size:12px;color:var(--muted2)">{{ $spec }}</span>
                        </td>

                        {{-- Classes --}}
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--accent)">{{ $classes }}</span>
                                <div>
                                    <div style="width:48px;height:3px;background:var(--border2);border-radius:99px;overflow:hidden">
                                        <div style="width:{{ min(100, $classes * 25) }}%;height:100%;background:var(--accent);border-radius:99px"></div>
                                    </div>
                                    <div style="font-family:var(--font-mono);font-size:8px;color:var(--muted);margin-top:2px">ASSIGNED</div>
                                </div>
                            </div>
                        </td>

                        {{-- Status --}}
                        <td>
                            @if($status === 'active')
                                <span class="status-tag tag-active">ACTIVE</span>
                            @elseif($status === 'on_leave')
                                <span class="status-tag tag-waiting">ON LEAVE</span>
                            @else
                                <span class="status-tag" style="background:var(--surface3);color:var(--muted2);border:1px solid var(--border2)">INACTIVE</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td style="text-align:right">
                            <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                                <button class="action-btn btn-view" title="View profile"
                                    onclick="openProfile(this.closest('tr'))">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button class="action-btn btn-edit" title="Edit instructor"
                                    onclick="openEditModal(this.closest('tr'))">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button class="action-btn btn-enroll" title="View assigned classes"
                                    onclick="showToast('Class assignment view coming soon','info')">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                </button>
                                <button class="action-btn" title="Semester Assignment"
                                    style="background:var(--violet)18;border-color:var(--violet)44;color:var(--violet)">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>
                                @if(Auth::user()->isSuperAdmin())
                                <button class="action-btn btn-del" title="Remove instructor"
                                    onclick="openDeleteModal({{ $instructor->id }}, '{{ addslashes($name) }}')">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="empty-title">No instructors found</div>
                                <div class="empty-desc">Add the first instructor to get started</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{-- ── GRID VIEW ── --}}
        <div id="gridView" style="display:none;padding:18px">
            <div id="gridBody"
                style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px">
                @foreach($instructors as $instructor)
                    @php
                        $avatarColors = [['#4f8ef7','#6aaeff'],['#34d399','#6ef0c8'],['#a78bfa','#c4b0ff'],['#f0a732','#f5c567'],['#f25757','#f87a7a'],['#38d9a9','#5ff0c8']];
                        $col2   = $avatarColors[$instructor->id % count($avatarColors)];
                        $name2 = $instructor->user->name ?? 'N/A';
                        $init2  = strtoupper(substr($name2, 0, 2));
                        $dept2  = $instructor->department->name ?? 'Unassigned';
                        $cls2   = $instructor->classes_count ?? 0;
                    @endphp
                    <div class="instructor-card fade-up"
                        data-id="{{ $instructor->id }}"
                        data-name="{{ strtolower($name2) }}"
                        data-dept="{{ $dept2 }}"
                        data-status="{{ $instructor->status ?? 'active' }}"
                        data-spec="{{ strtolower($instructor->specialization ?? '') }}"
                        data-email="{{ strtolower($instructor->user->email ?? '') }}"
                        data-phone="{{ $instructor->user->phone ?? '—' }}"
                        data-classes="{{ $cls2 }}"
                        style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px 18px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center;transition:all .2s;cursor:pointer;position:relative;overflow:hidden"
                        onmouseenter="this.style.borderColor='var(--border2)';this.style.transform='translateY(-3px)';this.style.boxShadow='var(--shadow-md)'"
                        onmouseleave="this.style.borderColor='var(--border)';this.style.transform='';this.style.boxShadow=''">

                        {{-- Top accent bar --}}
                        <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,{{ $col2[0] }},{{ $col2[1] }})"></div>

                        {{-- Avatar --}}
                        <div style="width:52px;height:52px;border-radius:50%;background:{{ $col2[0] }}22;border:2px solid {{ $col2[0] }}44;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:{{ $col2[0] }};letter-spacing:.04em">
                            {{ $init2 }}
                        </div>

                        {{-- Name --}}
                        <div style="font-family:var(--font-display);font-size:13px;font-weight:700;color:var(--text);line-height:1.3">
                            {{ $name2 }}
                        </div>

                        {{-- Dept badge --}}
                        <span style="font-family:var(--font-mono);font-size:9px;letter-spacing:.1em;color:{{ $col2[0] }};background:{{ $col2[0] }}15;border:1px solid {{ $col2[0] }}30;padding:3px 10px;border-radius:99px">
                            {{ strtoupper($dept2) }}
                        </span>

                        {{-- Classes count --}}
                        <div style="display:flex;align-items:center;gap:14px;margin-top:4px">
                            <div>
                                <div style="font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--accent);line-height:1">{{ $cls2 }}</div>
                                <div style="font-family:var(--font-mono);font-size:8px;color:var(--muted);letter-spacing:.1em">CLASSES</div>
                            </div>
                            <div style="width:1px;height:30px;background:var(--border2)"></div>
                            <div>
                                <div style="font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--green);line-height:1">{{ rand(85,99) }}</div>
                                <div style="font-family:var(--font-mono);font-size:8px;color:var(--muted);letter-spacing:.1em">RATE%</div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div style="display:flex;gap:6px;margin-top:4px">
                            <button class="action-btn btn-view" style="width:28px;height:28px" title="View"
                                onclick="openProfile(this.closest('.instructor-card'))">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <button class="action-btn btn-edit" style="width:28px;height:28px" title="Edit"
                                onclick="openEditModal(this.closest('.instructor-card'))">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button class="action-btn btn-enroll" style="width:28px;height:28px" title="Classes"
                                onclick="showToast('Class assignment view coming soon','info')">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </button>
                            @if(Auth::user()->isSuperAdmin())
                            <button class="action-btn btn-del" style="width:28px;height:28px" title="Remove"
                                onclick="openDeleteModal({{ $instructor->id }}, '{{ addslashes($name2) }}')">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        @if($instructors instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div style="padding:12px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <span style="font-family:var(--font-mono);font-size:9px;color:var(--muted);letter-spacing:.08em">
                SHOWING {{ $instructors->firstItem() }}–{{ $instructors->lastItem() }} OF {{ $instructors->total() }}
            </span>
            {{ $instructors->links('vendor.pagination.academy') }}
        </div>
        @endif
    </div>
</div>

<script>
// ── Modal helpers ──────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});

// ── Toast ──────────────────────────────────────
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    const ic = document.getElementById('toastIcon');
    t.className = `toast show toast-${type}`;
    ic.textContent = type === 'success' ? '✓' : type === 'error' ? '✕' : 'i';
    document.getElementById('toastMsg').textContent = msg;
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), 3200);
}

// ── Avatar preview ─────────────────────────────
function updateAvatarPreview(val) {
    const words = val.trim().split(/\s+/);
    const init  = words.length >= 2
        ? (words[0][0] + words[words.length-1][0]).toUpperCase()
        : (val.slice(0,2).toUpperCase() || '?');
    document.getElementById('modalAvatarPreview').textContent = init;
}

// ── View toggle ────────────────────────────────
function toggleView(mode) {
    const isTable = mode === 'table';
    document.getElementById('tableView').style.display = isTable ? '' : 'none';
    document.getElementById('gridView').style.display  = isTable ? 'none' : '';
    document.getElementById('viewTableBtn').style.opacity = isTable ? '1' : '.5';
    document.getElementById('viewGridBtn').style.opacity  = isTable ? '.5' : '1';
}
toggleView('table');

// ── Filter (Server-side) ───────────────────────
let filterTimeout = null;
function filterInstructors(e) {
    if (e && e.type === 'keyup' && e.key !== 'Enter') {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => filterInstructors(), 800);
        return;
    }
    const q = document.getElementById('searchInput').value;
    const dept = document.getElementById('deptFilter').value;
    const status = document.getElementById('statusFilter').value;

    const params = new URLSearchParams(window.location.search);
    if (q) params.set('search', q); else params.delete('search');
    if (dept) params.set('dept', dept); else params.delete('dept');
    if (status) params.set('status', status); else params.delete('status');
    params.set('page', 1);

    window.location.href = `${window.location.pathname}?${params.toString()}`;
}

// ── Delete ─────────────────────────────────────
let pendingDeleteId = null;
function openDeleteModal(id, name) {
    pendingDeleteId = id;
    document.getElementById('deleteSubtitle').innerHTML =
        `<strong style="color:var(--text2)">${name}</strong> will be permanently removed.<br>All class assignments may be affected.`;
    openModal('deleteModal');
}
document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('confirmDeleteBtn');
    const ogHtml = btn.innerHTML;
    btn.innerHTML = 'DELETING...';
    btn.disabled = true;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                   || document.querySelector('input[name="_token"]')?.value || '';
    try {
        const res = await fetch(`/api/admin/instructors/${pendingDeleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        if (!res.ok && res.status !== 200) {
            // Fallback: try web route
            const res2 = await fetch(`/admin/instructors/${pendingDeleteId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json',
                           'Content-Type': 'application/json' },
                body: JSON.stringify({ _method: 'DELETE' })
            });
        }
        const data = await res.json().catch(() => ({ success: res.ok }));
        if (data.success || res.ok) {
            document.querySelectorAll(`[data-id="${pendingDeleteId}"]`).forEach(el => {
                el.style.opacity = '0'; el.style.transition = 'opacity .3s';
                setTimeout(() => el.remove(), 350);
            });
            showToast('Instructor removed from registry.', 'success');
            closeModal('deleteModal');
            const countEl = document.getElementById('rowCount');
            if (countEl) countEl.textContent = Math.max(0, parseInt(countEl.textContent) - 1);
        } else {
            showToast(data.error || 'Failed to delete instructor.', 'error');
        }
    } catch (e) {
        showToast('Network error: ' + e.message, 'error');
    }
    btn.innerHTML = ogHtml;
    btn.disabled = false;
    pendingDeleteId = null;
});

// ── View Profile ───────────────────────────────
function openProfile(row) {
    // Works for both <tr> table rows and .instructor-card grid divs
    const name    = row.dataset.name
                    ? row.dataset.name.replace(/\b\w/g, c => c.toUpperCase())
                    : (row.querySelector('.subject-name')?.textContent.trim() || '—');
    const dept    = row.dataset.dept    || '—';
    const status  = row.dataset.status  || 'active';
    const email   = row.dataset.email   || '—';
    const classes = row.dataset.classes || '0';
    const spec    = row.dataset.spec    || '—';
    const phone   = row.dataset.phone   || '—';
    const init    = name.trim().split(/\s+/).filter(Boolean).map(w=>w[0]).slice(0,2).join('').toUpperCase() || '?';

    document.getElementById('profileAvatar').textContent   = init;
    document.getElementById('profileName').textContent     = name;
    document.getElementById('profileDept').textContent     = dept.toUpperCase ? dept.toUpperCase() : dept;
    document.getElementById('profileEmail').textContent    = email;
    document.getElementById('profilePhone').textContent    = phone;
    document.getElementById('profileClasses').textContent  = classes;
    document.getElementById('profileSpec').textContent     = spec;

    const stEl = document.getElementById('profileStatus');
    stEl.textContent = status === 'active' ? 'ACTIVE' : status === 'on_leave' ? 'ON LEAVE' : 'INACTIVE';
    stEl.className   = 'status-tag ' + (status === 'active' ? 'tag-active' : status === 'on_leave' ? 'tag-waiting' : '');

    document.getElementById('profileEditBtn').onclick = () => {
        closeModal('profileModal');
        openEditModal(row);
    };
    openModal('profileModal');
}

// ── Create ─────────────────────────────────────
function openCreateModal() {
    document.getElementById('instructorModalTitle').textContent = 'Add Instructor';
    document.getElementById('modalSubmitLabel').textContent = 'ADD INSTRUCTOR';
    document.getElementById('modalMode').value = 'create';
    document.getElementById('modalInstructorId').value = '';
    document.getElementById('modalAvatarPreview').textContent = '?';
    document.getElementById('instructorForm').reset();
    openModal('instructorModal');
}

// ── Edit ───────────────────────────────────────
function openEditModal(row) {
    // Works for both <tr> table rows and .instructor-card grid divs
    const name = row.dataset.name
                 ? row.dataset.name.replace(/\b\w/g, c => c.toUpperCase())
                 : (row.querySelector('.subject-name')?.textContent.trim() || '');
    const init = name.trim().split(/\s+/).filter(Boolean).map(w=>w[0]).slice(0,2).join('').toUpperCase() || '?';
    document.getElementById('instructorModalTitle').textContent = 'Edit Instructor';
    document.getElementById('modalSubmitLabel').textContent = 'SAVE CHANGES';
    document.getElementById('modalMode').value = 'edit';
    document.getElementById('modalInstructorId').value = row.dataset.id || '';
    document.getElementById('modalName').value   = name;
    document.getElementById('modalDept').value   = row.dataset.deptId || '';
    document.getElementById('modalStatus').value = row.dataset.status || 'active';
    document.getElementById('modalEmail').value  = row.dataset.email || '';
    document.getElementById('modalPhone').value  = row.dataset.phone || '';
    document.getElementById('modalSpec').value   = row.dataset.spec || '';
    document.getElementById('modalAvatarPreview').textContent = init;
    openModal('instructorModal');
}

document.getElementById('instructorForm').addEventListener('submit', async e => {
    e.preventDefault();
    const mode = document.getElementById('modalMode').value;
    const id   = document.getElementById('modalInstructorId').value;
    const btn  = document.getElementById('modalSubmitBtn');
    const ogHtml = btn.innerHTML;
    btn.innerHTML = '<span class="loading-spinner" style="width:12px;height:12px;border-width:2px;margin-right:8px"></span> SAVING...';
    btn.disabled = true;

    const payload = {
        name: document.getElementById('modalName').value,
        department_id: document.getElementById('modalDept').value,
        specialization: document.getElementById('modalSpec').value,
        status: document.getElementById('modalStatus').value,
        email: document.getElementById('modalEmail').value,
        phone: document.getElementById('modalPhone').value,
    };

    if (mode === 'create') {
        payload.password = 'password123'; // Default password for new instructors
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                   || document.querySelector('input[name="_token"]')?.value || '';
    try {
        const url = mode === 'create' ? '/api/admin/instructors' : `/api/admin/instructors/${id}`;
        const res = await fetch(url, {
            method: mode === 'create' ? 'POST' : 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            showToast(mode === 'create' ? 'Instructor added to registry.' : 'Profile updated successfully.', 'success');
            closeModal('instructorModal');
            setTimeout(() => window.location.reload(), 900);
        } else if (data.errors) {
            // Validation errors from Laravel
            const msgs = Object.values(data.errors).flat().join(' ');
            showToast(msgs, 'error');
        } else {
            showToast(data.error || data.message || 'Operation failed.', 'error');
        }
    } catch (err) {
        showToast('Network error.', 'error');
    }
    btn.innerHTML = ogHtml;
    btn.disabled = false;
});

</script>
@endsection
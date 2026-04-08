@extends('layouts.app')

@section('content')

{{-- ═══ TOAST ═══ --}}
<div id="toast" class="toast">
    <div id="toastIcon" class="toast-icon">✓</div>
    <span id="toastMsg">Message</span>
</div>

{{-- ═══ DELETE MODAL ═══ --}}
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box" style="max-width:400px">
        <div class="modal-body" style="text-align:center;padding:28px 22px 20px;">
            <div class="delete-modal-icon">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <div style="font-family:var(--font-display);font-size:16px;font-weight:700;color:var(--text);margin-bottom:8px">Delete Class?</div>
            <div style="font-family:var(--font-mono);font-size:10px;color:var(--muted);letter-spacing:.06em;line-height:1.6">This action is irreversible. All associated records<br>will be permanently removed from the system.</div>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal('deleteModal')" class="btn-secondary">CANCEL</button>
            <button id="confirmDeleteBtn" style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--radius-md);border:none;background:linear-gradient(135deg,var(--red),#f87a7a);color:#fff;font-family:var(--font-mono);font-size:10px;letter-spacing:.1em;font-weight:600;cursor:pointer;transition:all .2s;">
                DELETE ENTRY
            </button>
        </div>
    </div>
</div>

{{-- ═══ VIEW / EDIT MODAL ═══ --}}
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <span id="editModalTitle" class="modal-title">Edit Class</span>
            <button onclick="closeModal('editModal')" class="modal-close">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editForm">
            @csrf
            <input type="hidden" id="editClassId">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <select id="editSubjectName" name="subject_id" class="form-input">
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Class Group</label>
                        <select id="editClassGroup" name="group_id" class="form-input">
                            <option value="">No Group Assigned</option>
                            @foreach($classGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Instructor</label>
                        <select id="editInstructor" name="teacher_id" class="form-input">
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->user->name ?? 'Unknown' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location (Room)</label>
                        <input id="editRoom" name="room_number" class="form-input" type="text" placeholder="e.g. 101">
                    </div>
                </div>

                <div class="form-group" style="padding:15px; background:var(--surface3); border:1px solid var(--border); border-radius:12px; margin: 5px 0 15px;">
                    <label class="form-label" style="margin-top:0">Schedule & Timing</label>
                    <div class="form-grid-2">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:9px">Preferred Days</label>
                            <div class="day-selector-grid" id="editDaySelector">
                                <label class="day-chip"><input type="checkbox" value="Mon"><span>M</span></label>
                                <label class="day-chip"><input type="checkbox" value="Tue"><span>T</span></label>
                                <label class="day-chip"><input type="checkbox" value="Wed"><span>W</span></label>
                                <label class="day-chip"><input type="checkbox" value="Thu"><span>T</span></label>
                                <label class="day-chip"><input type="checkbox" value="Fri"><span>F</span></label>
                                <label class="day-chip weekend"><input type="checkbox" value="Sat"><span>S</span></label>
                                <label class="day-chip weekend"><input type="checkbox" value="Sun"><span>S</span></label>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                            <div class="form-group" style="margin-bottom:0">
                                <label class="form-label" style="font-size:9px">Start</label>
                                <input id="editTimeStart" name="time_start" class="form-input" type="time">
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label class="form-label" style="font-size:9px">End</label>
                                <input id="editTimeEnd" name="time_end" class="form-input" type="time">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Operational Status</label>
                    <select id="editStatus" name="status" class="form-input">
                        <option value="active">Active</option>
                        <option value="waiting">Waiting</option>
                        <option value="ready">Ready</option>
                    </select>
                </div>
            </div>
            <div id="editModalFooter" class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn-secondary">CANCEL</button>
                <button type="submit" class="btn-primary">SAVE CHANGES</button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ CREATE MODAL ═══ --}}
<div id="createModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <span class="modal-title">New Catalog Entry</span>
            <button onclick="closeModal('createModal')" class="modal-close">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="createForm">
            @csrf
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Subject <span class="req">*</span></label>
                        <select name="subject_id" class="form-input" required>
                            <option value="" disabled selected>Select a Subject...</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Class Group <span class="req">*</span></label>
                        <select name="group_id" class="form-input" required>
                            <option value="" disabled selected>Target Student Cohort...</option>
                            @foreach($classGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Lead Instructor <span class="req">*</span></label>
                        <select name="teacher_id" class="form-input" required>
                            <option value="" disabled selected>Select Instructor...</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->user->name ?? 'Unknown Instructor' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location (Room) <span class="req">*</span></label>
                        <input name="room_number" class="form-input" type="text" required placeholder="e.g. 101">
                    </div>
                </div>

                <div class="form-group" style="padding:15px; background:var(--surface3); border:1px solid var(--border); border-radius:12px; margin: 10px 0 15px;">
                    <label class="form-label" style="margin-top:0">Weekly Schedule</label>
                    <div class="form-grid-2">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" style="font-size:9px">Preferred Days</label>
                            <div class="day-selector-grid" id="createDaySelector">
                                <label class="day-chip"><input type="checkbox" value="Mon"><span>M</span></label>
                                <label class="day-chip"><input type="checkbox" value="Tue"><span>T</span></label>
                                <label class="day-chip"><input type="checkbox" value="Wed"><span>W</span></label>
                                <label class="day-chip"><input type="checkbox" value="Thu"><span>T</span></label>
                                <label class="day-chip"><input type="checkbox" value="Fri"><span>F</span></label>
                                <label class="day-chip weekend"><input type="checkbox" value="Sat"><span>S</span></label>
                                <label class="day-chip weekend"><input type="checkbox" value="Sun"><span>S</span></label>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                            <div class="form-group" style="margin-bottom:0">
                                <label class="form-label" style="font-size:9px">Start Time</label>
                                <input name="time_start" class="form-input" type="time" value="08:00">
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label class="form-label" style="font-size:9px">End Time</label>
                                <input name="time_end" class="form-input" type="time" value="09:30">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Initial Operational Status</label>
                    <select name="status" class="form-input">
                        <option value="active">Active</option>
                        <option value="waiting">Waiting</option>
                        <option value="ready">Ready</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('createModal')" class="btn-secondary">CANCEL</button>
                <button type="submit" class="btn-primary">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    CREATE ENTRY
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ GENERATE CALENDAR MODAL ═══ --}}
<div id="calendarModal" class="modal-overlay">
    <div class="modal-box" style="max-width:440px; border-radius:24px; padding:0; overflow:hidden;">
        <div class="modal-head" style="padding: 24px 28px; background: var(--surface2); border-bottom: 1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:15px">
                <div style="width:42px;height:42px;border-radius:14px;background:var(--accent)22;color:var(--accent);display:flex;align-items:center;justify-content:center;box-shadow: 0 4px 15px var(--accent)22">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div>
                    <div class="modal-title" style="font-weight: 800; font-size: 17px; letter-spacing: -0.02em;">Academic Period Setup</div>
                    <div style="font-family:var(--font-mono);font-size:10px;color:var(--muted);letter-spacing:0.02em;text-transform:uppercase">BATCH SESSION GENERATION</div>
                </div>
            </div>
            <button onclick="closeModal('calendarModal')" class="modal-close" style="background:var(--surface3); width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <form id="calendarForm">
            @csrf
            <div class="modal-body" style="padding: 28px;">
                <div style="background:var(--accent)08; border:1px solid var(--accent)22; padding:16px; border-radius:16px; margin-bottom:24px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px">
                        <div style="width:8px; height:8px; border-radius:50%; background:var(--accent); box-shadow: 0 0 8px var(--accent)"></div>
                        <span style="font-family:var(--font-mono); font-size:10px; font-weight:800; color:var(--accent)">SYSTEM INTEL</span>
                    </div>
                    <p style="font-size:11px; color:var(--text2); line-height:1.5; font-weight:600; margin:0">
                        This action will target **{{ $classes->count() }} total classes**. Each active catalog entry will receive up to **30 attendance sessions** based on the rules you defined.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-family:var(--font-mono); font-size:9px; color:var(--muted); font-weight:800; letter-spacing:.05em">ACADEMIC YEAR</label>
                    <input name="academic_year" class="form-input" type="text" placeholder="e.g. 2025-2026" required value="{{ date('Y') }}-{{ date('Y')+1 }}" style="font-weight:700">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" style="font-family:var(--font-mono); font-size:9px; color:var(--muted); font-weight:800; letter-spacing:.05em">SEMESTER</label>
                        <select name="semester" class="form-input" style="font-weight:700">
                            <option value="1">SEMESTER 1</option>
                            <option value="2">SEMESTER 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-family:var(--font-mono); font-size:9px; color:var(--muted); font-weight:800; letter-spacing:.05em">SESSION QUOTA</label>
                        <input name="sessions_count" class="form-input" type="number" value="30" min="1" max="60" style="font-weight:800; color:var(--accent)">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-family:var(--font-mono); font-size:9px; color:var(--muted); font-weight:800; letter-spacing:.05em">START DATE (BATCH LAUNCH)</label>
                    <input name="start_date" class="form-input" type="date" required value="{{ date('Y-m-d') }}" style="font-weight:700">
                </div>
            </div>
            <div class="modal-footer" style="padding: 18px 28px; background: var(--surface2); border-top: 1px solid var(--border);">
                <button type="button" onclick="closeModal('calendarModal')" class="btn-secondary" style="height:42px; padding:0 24px; border-radius:12px; font-weight:800; font-size:11px">CANCEL</button>
                <button type="submit" id="genBtn" class="btn-primary" style="height:42px; padding:0 24px; border-radius:12px; font-weight:800; font-size:11px; flex:1; background:var(--accent); box-shadow: 0 4px 15px var(--accent)44">
                    BEGIN BATCH GENERATION
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ ENROLL MODAL ═══ --}}
<div id="enrollModal" class="modal-overlay">
    <div class="modal-box" style="max-width:600px; border-radius:24px; overflow:hidden;">
        <div class="modal-head" style="padding: 24px 28px; background: var(--surface2); border-bottom: 1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:15px">
                <div style="width:42px;height:42px;border-radius:14px;background:var(--accent)22;color:var(--accent);display:flex;align-items:center;justify-content:center;box-shadow: 0 4px 15px var(--accent)22">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <div>
                    <div id="enrollModalTitle" class="modal-title" style="font-weight: 800; font-size: 17px; letter-spacing: -0.02em;">Manage Enrollment</div>
                    <div id="enrollModalSubtitle" style="font-family:var(--font-mono);font-size:10px;color:var(--muted);letter-spacing:0.02em">BATCH TRANSFER STUDENTS</div>
                </div>
            </div>
            <button onclick="closeModal('enrollModal')" class="modal-close" style="background:var(--surface3); width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <div class="modal-body" style="padding:0">
            <div style="padding:18px 28px; background:var(--surface3); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:10; display:flex; gap:12px; align-items:center;">
                <div style="position:relative; flex:1">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="var(--muted)" style="position:absolute; left:12px; top:50%; transform:translateY(-50%)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                    <input id="studentSearch" type="text" class="search-input" placeholder="Find student by name or code..." onkeyup="filterEnrollList()" style="padding-left:36px; height:42px; background:var(--surface2); border:1px solid var(--border); border-radius:12px; font-size:12px;">
                </div>
                <div id="enrollCount" style="font-family:var(--font-mono); font-size:10px; color:var(--text2); background:var(--surface2); padding:11px 16px; border-radius:12px; border:1px solid var(--border); font-weight:800">
                    {{ count($students) }} TOTAL
                </div>
            </div>
            <div id="studentListContainer" class="enroll-list" style="max-height:480px; padding: 20px 28px;">
                @php
                    $sortedStudents = $students->sortBy(function($s) { return $s->user->name ?? ''; });
                @endphp
                @foreach($sortedStudents as $s)
                <div class="enroll-row" data-id="{{ $s->id }}" data-name="{{ strtolower($s->user->name) }}" data-code="{{ strtolower($s->student_code) }}" data-class="{{ $s->class_id }}">
                    <div class="enroll-info" onclick="openStudentRecordModal({{ $s->id }})" style="cursor:pointer" onmouseover="this.querySelector('.enroll-name').style.color='var(--accent)'" onmouseout="this.querySelector('.enroll-name').style.color='var(--text)'">
                        @php
                            $allClr = ['#4f8ef7','#34d399','#a78bfa','#f0a732','#38d9a9','#f25757'];
                            $clr = $allClr[$s->id % count($allClr)];
                        @endphp
                        <div class="enroll-avatar" style="background:{{ $clr }}">
                            {{ strtoupper(substr($s->user->name, 0, 1)) }}
                        </div>
                        <div class="enroll-details">
                            <div class="enroll-name">{{ $s->user->name }}</div>
                            <div class="enroll-meta">
                                <span class="enroll-code" style="color:var(--text2); font-weight:700">{{ $s->student_code }}</span>
                                <span style="opacity:0.3">/</span>
                                <span class="enroll-major">{{ $s->major ?? 'GENERAL' }}</span>
                                <span style="opacity:0.3">•</span>
                                <span>YEAR {{ $s->year_level ?? 1 }}</span>
                            </div>
                        </div>
                    </div>
                    <button class="enroll-btn" onclick="toggleEnroll({{ $s->id }})">
                        <span class="lbl-add">ENROLL</span>
                        <span class="lbl-rem">REMOVE</span>
                        <span class="lbl-oth">MOVE HERE</span>
                    </button>
                </div>
                @endforeach
            </div>
        </div>
        <div class="modal-footer" style="background:var(--surface2); padding: 18px 28px; justify-content: space-between;">
            <div style="display:flex; align-items:center; gap:8px">
                <div style="width:6px; height:6px; border-radius:50%; background:var(--green); box-shadow:0 0 8px var(--green)"></div>
                <span style="font-family:var(--font-mono); font-size:10px; color:var(--muted); font-weight:600; letter-spacing:0.02em">ATOMIC AUTO-SYNC ACTIVE</span>
            </div>
            <button onclick="closeModal('enrollModal')" class="btn-primary" style="height:40px; padding:0 24px; font-weight:800; border-radius:12px; font-size:11px; letter-spacing:0.02em">EXIT MANAGEMENT</button>
        </div>
    </div>
</div>

<style>
/* Enrollment Management Premium Styles */
.enroll-container { display:flex; flex-direction:column; background:var(--surface2); border-radius:20px; overflow:hidden; border:1px solid var(--border); }
.enroll-list { max-height:450px; overflow-y:auto; padding:12px; }
.enroll-row { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-radius:14px; margin-bottom:8px; background:var(--surface3); border:1px solid var(--border); transition:all .2s; }
.enroll-row:hover { transform:translateX(4px); border-color:var(--accent)44; background:var(--surface2); }
.enroll-info { display:flex; align-items:center; gap:14px; }
.enroll-avatar { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; color:#fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.enroll-details { display:flex; flex-direction:column; gap:2px; }
.enroll-name { font-weight:700; font-size:14px; color:var(--text); letter-spacing:-0.01em; }
.enroll-meta { display:flex; align-items:center; gap:8px; font-family:var(--font-mono); font-size:9px; color:var(--muted); letter-spacing:0.02em; }
.enroll-major { color:var(--accent); font-weight:700; text-transform:uppercase; }
.enroll-btn { border:none; border-radius:10px; padding:8px 16px; font-family:var(--font-mono); font-size:10px; font-weight:800; letter-spacing:.05em; cursor:pointer; transition:all .2s; min-width:100px; }

/* Status-specific button colors */
.enroll-btn { background:var(--surface2); color:var(--text2); border:1px solid var(--border); }
.enroll-btn:hover { background:var(--accent); color:#fff; border-color:var(--accent); }
.enroll-btn.active { background:var(--red)15; color:var(--red); border:1px solid var(--red)33; }
.enroll-btn.active:hover { background:var(--red); color:#fff; border-color:var(--red); }
.enroll-btn.other { background:var(--amber)15; color:var(--amber); border:1px solid var(--amber)33; }
.enroll-btn.other:hover { background:var(--amber); color:#fff; border-color:var(--amber); }

.enroll-btn .lbl-rem, .enroll-btn .lbl-oth { display:none }
.enroll-btn.active .lbl-add, .enroll-btn.active .lbl-oth { display:none }
.enroll-btn.active .lbl-rem { display:inline }
.enroll-btn.other .lbl-add, .enroll-btn.other .lbl-rem { display:none }
.enroll-btn.other .lbl-oth { display:inline }

.enroll-btn.loading { opacity: 0.6; pointer-events: none; }
.enroll-btn.loading .lbl-add, .enroll-btn.loading .lbl-rem, .enroll-btn.loading .lbl-oth { display:none !important }
.enroll-btn.loading::after { content: ""; width: 10px; height: 10px; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; display: inline-block; vertical-align: middle; }
@keyframes spin { from {transform: rotate(0deg)} to {transform: rotate(360deg)} }

@media (max-width: 640px) {
    .enroll-row { padding: 12px; }
    .enroll-avatar { width: 32px; height: 32px; font-size: 12px; }
    .enroll-meta { flex-wrap: wrap; }
}
.day-selector-grid { display: flex; gap: 4px; margin-top: 4px; }
.day-chip { flex: 1; cursor: pointer; }
.day-chip input { display: none; }
.day-chip span {
    display: flex; align-items: center; justify-content: center;
    height: 32px; border-radius: 10px; background: var(--surface2);
    border: 1px solid var(--border); color: var(--muted);
    font-family: var(--font-mono); font-size: 10px; font-weight: 800;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.day-chip input:checked + span {
    background: var(--accent); color: #fff; border-color: var(--accent);
    box-shadow: 0 4px 12px var(--accent)44;
}
.day-chip.weekend span { color: var(--red)88; }
.day-chip.weekend input:checked + span { background: var(--red); color: #fff; border-color: var(--red); }
</style>

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <span>ACADEMIC</span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current">CLASSES</span>
            </div>
            <h1 class="page-title">Academic Class Schedule</h1>
            <p class="page-subtitle">AVAILABLE COURSES & SUBJECTS</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-glow"></div>
            <div class="stat-icon-wrap">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div class="stat-label">TOTAL COURSES</div>
            <div class="stat-value">{{ $classes->count() }}</div>
            <span class="stat-pill pill-up">↑ Stable</span>
        </div>
        <div class="stat-card green">
            <div class="stat-glow"></div>
            <div class="stat-icon-wrap">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div class="stat-label">UNIQUE INSTRUCTORS</div>
            <div class="stat-value">{{ $classes->pluck('teacher_id')->unique()->count() }}</div>
            <span class="stat-pill pill-up">↑ Verified</span>
        </div>
        <div class="stat-card amber">
            <div class="stat-glow"></div>
            <div class="stat-icon-wrap">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            <div class="stat-label">ACTIVE ROOMS</div>
            <div class="stat-value">{{ $classes->count() }}</div>
            <span class="stat-pill pill-amber">In Operation</span>
        </div>
        <div class="stat-card red">
            <div class="stat-glow"></div>
            <div class="stat-icon-wrap">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div class="stat-label">SYSTEM UPTIME</div>
            <div class="stat-value">99.9%</div>
            <span class="stat-pill pill-up">Optimal</span>
        </div>
    </div>

    {{-- Main two-column grid --}}
    <div class="main-grid">

        {{-- LEFT: Catalog Table --}}
        <div class="panel">

            {{-- Toolbar --}}
            <div class="catalog-toolbar" style="padding: 16px 20px; gap: 15px;">
                <div style="display:flex;align-items:center;gap:10px; flex: 1;">
                    <div style="width:8px;height:8px;border-radius:50%;background:var(--accent);box-shadow:0 0 10px var(--accent)"></div>
                    <span style="font-family:var(--font-mono);font-size:10px;font-weight:700;letter-spacing:.12em;color:var(--text2)">ACADEMIC CATALOG</span>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="search-wrap" style="width: 250px; height: 36px; background: var(--surface3); border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; padding: 0 12px;">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="var(--muted2)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                        <input id="searchInput" name="search" value="{{ request('search') }}" class="search-input" type="text" placeholder="Search catalog..." onkeyup="filterTable(event)" style="border: none; background: transparent; color: var(--text); font-size: 11px; padding-left: 10px; width: 100%; outline: none;">
                    </div>
                    
                    <select class="filter-select" onchange="filterByStatus(this.value)" style="height: 36px; background: var(--surface3); border: 1px solid var(--border); border-radius: 10px; color: var(--text2); font-family: var(--font-mono); font-size: 9px; padding: 0 35px 0 15px; cursor: pointer;">
                        <option value="">ALL STATUS</option>
                        <option value="active">ACTIVE</option>
                        <option value="waiting">WAITING</option>
                        <option value="ready">READY</option>
                    </select>

                    <div style="height: 36px; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; padding: 0 15px;">
                        <span style="font-family: var(--font-mono); font-size: 9px; color: var(--muted2); letter-spacing: .05em;">
                            <span id="rowCount" style="color: var(--accent); font-weight: 700;">{{ $classes->count() }}</span> ENTRIES
                        </span>
                    </div>

                    <button onclick="window.open('{{ route('admin.export.courses') }}', '_blank')" class="btn-secondary" style="height: 36px; gap:7px; background:var(--surface3); border:1px solid var(--border); font-size:9px; font-weight:700">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        EXPORT ALL
                    </button>

                    <button class="btn-primary" onclick="openModal('createModal')" title="Add Entry" style="width: 36px; height: 36px; border-radius: 10px; padding: 0; display: flex; align-items: center; justify-content: center; transform: scale(1); transition: transform .2s;" onmousedown="this.style.transform='scale(0.95)'" onmouseup="this.style.transform='scale(1)'">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <table class="att-table" id="classTable">
                <thead>
                    <tr>
                        <th style="padding-left:25px">SUBJECT & ID</th>
                        <th>LEAD INSTRUCTOR</th>
                        <th>LOCATION & SCHEDULE</th>
                        <th style="width:80px">STU</th>
                        <th style="width:140px">WORKLOAD (30)</th>
                        <th style="width:100px">STATUS</th>
                        <th style="text-align:right; padding-right:25px">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                @forelse($classes as $class)
                    <tr data-id="{{ $class->id }}" data-status="{{ $class->status ?? 'active' }}" class="fade-up">
                        {{-- Subject --}}
                        <td style="padding-left:25px">
                            <div class="subject-cell">
                                @php
                                    $subName = $class->subject->name ?? 'Unknown';
                                    $initials = strtoupper(substr($subName, 0, 1));
                                    $allClr = ['#4f8ef7','#34d399','#a78bfa','#f0a732','#38d9a9','#f25757'];
                                    $clr = $allClr[$class->subject_id % count($allClr)];
                                @endphp
                                <div class="subject-avatar" style="background:{{ $clr }}22;color:{{ $clr }};border:1px solid {{ $clr }}33">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <div class="subject-name">{{ $subName }}</div>
                                    <div style="display:flex; align-items:center; gap:5px; margin-top:2px;">
                                        <div class="subject-id">#{{ str_pad($class->id, 4, '0', STR_PAD_LEFT) }}</div>
                                        @if($class->group)
                                            <div style="font-family:var(--font-mono); font-size:8px; background:var(--surface3); border:1px solid var(--border); color:var(--text2); padding:1px 6px; border-radius:4px; font-weight:700">
                                                {{ $class->group->name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        {{-- Instructor --}}
                        <td>
                            @if($class->teacher && $class->teacher->user)
                                <div class="instructor-cell">
                                    <div class="instructor-dot">{{ strtoupper(substr($class->teacher->user->name, 0, 1)) }}</div>
                                    <span class="instructor-name">{{ $class->teacher->user->name }}</span>
                                </div>
                            @else
                                <span class="instructor-empty">— unassigned —</span>
                            @endif
                        </td>
                        {{-- Room & Schedule --}}
                        <td>
                            <div style="display:flex; flex-direction:column; gap:5px">
                                <span class="room-badge" style="background:var(--surface3); border:1px solid var(--border); padding:3px 8px; border-radius:6px; font-size:10px; font-weight:800; color:var(--text2); display:inline-flex; align-items:center; gap:5px; width:fit-content">
                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    RM {{ $class->room_number ?? 'TBD' }}
                                </span>
                                @if($class->schedule)
                                    @php
                                        $sched = explode(' ', $class->schedule);
                                        $days = $sched[0] ?? 'TBD';
                                        $times = $sched[1] ?? '';
                                    @endphp
                                    <div style="display:flex; align-items:center; gap:6px">
                                        <span style="font-family:var(--font-mono); font-size:9px; background:var(--accent)12; color:var(--accent); padding:1px 5px; border-radius:4px; font-weight:800">{{ strtoupper($days) }}</span>
                                        <span style="font-family:var(--font-mono); font-size:9px; color:var(--muted); font-weight:600">{{ str_replace(['(', ')'], '', $times) }}</span>
                                    </div>
                                @else
                                    <span style="font-family:var(--font-mono); font-size:8px; color:var(--red); font-weight:700">NO SCHEDULE SET</span>
                                @endif
                            </div>
                        </td>
                        {{-- Enrolled --}}
                        <td>
                            <div style="display:flex; align-items:baseline; gap:4px">
                                <span style="font-family:var(--font-display);font-weight:800;font-size:16px;color:var(--text)">{{ $class->students_count }}</span>
                                <span style="font-family:var(--font-mono);font-size:8px;color:var(--muted)">STU</span>
                            </div>
                        </td>
                        {{-- Workload / Generation Progress --}}
                        <td>
                            @php
                                // Count how many sessions exist for the current academic year/semester
                                $currentSessionsCount = \App\Models\AttendanceSession::where('class_id', $class->id)
                                    ->where('academic_year', $class->academic_year)
                                    ->where('semester', (int)$class->semester)
                                    ->count();
                                $target = 30; // Your thesis requirement
                                $pct = min(100, $currentSessionsCount > 0 ? round(($currentSessionsCount / $target) * 100) : 0);
                            @endphp
                            <div style="display:flex; flex-direction:column; gap:4px">
                                <div style="display:flex; justify-content:space-between; align-items:center">
                                    <span style="font-family:var(--font-mono); font-size:9px; font-weight:800; color:{{ $pct >= 100 ? 'var(--green)' : 'var(--accent)' }}">
                                        {{ $currentSessionsCount }}/{{ $target }}
                                    </span>
                                    <span style="font-family:var(--font-mono); font-size:8px; color:var(--muted)">SESSION</span>
                                </div>
                                <div style="height:4px; background:var(--surface3); border-radius:2px; overflow:hidden; border:1px solid var(--border)">
                                    <div style="height:100%; width:{{ $pct }}%; background:{{ $pct >= 100 ? 'var(--green)' : 'var(--accent)' }}; border-radius:2px; transition:width 0.5s ease"></div>
                                </div>
                            </div>
                        </td>
                        {{-- Status --}}
                        <td>
                            @php $st = strtolower($class->status ?? 'active'); @endphp
                            @if($st === 'active')
                                <span class="status-tag tag-active">ACTIVE</span>
                            @elseif($st === 'waiting')
                                <span class="status-tag tag-waiting">WAITING</span>
                            @else
                                <span class="status-tag tag-ready">{{ strtoupper($st) }}</span>
                            @endif
                        </td>
                        {{-- Actions --}}
                        <td style="text-align:right; padding-right:25px">
                            <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                                <button class="action-btn btn-view" title="View"
                                    onclick="openViewModal({{ $class->id }}, '{{ $class->subject_id }}', '{{ $class->teacher_id }}', '{{ addslashes($class->room_number ?? '') }}', '{{ addslashes($class->schedule ?? '') }}', '{{ $class->status ?? 'active' }}', '{{ $class->group_id }}')">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button class="action-btn btn-edit" title="Edit"
                                    onclick="openEditModal({{ $class->id }}, '{{ $class->subject_id }}', '{{ $class->teacher_id }}', '{{ addslashes($class->room_number ?? '') }}', '{{ addslashes($class->schedule ?? '') }}', '{{ $class->status ?? 'active' }}', '{{ $class->group_id }}')">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button class="action-btn btn-enroll" title="Enroll students"
                                    onclick="openEnrollModal({{ $class->id }}, '{{ addslashes($subName) }}')">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                </button>
                                <button class="action-btn" title="View Sessions"
                                    onclick="openSessionsModal({{ $class->id }}, '{{ addslashes($class->subject->name ?? 'Unknown Class') }}')"
                                    style="background:var(--amber)18;border-color:var(--amber)44;color:var(--amber)">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                <button class="action-btn" title="Semester Assignment"
                                    onclick="openCourseSemesterModal({{ $class->id }}, '{{ addslashes($class->subject->name ?? 'Unknown Class') }}', '{{ addslashes($class->schedule ?? '') }}')"
                                    style="background:var(--violet)18;border-color:var(--violet)44;color:var(--violet)">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>
                                <button class="action-btn btn-del" title="Delete"
                                    onclick="openDeleteModal({{ $class->id }})">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state" style="padding: 60px 0;">
                                <div class="empty-icon" style="background:var(--surface3); color:var(--muted); margin-bottom:15px">
                                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                </div>
                                <div class="empty-title" style="font-family:var(--font-display); font-size:16px; font-weight:700; color:var(--text)">Catalog is Empty</div>
                                <div class="empty-desc" style="font-family:var(--font-mono); font-size:10px; color:var(--muted); max-width:260px; margin:0 auto">Register your first subject above to begin the academic lifecycle.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            {{-- Pagination --}}
            @if($classes instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div style="padding:12px 18px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <span style="font-family:var(--font-mono);font-size:9px;color:var(--muted);letter-spacing:.08em">
                    SHOWING {{ $classes->firstItem() }}–{{ $classes->lastItem() }} OF {{ $classes->total() }}
                </span>
                {{ $classes->links('vendor.pagination.academy') }}
            </div>
            @endif
        </div>

        {{-- RIGHT: Sidebar panel --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- Distribution Trends --}}
            <div class="side-panel">
                <div class="side-panel-head">
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--accent);display:inline-block"></span>
                    DISTRIBUTION TRENDS
                </div>
                <div>
                    <div class="dist-item">
                        <div class="dist-row">
                            <span class="dist-label">CORE CURRICULUM</span>
                            <span class="dist-pct" style="color:var(--accent)">74%</span>
                        </div>
                        <div class="dist-track"><div class="dist-fill" style="width:74%;background:var(--accent)"></div></div>
                    </div>
                    <div class="dist-item">
                        <div class="dist-row">
                            <span class="dist-label">ELECTIVE MODULES</span>
                            <span class="dist-pct" style="color:var(--green)">18%</span>
                        </div>
                        <div class="dist-track"><div class="dist-fill" style="width:18%;background:var(--green)"></div></div>
                    </div>
                    <div class="dist-item" style="border-bottom:none">
                        <div class="dist-row">
                            <span class="dist-label">RESEARCH LABS</span>
                            <span class="dist-pct" style="color:var(--amber)">8%</span>
                        </div>
                        <div class="dist-track"><div class="dist-fill" style="width:8%;background:var(--amber)"></div></div>
                    </div>
                </div>

                {{-- Academic Period Setup --}}
                <div style="padding:16px; border-top:1px solid var(--border); background:var(--surface2)">
                    <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:.12em;color:var(--accent);margin-bottom:12px;font-weight:700">ACADEMIC PERIOD SETUP</div>
                    <button onclick="openModal('calendarModal')" class="btn-primary" style="width:100%; background:var(--surface3); border:1px solid var(--accent)40; color:var(--text); height:38px; font-size:10px; font-weight:700;">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="margin-right:8px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        GENERATE SESSIONS
                    </button>
                    <p style="font-size:9px; color:var(--muted); line-height:1.4; margin-top:10px; font-family:var(--font-mono)">BATCH CREATE 30 SESSIONS PER SEMESTER</p>
                </div>

                {{-- Quick Ops --}}
                <div style="padding:16px; border-top:1px solid var(--border)">
                    <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:.12em;color:var(--muted2);margin-bottom:12px;font-weight:700">QUICK OPERATIONS</div>
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                            <button onclick="window.location.href='/api/admin/classes/export'" class="btn-secondary" style="font-size:9px;letter-spacing:.08em;padding:9px; cursor:pointer; font-weight:700">
                                EXPORT CSV
                            </button>
                            <button onclick="showToast('Syncing cache…','info')" class="btn-secondary" style="font-size:9px;letter-spacing:.08em;padding:9px; cursor:pointer; font-weight:700">
                                SYNC CACHE
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Hub Integrity --}}
                <div class="hub-integrity" style="padding:16px; border-top:1px solid var(--border)">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                        <div class="hub-dot" style="background:var(--accent)"></div>
                        <span class="hub-label" style="font-family:var(--font-mono); font-size:9px; color:var(--muted2); letter-spacing:.05em">HUB INTEGRITY SCORE</span>
                    </div>
                    <div style="display:flex; align-items:baseline; gap:10px">
                        <div class="hub-grade" style="font-size:28px; font-weight:800; color:var(--accent); font-family:var(--font-display)">A+</div>
                        <div style="font-size:9px; color:var(--muted); line-height:1.2">REGISTRY DATA<br>OPTIMIZED</div>
                    </div>
                </div>
            </div>

            {{-- DB Activity --}}
            <div class="side-panel">
                <div class="side-panel-head">
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--green);animation:blink 2s infinite;display:inline-block"></span>
                    RECENT ACTIVITY
                </div>
                <div class="db-entries">
                    <div class="db-entry">
                        <div class="db-dot" style="background:var(--green)"></div>
                        <span class="db-time">09:14</span>
                        <span class="db-action" style="color:var(--green)">INSERT</span>
                        <span style="font-family:var(--font-mono);font-size:9px;color:var(--muted);margin-left:4px">courses.#0009</span>
                    </div>
                    <div class="db-entry">
                        <div class="db-dot" style="background:var(--amber)"></div>
                        <span class="db-time">08:52</span>
                        <span class="db-action" style="color:var(--amber)">UPDATE</span>
                        <span style="font-family:var(--font-mono);font-size:9px;color:var(--muted);margin-left:4px">courses.#0003</span>
                    </div>
                    <div class="db-entry">
                        <div class="db-dot" style="background:var(--red)"></div>
                        <span class="db-time">08:31</span>
                        <span class="db-action" style="color:var(--red)">DELETE</span>
                        <span style="font-family:var(--font-mono);font-size:9px;color:var(--muted);margin-left:4px">courses.#0011</span>
                    </div>
                    <div class="db-entry">
                        <div class="db-dot" style="background:var(--accent)"></div>
                        <span class="db-time">08:10</span>
                        <span class="db-action" style="color:var(--accent)">SELECT</span>
                        <span style="font-family:var(--font-mono);font-size:9px;color:var(--muted);margin-left:4px">all courses</span>
                    </div>
                </div>
            </div>

        </div>{{-- end right column --}}
    </div>{{-- end main-grid --}}

{{-- ═══ SEMESTER ASSIGNMENT MODAL ═══ --}}
<div id="courseSemesterModal" class="modal-overlay">
    <div class="modal-box" style="max-width:640px; border-radius: 20px; overflow: hidden;">
        <div class="modal-head" style="padding: 24px 24px 16px; background: var(--surface2); border-bottom: 1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:15px">
                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg, var(--violet)22, var(--violet)08);color:var(--violet);display:flex;align-items:center;justify-content:center;box-shadow: 0 4px 12px rgba(0,0,0,0.1)">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <div class="modal-title" style="font-size: 16px; font-weight: 800; letter-spacing: -0.01em;">Semester Management</div>
                    <div id="csmSubtitle" style="font-family:var(--font-mono);font-size:10px;color:var(--accent);font-weight:700;letter-spacing:0.05em">LOADING COURSE...</div>
                </div>
            </div>
            <button onclick="closeModal('courseSemesterModal')" class="modal-close" style="background: var(--surface3); border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="modal-body" style="padding:24px; max-height: 70vh; overflow-y: auto;">
            {{-- Preview Timeline Section --}}
            <div id="csmPreview" class="csm-timeline-preview" style="display:none">
                {{-- Preview content injected via JS --}}
            </div>

            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <div style="height: 1px; flex: 1; background: linear-gradient(90deg, var(--accent), transparent);"></div>
                <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:.12em;color:var(--accent);font-weight:800">ASSIGN NEW SEMESTER</div>
                <div style="height: 1px; flex: 1; background: linear-gradient(270deg, var(--accent), transparent);"></div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Academic Year <span class="req">*</span></label>
                    <input id="csmYear" class="form-input" type="text" placeholder="2025-2026" value="{{ now()->year }}-{{ now()->year + 1 }}" style="background:var(--surface3)">
                </div>
                <div class="form-group">
                    <label class="form-label">Semester <span class="req">*</span></label>
                    <select id="csmSemester" class="form-input" style="background:var(--surface3)">
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:20px">
                <label class="form-label" style="font-size:9px">Preferred Days <span class="req">*</span></label>
                <div class="day-selector-grid" id="csmDaySelector">
                    <label class="day-chip"><input type="checkbox" value="Mon"><span>M</span></label>
                    <label class="day-chip"><input type="checkbox" value="Tue"><span>T</span></label>
                    <label class="day-chip"><input type="checkbox" value="Wed"><span>W</span></label>
                    <label class="day-chip"><input type="checkbox" value="Thu"><span>T</span></label>
                    <label class="day-chip"><input type="checkbox" value="Fri"><span>F</span></label>
                    <label class="day-chip weekend"><input type="checkbox" value="Sat"><span>S</span></label>
                    <label class="day-chip weekend"><input type="checkbox" value="Sun"><span>S</span></label>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Academic Start <span class="req">*</span></label>
                    <div style="position:relative">
                        <input id="csmStart" class="form-input" type="date" oninput="csmPreview()" style="background:var(--surface3); padding-right: 12px;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Break / Holiday <span style="color:var(--muted); font-size: 8px;">(OPTIONAL)</span></label>
                    <input id="csmHoliday" class="form-input" type="date" oninput="csmPreview()" style="background:var(--surface3)">
                </div>
            </div>

            <div class="form-grid-2" style="margin-top: -5px">
               <div class="form-group">
                    <label class="form-label" style="display:flex; justify-content:space-between">
                        Session 1 <span style="color:var(--muted); font-size: 8px;">(START/END)</span>
                    </label>
                    <div style="display:flex; align-items:center; gap:8px">
                        <input id="csmTimeStart" class="form-input" type="time" value="08:00" style="background:var(--surface3); font-size: 11px;">
                        <span style="font-size:10px; color:var(--muted); font-weight: 700;">TO</span>
                        <input id="csmTimeEnd" class="form-input" type="time" value="09:30" style="background:var(--surface3); font-size: 11px;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display:flex; justify-content:space-between">
                        Session 2 <span style="color:var(--muted); font-size: 8px;">(OPTIONAL)</span>
                    </label>
                    <div style="display:flex; align-items:center; gap:8px">
                        <input id="csmTimeStart2" class="form-input" type="time" style="background:var(--surface3); font-size: 11px;">
                        <span style="font-size:10px; color:var(--muted); font-weight: 700;">TO</span>
                        <input id="csmTimeEnd2" class="form-input" type="time" style="background:var(--surface3); font-size: 11px;">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Target Capacity <span style="color:var(--muted); font-size: 8px;">(SESSIONS)</span></label>
                <div style="position:relative">
                    <input id="csmCount" class="form-input" type="number" value="30" min="1" max="100" style="background:var(--surface3); font-weight: 700; color: var(--accent); padding-right: 40px">
                    <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%); font-family:var(--font-mono); font-size:9px; color:var(--muted); pointer-events:none">SESSIONS</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Internal Notes</label>
                <textarea id="csmNotes" class="form-input" rows="2" placeholder="Administrative references, special conditions, etc." style="background:var(--surface3); height: 60px; padding-top: 10px; resize: none;"></textarea>
            </div>

            {{-- Dynamic Assignments List Container --}}
            <div style="margin-top: 30px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px">
                    <div style="display:flex; align-items:center; gap:10px">
                        <div style="width:14px; height:2px; background:var(--accent); border-radius:2px"></div>
                        <span style="font-family:var(--font-mono); font-size:10px; font-weight:800; letter-spacing:.12em; color:var(--text2)">ACTIVE ASSIGNMENTS</span>
                    </div>
                    <span id="csmCountBadge" style="font-family:var(--font-mono); font-size:9px; color:var(--accent); background:var(--accent)18; padding:4px 12px; border-radius:20px; font-weight:800; border:1px solid var(--accent)22">0 ACTIVE</span>
                </div>
                <div id="csmItems">
                    {{-- Cards injected via csmLoad() --}}
                </div>
            </div>

        </div>

        <style>
            /* Premium Semester Assignment Styles */
            .csm-card {
                background: var(--surface2);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 22px 26px;
                margin-bottom: 18px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
            }
            .csm-card:hover {
                border-color: var(--border2);
                transform: translateY(-2px);
                box-shadow: var(--shadow-lg);
            }
            .csm-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 22px;
            }
            .csm-title-group {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .csm-accent-bar {
                width: 3.5px;
                height: 22px;
                background: var(--violet);
                border-radius: 4px;
            }
            .csm-title {
                font-family: var(--font-mono);
                font-size: 13px;
                font-weight: 700;
                color: var(--text);
                letter-spacing: 0.02em;
            }
            .csm-badge {
                font-family: var(--font-mono);
                font-size: 9px;
                font-weight: 800;
                padding: 4px 10px;
                border-radius: 20px;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }
            .csm-badge.upcoming { background: #EEEDFE; color: #534AB7; }
            .csm-badge.active   { background: rgba(52, 211, 153, 0.15); color: var(--green); }
            .csm-badge.completed { background: var(--surface3); color: var(--muted); }

            .csm-remove-btn {
                font-family: var(--font-mono);
                font-size: 10px;
                font-weight: 800;
                padding: 10px 22px;
                border-radius: 12px;
                border: 1.5px solid var(--border2);
                background: transparent;
                color: var(--text);
                cursor: pointer;
                transition: all 0.2s ease;
                text-transform: uppercase;
                letter-spacing: 0.1em;
            }
            .csm-remove-btn:hover {
                background: var(--red);
                color: #fff;
                border-color: var(--red);
            }

            .csm-divider {
                height: 1px;
                background: var(--border);
                margin: 0 -26px 22px;
                opacity: 0.6;
            }

            .csm-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 24px;
                margin-bottom: 24px;
            }
            .csm-label {
                font-family: var(--font-mono);
                font-size: 9px;
                font-weight: 700;
                color: var(--muted2);
                text-transform: uppercase;
                margin-bottom: 9px;
                letter-spacing: 0.1em;
            }
            .csm-value {
                font-family: var(--font-mono);
                font-size: 14px;
                font-weight: 800;
                color: var(--text);
                letter-spacing: 0.02em;
            }
            .csm-value.green { color: var(--green); }
            .csm-value.muted { font-size: 11px; color: var(--muted); font-weight: 500; }

            .csm-progress-section {
                background: var(--bg);
                border-radius: 14px;
                padding: 18px 22px;
                border: 1px solid var(--border);
            }
            .csm-progress-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 14px;
            }
            .csm-progress-track {
                height: 6px;
                background: var(--surface3);
                border-radius: 10px;
                overflow: visible;
                position: relative;
            }
            .csm-progress-fill {
                height: 100%;
                background: var(--violet);
                border-radius: 10px;
                transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                box-shadow: 0 0 15px rgba(167, 139, 250, 0.3);
            }
            .csm-progress-fill::after {
                content: '';
                position: absolute;
                right: -4px;
                top: 50%;
                transform: translateY(-50%);
                width: 10px;
                height: 10px;
                background: #fff;
                border: 2px solid var(--violet);
                border-radius: 50%;
                box-shadow: 0 0 10px var(--violet);
                z-index: 2;
            }
        </style>
        <div class="modal-footer" style="padding: 16px 24px; background: var(--surface2); border-top: 1px solid var(--border);">
            <button type="button" onclick="closeModal('courseSemesterModal')" class="btn-secondary" style="height: 42px; font-weight: 700;">CANCEL</button>
            <button type="button" onclick="csmSave()" class="btn-primary" id="csmSaveBtn" style="height: 42px; flex: 1; font-weight: 700; gap: 8px; box-shadow: 0 4px 15px var(--accent)44;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                INITIATE ASSIGNMENT
            </button>
        </div>
    </div>
</div>

        {{-- ═══ STUDENT RECORD MODAL ═══ --}}
<div id="studentDetailModal" class="modal-overlay" style="z-index: 1100;">
    <div class="modal-box" style="max-width:500px; border-radius:28px; overflow-y:auto; max-height:90vh;">
        <div class="modal-body" style="padding:0; position:relative">
            {{-- Profile Header --}}
            <div style="background:linear-gradient(135deg, var(--accent) 0%, var(--violet) 100%); padding:40px 30px 60px; color:white; position:relative">
                <button onclick="closeModal('studentDetailModal')" style="position:absolute; top:20px; right:20px; background:rgba(255,255,255,0.15); border:none; width:32px; height:32px; border-radius:50%; color:white; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div style="display:flex; align-items:center; gap:22px">
                    <div id="smInitials" style="width:72px; height:72px; border-radius:24px; background:rgba(255,255,255,0.25); border:2px solid rgba(255,255,255,0.4); display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:800; text-shadow:0 2px 10px rgba(0,0,0,0.1)">-</div>
                    <div>
                        <div id="smName" style="font-size:20px; font-weight:800; letter-spacing:-0.01em; margin-bottom:4px">Loading Name...</div>
                        <div id="smCode" style="font-family:var(--font-mono); font-size:11px; font-weight:700; color:rgba(255,255,255,0.8); background:rgba(0,0,0,0.15); padding:3px 12px; border-radius:20px; display:inline-block">ID - ???</div>
                    </div>
                </div>
            </div>

            {{-- Info Cards (Floating) --}}
            <div style="margin-top:-35px; padding:0 24px 30px">
                <div style="background:var(--surface2); border:1px solid var(--border); border-radius:20px; box-shadow:var(--shadow-xl); padding:24px">
                    <div style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap:20px; align-items:center">
                        <div>
                            <div style="display:inline-block; font-family:var(--font-mono); font-size:9px; font-weight:800; color:var(--green); background:var(--green)18; padding:3px 12px; border-radius:20px; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:12px" id="smStatusBadge">ACTIVE STUDENT</div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px">
                                <div>
                                    <div style="font-family:var(--font-mono); font-size:8px; color:var(--muted); text-transform:uppercase; margin-bottom:4px">YEAR LEVEL</div>
                                    <div style="font-size:13px; font-weight:700; color:var(--text)" id="smYear">1st Year</div>
                                </div>
                                <div>
                                    <div style="font-family:var(--font-mono); font-size:8px; color:var(--muted); text-transform:uppercase; margin-bottom:4px">MAJOR</div>
                                    <div style="font-size:13px; font-weight:700; color:var(--text)" id="smMajor">Technology</div>
                                </div>
                            </div>
                        </div>
                        <div style="background:var(--surface3); border-radius:16px; padding:15px; text-align:center; border:1px solid var(--border)">
                            <div style="font-family:var(--font-mono); font-size:8px; color:var(--muted); text-transform:uppercase; margin-bottom:5px">ATTENANCE RATE</div>
                            <div style="font-family:var(--font-display); font-size:24px; font-weight:800; color:var(--accent)" id="smRate">0%</div>
                            <div style="font-family:var(--font-mono); font-size:8px; color:var(--muted2); margin-top:3px" id="smJoinedDate">JOINED AT -</div>
                        </div>
                    </div>

                    <div style="height:1px; background:var(--border); margin:20px 0; opacity:0.5"></div>

                    {{-- Recent Attendance --}}
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px">
                            <span style="font-family:var(--font-mono); font-size:9px; font-weight:800; color:var(--text2); letter-spacing:0.05em">RECENT ATTENDANCE</span>
                            <span style="font-size:9px; color:var(--accent); font-weight:700">LATEST 10</span>
                        </div>
                        <div id="smHistory" style="display:flex; flex-direction:column; gap:10px">
                            {{-- Rows injected --}}
                        </div>
                    </div>
                
                    {{-- Action Footer --}}
                    <div style="display:flex; gap:10px; margin-top:20px; padding-top:15px; border-top:1px solid var(--border)">
                        <button type="button" onclick="closeModal('studentDetailModal')" class="btn-secondary" style="height:44px; flex:1; font-weight:700">CLOSE PROFILE</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



{{-- ═══ SESSION LIST MODAL ═══ --}}
<div id="sessionsModal" class="modal-overlay">
    <div class="modal-box" style="max-width:700px; border-radius:24px;">
        <div class="modal-head" style="padding: 24px 28px; background: var(--surface2); border-bottom: 1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:15px">
                <div style="width:42px;height:42px;border-radius:14px;background:var(--amber)22;color:var(--amber);display:flex;align-items:center;justify-content:center;box-shadow: 0 4px 15px var(--amber)22">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="modal-title" style="font-weight: 800; font-size: 17px; letter-spacing: -0.02em;">Session History</div>
                    <div id="sessionsModalSubtitle" style="font-family:var(--font-mono);font-size:10px;color:var(--muted);letter-spacing:0.02em;text-transform:uppercase">TIMELINE ANALYTICS</div>
                </div>
            </div>
            <button onclick="closeModal('sessionsModal')" class="modal-close" style="background:var(--surface3); width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <div class="modal-body" style="padding:0; max-height: 500px; overflow-y: auto;">
            <div id="sessionsListContainer" style="padding: 20px 28px;">
                {{-- Dynamic content --}}
            </div>
        </div>
        <div class="modal-footer" style="background:var(--surface2); padding: 18px 28px;">
            <button onclick="closeModal('sessionsModal')" class="btn-secondary" style="width:100%; height:42px; font-weight:800; border-radius:12px; font-size:11px; letter-spacing:0.02em">CLOSE TIMELINE</button>
        </div>
    </div>
</div>

{{-- ═══ SESSION DETAIL MODAL ═══ --}}
<div id="sessionDetailModal" class="modal-overlay" style="z-index: 1000;">
    <div class="modal-box" style="max-width:600px; border-radius:24px;">
        <div class="modal-head" style="padding: 24px 28px; background: var(--surface2); border-bottom: 1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:15px">
                <div style="width:42px;height:42px;border-radius:14px;background:var(--accent)22;color:var(--accent);display:flex;align-items:center;justify-content:center;box-shadow: 0 4px 15px var(--accent)22">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
                <div>
                    <div id="sdmTitle" class="modal-title" style="font-weight: 800; font-size: 17px; letter-spacing: -0.02em;">Attendance Detail</div>
                    <div id="sdmSubtitle" style="font-family:var(--font-mono);font-size:10px;color:var(--muted);letter-spacing:0.02em;text-transform:uppercase">SESSION RECORD</div>
                </div>
            </div>
            <button onclick="closeModal('sessionDetailModal')" class="modal-close" style="background:var(--surface3); width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <div class="modal-body" style="padding:0; max-height: 400px; overflow-y: auto;">
            <div id="sdmStats" style="padding: 15px 28px; background: var(--surface3); border-bottom: 1px solid var(--border); display: flex; gap: 20px;">
                {{-- Stats injected --}}
            </div>
            <div id="sdmList" style="padding: 20px 28px;">
                {{-- Student list injected --}}
            </div>
        </div>
        <div class="modal-footer" style="background:var(--surface2); padding: 18px 28px;">
            <button onclick="closeModal('sessionDetailModal')" class="btn-secondary" style="width:100%; height:42px; font-weight:800; border-radius:12px; font-size:11px; letter-spacing:0.02em">RETURN TO TIMELINE</button>
        </div>
    </div>
</div>


<script>
// ─── Modals ────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});

// ─── Toast ─────────────────────────────────────
function showToast(msg, type = 'success') {
    const t  = document.getElementById('toast');
    const ic = document.getElementById('toastIcon');
    const tx = document.getElementById('toastMsg');
    t.className = `toast show toast-${type}`;
    ic.textContent = type === 'success' ? '✓' : type === 'error' ? '✕' : 'i';
    tx.textContent = msg;
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('show'), 3200);
}

// ── Search + Filter (Server-side) ─────────────
let filterTimeout = null;
function filterTable(e) {
    if (e && e.type === 'keyup' && e.key !== 'Enter') {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => filterTable(), 800);
        return;
    }
    const q = document.getElementById('searchInput').value;
    const params = new URLSearchParams(window.location.search);
    if (q) params.set('search', q); else params.delete('search');
    params.set('page', 1);
    window.location.href = `${window.location.pathname}?${params.toString()}`;
}

function filterByStatus(val) {
    const params = new URLSearchParams(window.location.search);
    if (val) params.set('status', val); else params.delete('status');
    params.set('page', 1);
    window.location.href = `${window.location.pathname}?${params.toString()}`;
}

// ─── Delete ────────────────────────────────────
let pendingDeleteId = null;
function openDeleteModal(id) { pendingDeleteId = id; openModal('deleteModal'); }

document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
    if (!pendingDeleteId) return;
    const btn = document.getElementById('confirmDeleteBtn');
    const ogHtml = btn.innerHTML;
    btn.innerHTML = 'DELETING...';
    try {
        const res = await fetch(`/api/admin/classes/${pendingDeleteId}`, { 
            method: 'DELETE', 
            headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' } 
        });
        const data = await res.json();
        if (data.success) {
            const row = document.querySelector(`tr[data-id="${pendingDeleteId}"]`);
            if (row) { row.style.opacity = 0; setTimeout(() => row.remove(), 300); }
            showToast('Class entry deleted permanently.', 'success');
            closeModal('deleteModal');
            // Update counts if necessary
            const cntEl = document.getElementById('rowCount');
            if(cntEl) cntEl.textContent = parseInt(cntEl.textContent) - 1;
        } else {
            showToast(data.error || 'Failed to delete entry.', 'error');
        }
    } catch (e) {
        showToast('Network error on delete.', 'error');
    }
    btn.innerHTML = ogHtml;
    pendingDeleteId = null;
});

// ─── View (read-only) ──────────────────────────
function openViewModal(id, subject, instructor, room, schedule, status, groupId) {
    document.getElementById('editModalTitle').textContent = 'Class Details';
    document.getElementById('editClassId').value    = id;
    document.getElementById('editSubjectName').value = subject;
    document.getElementById('editInstructor').value  = instructor;
    document.getElementById('editRoom').value         = room;
    document.getElementById('editClassGroup').value    = groupId || '';
    
    // Sync Day Chips
    const syncDayChips = (selectorId, sched) => {
        document.querySelectorAll(`#${selectorId} input`).forEach(inp => {
            inp.checked = false;
            if (sched.includes('mon-fri') || sched.includes('weekday')) {
                if (['Mon','Tue','Wed','Thu','Fri'].includes(inp.value)) inp.checked = true;
            } else if (sched.includes(inp.value.toLowerCase()) || sched.includes(inp.value)) {
                inp.checked = true;
            }
        });
    };

    if (schedule) {
        syncDayChips('editDaySelector', schedule.toLowerCase());
        const parts = schedule.split(' ');
        const timePart = parts.length > 1 ? parts[1] : '';
        if (timePart.includes('(')) {
            const inner = timePart.replace('(', '').replace(')', '');
            const times = inner.includes('–') ? inner.split('–') : inner.split('-');
            if (times.length >= 2) {
                document.getElementById('editTimeStart').value = times[0];
                document.getElementById('editTimeEnd').value   = times[1];
            }
        }
    }
    
    let stEl = document.getElementById('editStatus');
    if(stEl) stEl.value = status;

    ['editSubjectName','editInstructor','editRoom', 'editTimeStart', 'editTimeEnd', 'editStatus', 'editClassGroup'].forEach(f => {
        const el = document.getElementById(f);
        if(!el) return;
        el.disabled = true;
        el.readOnly = true;
    });
    document.querySelectorAll('#editDaySelector input').forEach(inp => inp.disabled = true);
    document.getElementById('editModalFooter').style.display = 'none';
    openModal('editModal');
}

// ─── Edit ──────────────────────────────────────
function openEditModal(id, subject, instructor, room, schedule, status, groupId) {
    document.getElementById('editModalTitle').textContent = 'Edit Class';
    document.getElementById('editClassId').value    = id;
    document.getElementById('editSubjectName').value = subject;
    document.getElementById('editInstructor').value  = instructor;
    document.getElementById('editRoom').value         = room;
    document.getElementById('editClassGroup').value    = groupId || '';
    
    // Sync Day Chips
    const syncDayChips = (selectorId, sched) => {
        document.querySelectorAll(`#${selectorId} input`).forEach(inp => {
            inp.checked = false;
            // Disable only for "View" mode? No, this function is shared.
            // Actually I should pass a "disabled" flag.
        });
        
        const lowerSched = sched.toLowerCase();
        document.querySelectorAll(`#${selectorId} input`).forEach(inp => {
            if (lowerSched.includes('mon-fri') || lowerSched.includes('weekday')) {
                if (['Mon','Tue','Wed','Thu','Fri'].includes(inp.value)) inp.checked = true;
            } else if (lowerSched.includes(inp.value.toLowerCase())) {
                inp.checked = true;
            }
        });
    };

    if (schedule) {
        syncDayChips('editDaySelector', schedule);
        const parts = schedule.split(' ');
        const timePart = parts.length > 1 ? parts[parts.length - 1] : '';
        if (timePart.includes('(')) {
            const inner = timePart.replace('(', '').replace(')', '');
            const times = inner.includes('–') ? inner.split('–') : inner.split('-');
            if (times.length >= 2) {
                document.getElementById('editTimeStart').value = times[0];
                document.getElementById('editTimeEnd').value   = times[1];
            }
        }
    }

    let stEl = document.getElementById('editStatus');
    if(stEl) stEl.value = status;

    ['editSubjectName','editInstructor','editRoom', 'editTimeStart', 'editTimeEnd', 'editStatus', 'editClassGroup'].forEach(f => {
        const el = document.getElementById(f);
        if(!el) return;
        el.disabled = false;
        el.readOnly = false;
    });
    document.querySelectorAll('#editDaySelector input').forEach(inp => inp.disabled = false);
    document.getElementById('editModalFooter').style.display = 'flex';
    openModal('editModal');
}
document.getElementById('editForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const ogHtml = btn.innerHTML;
    btn.innerHTML = 'SAVING...';
    
    try {
        const id = document.getElementById('editClassId').value;
        const formData = new FormData(e.target);
        
        // Format schedule from chips
        const getSelectedDays = (selectorId) => {
            const checked = Array.from(document.querySelectorAll(`#${selectorId} input:checked`));
            if (checked.length === 5 && checked.every(c => ['Mon','Tue','Wed','Thu','Fri'].includes(c.value))) return 'Mon-Fri';
            return checked.length > 0 ? checked.map(c => c.value).join('/') : 'TBD';
        };

        const days = getSelectedDays('editDaySelector');
        const tStart = formData.get('time_start') || '00:00';
        const tEnd = formData.get('time_end') || '00:00';
        const scheduleStr = `${days} (${tStart}-${tEnd})`;

        const payload = {
            subject_id: formData.get('subject_id'),
            teacher_id: formData.get('teacher_id'),
            group_id:   formData.get('group_id'),
            room_number: formData.get('room_number'),
            schedule:   scheduleStr,
            status:     formData.get('status')
        };
        
        const res = await fetch(`/api/admin/classes/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if (data.success) {
            showToast('Class updated successfully!', 'success');
            closeModal('editModal');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast(data.error || 'Failed to update entry.', 'error');
        }
    } catch(err) {
        showToast('Network error occurred.', 'error');
    }
    btn.innerHTML = ogHtml;
});

// ─── Create ────────────────────────────────────
document.getElementById('createForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const ogHtml = btn.innerHTML;
    btn.innerHTML = 'SAVING...';
    
    try {
        const formData = new FormData(e.target);
        
        // Find selected subject text to generate DB title
        let subName = 'New Module';
        const subSelect = e.target.querySelector('select[name="subject_id"]');
        if (subSelect && subSelect.selectedIndex > 0) {
            subName = subSelect.options[subSelect.selectedIndex].text;
        }
        
        // Format schedule from chips
        const getSelectedDays = (selectorId) => {
            const checked = Array.from(document.querySelectorAll(`#${selectorId} input:checked`));
            if (checked.length === 5 && checked.every(c => ['Mon','Tue','Wed','Thu','Fri'].includes(c.value))) return 'Mon-Fri';
            return checked.length > 0 ? checked.map(c => c.value).join('/') : 'TBD';
        };

        const days = getSelectedDays('createDaySelector');
        const tStart = formData.get('time_start') || '00:00';
        const tEnd = formData.get('time_end') || '00:00';
        const scheduleStr = `${days} (${tStart}-${tEnd})`;

        const payload = {
            name: subName + ' Class', // "name" goes directly to DB classes.name
            subject_id: formData.get('subject_id'),
            teacher_id: formData.get('teacher_id'),
            group_id:   formData.get('group_id'),
            room_number: formData.get('room_number'),
            schedule:   scheduleStr,
            status:     formData.get('status')
        };
        
        const res = await fetch('/api/admin/classes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if (data.success) {
            showToast('New catalog entry created!', 'success');
            closeModal('createModal');
            e.target.reset();
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast(data.error || 'Failed to create entry.', 'error');
        }
    } catch(err) {
        showToast('Network error occurred.', 'error');
    }
    btn.innerHTML = ogHtml;
});

// ─── Enrollment Management ──────────────────────
let enrollingClassId = null;
function openEnrollModal(classId, className) {
    enrollingClassId = classId;
    document.getElementById('enrollModalTitle').textContent = `Manage Enrollment: ${className}`;
    refreshEnrollList();
    openModal('enrollModal');
}

function filterEnrollList() {
    const q = document.getElementById('studentSearch').value.toLowerCase();
    let count = 0;
    document.querySelectorAll('.enroll-row').forEach(row => {
        const nameMatch = row.dataset.name.includes(q);
        const codeMatch = row.dataset.code.includes(q);
        const show = nameMatch || codeMatch;
        row.style.display = show ? 'flex' : 'none';
        if (show) count++;
    });
    const countBadge = document.getElementById('enrollCount');
    if(countBadge) countBadge.textContent = `${count} RESULT${count === 1 ? '' : 'S'}`;
}

function refreshEnrollList() {
    document.querySelectorAll('.enroll-row').forEach(row => {
        const rowClassId = parseInt(row.dataset.class);
        const btn = row.querySelector('.enroll-btn');
        btn.className = 'enroll-btn';
        if (rowClassId === enrollingClassId) {
            btn.classList.add('active');
        } else if (rowClassId > 0) {
            btn.classList.add('other');
        }
    });
}

async function toggleEnroll(studentId) {
    const row = document.querySelector(`.enroll-row[data-id="${studentId}"]`);
    const btn = row.querySelector('.enroll-btn');
    const isEnrolled = parseInt(row.dataset.class) === enrollingClassId;
    const newClassId = isEnrolled ? null : enrollingClassId;
    
    btn.classList.add('loading');

    try {
        const res = await fetch(`/api/admin/students/${studentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({ class_id: newClassId })
        });
        const data = await res.json();
        if (data.success) {
            row.dataset.class = newClassId || 0;
            refreshEnrollList();
            showToast(newClassId ? 'Student cohort updated.' : 'Removed from manual enrollment.', 'success');
        } else {
            showToast(data.error || 'Server error', 'error');
        }
    } catch (e) { 
        showToast('Sync failure', 'error'); 
    }
    btn.classList.remove('loading');
}

// ─── Generate Calendar ──────────────────────────
document.getElementById('calendarForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('genBtn');
    const ogHtml = btn.innerHTML;
    btn.innerHTML = 'GENERATING...';
    btn.disabled = true;

    try {
        const formData = new FormData(e.target);
        const payload = Object.fromEntries(formData.entries());
        
        const res = await fetch('/api/admin/generate-calendar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if (data.success) {
            showToast(`BATCH SUCCESS: ${data.generated} academic slots established across the catalog.`, 'success');
            closeModal('calendarModal');
            setTimeout(() => location.reload(), 1500); // Reload to show new 30/30 progress bars
        } else {
            showToast(data.error || 'The system could not generate the batch period.', 'error');
        }
    } catch(err) {
        showToast('Connection lost during batch generation.', 'error');
    }
    btn.innerHTML = ogHtml;
    btn.disabled = false;
});

// ── COURSE PAGE: SEMESTER ASSIGNMENT ──────────────────────────────
const csmCsrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
let _csmClassId = null;

function csmPreview() {
    const sv = document.getElementById('csmStart').value;
    const hv = document.getElementById('csmHoliday').value;
    const previewDiv = document.getElementById('csmPreview');
    
    if (!sv) { 
        previewDiv.innerHTML = `
            <div style="padding:12px; text-align:center; background:var(--surface3)44; border-radius:12px; border:1px dashed var(--border)">
                <div style="font-family:var(--font-mono); font-size:9px; font-weight:700; color:var(--muted); letter-spacing:.05em">TIMELINE ARCHITECTURE INACTIVE</div>
                <div style="font-size:8px; color:var(--muted); font-family:var(--font-mono); margin-top:2px">Awaiting start date for temporal visualization</div>
            </div>
        `;
        previewDiv.style.display = 'block';
        return; 
    }
    
    previewDiv.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:15px">
            <div>
                <div class="csm-label">ESTIMATED COMPLETION</div>
                <div id="csmPrevEnd" style="font-weight:800;font-size:14px;color:var(--text); line-height:1">-</div>
            </div>
            <div style="text-align:right">
                <div class="csm-label">ACADEMIC SPAN</div>
                <div id="csmPrevDays" style="font-weight:800;font-size:14px;color:var(--green); line-height:1">-</div>
            </div>
        </div>
        <div style="position:relative;height:24px;background:var(--surface2);border-radius:8px;overflow:hidden;border:1px solid var(--border); box-shadow: inset 0 2px 4px rgba(0,0,0,0.1)">
            <div id="csmPrevBar" style="position:absolute;left:0;top:0;height:100%;background:linear-gradient(90deg, var(--violet)44, var(--accent)44);border-radius:8px"></div>
            <div id="csmPrevHolBar" style="position:absolute;top:0;height:100%;background:var(--amber)33; border-left:1px solid var(--amber)44; border-right:1px solid var(--amber)44;"></div>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--font-mono);font-size:8px;color:var(--text);font-weight:700;letter-spacing:0.1em;text-shadow: 0 1px 2px rgba(0,0,0,0.5)">TEMPORAL SEMESTER PROJECTION</div>
        </div>
        <div id="csmPrevHol" style="margin-top:10px; font-family:var(--font-mono); font-size:9px; color:var(--amber); font-weight:600; text-align:center"></div>
    `;
    previewDiv.style.display = 'block';
    const start = new Date(sv);
    const end = new Date(sv); end.setMonth(end.getMonth() + 4);
    const total = Math.round((end-start)/86400000);
    let hDays=0, hStart=null, hEnd=null;
    if(hv){ hStart=new Date(hv); hEnd=new Date(hv); hEnd.setDate(hEnd.getDate()+21); hDays=21; }
    const active = total - hDays;
    const fmt = d => d ? d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : 'N/A';
    document.getElementById('csmPrevEnd').textContent  = fmt(end);
    document.getElementById('csmPrevDays').textContent = active + ' DAYS';
    const holEl = document.getElementById('csmPrevHol');
    if (hStart) {
        holEl.innerHTML = `<span style="opacity:0.6">HOLIDAY BREAK:</span> ${fmt(hStart)} — ${fmt(hEnd)}`;
    } else {
        holEl.textContent = '';
    }
    
    document.getElementById('csmPrevBar').style.width  = '100%';
    if(hStart && total>0){
        const off=Math.round(((hStart-start)/(end-start))*100);
        const wid=Math.min(Math.round((21/total)*100),100-off);
        const holBar = document.getElementById('csmPrevHolBar');
        holBar.style.left=off+'%';
        holBar.style.width=wid+'%';
    } else { 
        document.getElementById('csmPrevHolBar').style.width='0'; 
    }
}

async function openCourseSemesterModal(classId, className, schedule) {
    if(!classId){ showToast('Invalid class ID.','error'); return; }
    _csmClassId = classId;
    
    // Initial UI state
    document.getElementById('csmSubtitle').textContent = className.toUpperCase();
    document.getElementById('csmStart').value='';
    document.getElementById('csmHoliday').value='';
    document.getElementById('csmNotes').value='';
    
    // Refresh existing assignments list immediately
    csmLoad(classId);
    
    // Default times from class schedule if possible
    document.getElementById('csmTimeStart2').value = '';
    document.getElementById('csmTimeEnd2').value = '';

    // Reset Day Chips in CSM
    document.querySelectorAll('#csmDaySelector input').forEach(i => i.checked = false);

    if (schedule && schedule.includes('(')) {
        const daysPart = schedule.split('(')[0].trim();
        const lowerDays = daysPart.toLowerCase();
        document.querySelectorAll('#csmDaySelector input').forEach(inp => {
            if (lowerDays.includes('mon-fri') || lowerDays.includes('weekday')) {
                if (['Mon','Tue','Wed','Thu','Fri'].includes(inp.value)) inp.checked = true;
            } else if (lowerDays.includes(inp.value.toLowerCase())) {
                inp.checked = true;
            }
        });

        const inner = schedule.split('(')[1].replace(')', '');
        const slots = inner.split(',').map(s => s.trim());
        
        if (slots[0]) {
            const times = slots[0].includes('–') ? slots[0].split('–') : slots[0].split('-');
            if (times.length >= 2) {
                document.getElementById('csmTimeStart').value = times[0].trim();
                document.getElementById('csmTimeEnd').value   = times[1].trim();
            }
        }
        if (slots[1]) {
            const times = slots[1].includes('–') ? slots[1].split('–') : slots[1].split('-');
            if (times.length >= 2) {
                document.getElementById('csmTimeStart2').value = times[0].trim();
                document.getElementById('csmTimeEnd2').value   = times[1].trim();
            }
        }
    } else if (schedule) {
        const lowerSched = schedule.toLowerCase();
        document.querySelectorAll('#csmDaySelector input').forEach(inp => {
            if (lowerSched.includes(inp.value.toLowerCase())) inp.checked = true;
        });
    }

    csmPreview();
    openModal('courseSemesterModal');
}

async function csmLoad(classId) {
    const c = document.getElementById('csmItems');
    const badge = document.getElementById('csmCountBadge');
    c.innerHTML = `
        <div style="padding:40px; text-align:center;">
            <div class="loading-spinner" style="margin: 0 auto 12px; border-top-color: var(--accent);"></div>
            <div style="font-family:var(--font-mono);font-size:10px;color:var(--muted)">RETRIEVING ACADEMIC RECORDS...</div>
        </div>
    `;
    try {
        const res = await fetch('/api/admin/classes/'+classId+'/semesters');
        const json = await res.json();
        const data = json.data || json; // Handle wrapped or unwrapped
        
        if(!data || !data.length){
            badge.textContent = '0 FOUND';
            c.innerHTML = `
                <div style="padding:40px 20px; text-align:center; background:var(--surface3)44; border-radius:16px; border:1px dashed var(--border); margin:0 4px">
                    <div style="width:48px; height:48px; border-radius:50%; background:var(--violet)10; color:var(--violet); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; opacity:0.8">
                        <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div style="font-family:var(--font-mono); font-size:11px; font-weight:800; color:var(--text); letter-spacing:.05em; margin-bottom:6px">O RECORDS ASSIGNED</div>
                    <div style="font-size:10px; color:var(--muted); font-family:var(--font-mono); max-width: 280px; margin: 0 auto; line-height: 1.5">No semester periods are currently linked to this course catalog entry.</div>
                </div>
            `;
            return;
        }

        const activeAssignment = data.find(a => a.status === 'active');
        if (activeAssignment) {
            const subTitle = document.getElementById('csmSubtitle');
            const originalName = subTitle.textContent.split(' • ')[0];
            subTitle.innerHTML = `${originalName} • <span style="color:var(--green)">ACTIVE PERIOD FOUND</span>`;
        }
        
        badge.textContent = `${data.length} ACTIVE`;
        c.innerHTML = data.map(a=>{
            const isCompleted = a.status === 'completed';
            const isActive = a.status === 'active';
            const sc = isActive ? 'var(--green)' : isCompleted ? 'var(--muted)' : 'var(--accent)';
            const bg = isActive ? 'var(--green)15' : isCompleted ? 'var(--surface3)' : 'var(--accent)15';
            
            const holDisplay = a.holiday_start 
                ? `<span style="color:var(--amber)">${a.holiday_start}</span> <span style="opacity:0.5">TO</span> <span style="color:var(--amber)">${a.holiday_end}</span>`
                : '<span style="opacity:0.5">NOT DEFINED</span>';

            const notesHtml = a.notes 
                ? `<div style="background:var(--surface3); border-radius:10px; padding:10px 14px; margin-bottom:15px; display:flex; align-items:flex-start; gap:10px; border-left: 3px solid var(--accent)66">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="var(--accent)" style="margin-top:2px; flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <div style="font-size:10px; color:var(--text2); font-family:var(--font-mono); line-height:1.5">${a.notes}</div>
                   </div>` : '';

            return `
                <div class="csm-card">
                    ${notesHtml}
                    <div class="csm-card-header">
                        <div class="csm-title-group">
                            <div class="csm-accent-bar"></div>
                            <span class="csm-title">${a.academic_year} · SEMESTER ${a.semester}</span>
                            <span class="csm-badge ${a.status === 'waiting' ? 'upcoming' : a.status}">${a.status === 'waiting' ? 'UPCOMING' : a.status.toUpperCase()}</span>
                        </div>
                        <button class="csm-remove-btn" onclick="csmDelete(${a.id})">REMOVE</button>
                    </div>

                    <div class="csm-divider"></div>

                    <div class="csm-grid">
                        <div>
                            <div class="csm-label">TERMINATION DATE</div>
                            <div class="csm-value">${a.end_date}</div>
                        </div>
                        <div>
                            <div class="csm-label">NET SESSIONS</div>
                            <div class="csm-value green">${a.active_days} DAYS</div>
                        </div>
                        <div>
                            <div class="csm-label">ACADEMIC BREAK</div>
                            <div class="csm-value ${a.holiday_start ? '' : 'muted'}">${holDisplay}</div>
                        </div>
                    </div>

                    <div class="csm-progress-section">
                        <div class="csm-progress-head">
                            <span class="csm-label" style="margin-bottom:0">COURSE PROGRESSION</span>
                            <span class="csm-value" style="font-size:13px">${a.progress}%</span>
                        </div>
                        <div class="csm-progress-track">
                            <div class="csm-progress-fill" style="width:${a.progress}%"></div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } catch(e){
        console.error(e);
        c.innerHTML=`
            <div style="padding:24px; text-align:center; color:var(--red); background:var(--red)08; border-radius:12px; border:1px solid var(--red)22">
                <div style="font-family:var(--font-mono); font-size:10px; font-weight:800">DATA SYNCHRONIZATION ERROR</div>
                <div style="font-size:9px; margin-top:4px">Failed to resolve academic assignments.</div>
            </div>
        `;
    }
}

async function csmSave() {
    if(!_csmClassId) return;
    const btn=document.getElementById('csmSaveBtn');
    const og=btn.innerHTML; btn.textContent='SAVING...'; btn.disabled=true;
    const getSelectedDays = (selectorId) => {
        const checked = Array.from(document.querySelectorAll(`#${selectorId} input:checked`));
        if (checked.length === 5 && checked.every(c => ['Mon','Tue','Wed','Thu','Fri'].includes(c.value))) return 'Mon-Fri';
        return checked.length > 0 ? checked.map(c => c.value).join('/') : 'TBD';
    };

    const payload={
        academic_year:  document.getElementById('csmYear').value.trim(),
        semester:       document.getElementById('csmSemester').value,
        start_date:     document.getElementById('csmStart').value,
        schedule_days:  getSelectedDays('csmDaySelector'),
        holiday_start:  document.getElementById('csmHoliday').value||null,
        notes:          document.getElementById('csmNotes').value.trim()||null,
        time_start:     document.getElementById('csmTimeStart').value,
        time_end:       document.getElementById('csmTimeEnd').value,
        time_start2:    document.getElementById('csmTimeStart2').value || null,
        time_end2:      document.getElementById('csmTimeEnd2').value || null,
        sessions_count: document.getElementById('csmCount').value,
    };
    if(!payload.academic_year||!payload.start_date){
        showToast('Academic year and start date are required.','error');
        btn.innerHTML=og; btn.disabled=false; return;
    }
    try {
        const res = await fetch('/api/admin/classes/'+_csmClassId+'/assign-semester',{
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csmCsrf
            },
            body:JSON.stringify(payload)
        });
        const data=await res.json();
        if(data.success){
            showToast('Semester assignment saved.','success');
            document.getElementById('csmStart').value='';
            document.getElementById('csmHoliday').value='';
            document.getElementById('csmNotes').value='';
            document.getElementById('csmPreview').style.display='none';
            await csmLoad(_csmClassId);
        } else { showToast(data.error||data.message||'Failed.','error'); }
    } catch(e){ showToast('Network error.','error'); }
    btn.innerHTML=og; btn.disabled=false;
}

async function csmDelete(id) {
    if(!confirm('Remove this semester assignment?')) return;
    try {
        const res=await fetch('/api/admin/semesters/'+id,{
            method:'DELETE',headers:{'Accept':'application/json','X-CSRF-TOKEN':csmCsrf}
        });
        const data=await res.json();
        if(data.success){ showToast('Removed.','success'); await csmLoad(_csmClassId); }
        else showToast('Failed.','error');
    } catch(e){ showToast('Network error.','error'); }
}

// ── SESSION HISTORY FEATURE ─────────────────────────
async function openSessionsModal(classId, className) {
    const container = document.getElementById('sessionsListContainer');
    document.getElementById('sessionsModalSubtitle').textContent = className;
    container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--muted)">Retrieving timeline records...</div>';
    openModal('sessionsModal');

    try {
        const res = await fetch(`/api/admin/classes/${classId}/sessions`);
        if (!res.ok) throw new Error('API request failed');
        const sessions = await res.json();
        
        if (sessions.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:60px; color:var(--muted); font-family:var(--font-mono); font-size:11px">NO RECORDED SESSIONS FOUND</div>';
            return;
        }

        container.innerHTML = sessions.map(s => {
            const startStr = (s.start_time || '').replace(' ', 'T');
            const d = new Date(startStr);
            
            const dateStr = isNaN(d.getTime()) ? 'Invalid Date' : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const timeStr = isNaN(d.getTime()) ? '--:--' : d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
            
            const isCompleted = s.status === 'completed';
            const isActive = s.status === 'active';
            const statusClr = isCompleted ? 'var(--muted)' : isActive ? 'var(--green)' : 'var(--amber)';
            const statusBg = isCompleted ? 'var(--surface3)' : isActive ? 'var(--green)15' : 'var(--amber)15';
            
            const pct = s.total_students_count > 0 ? Math.round((s.presence_count / s.total_students_count) * 100) : 0;

            return `
                <div style="display:flex; align-items:center; justify-content:space-between; padding:16px; border:1px solid var(--border); border-radius:16px; margin-bottom:12px; background:var(--surface2); transition:all 0.2s" onmouseover="this.style.borderColor='var(--amber)44'; this.style.transform='translateX(4px)'" onmouseout="this.style.borderColor='var(--border)'; this.style.transform='none'">
                    <div style="display:flex; align-items:center; gap:16px">
                        <div style="width:48px; height:48px; border-radius:12px; background:var(--surface3); display:flex; flex-direction:column; align-items:center; justify-content:center; border:1px solid var(--border)">
                            <div style="font-size:9px; font-weight:800; color:var(--muted); font-family:var(--font-mono)">${isNaN(d.getTime()) ? '???' : d.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()}</div>
                            <div style="font-size:16px; font-weight:800; color:var(--text); line-height:1">${isNaN(d.getTime()) ? '--' : d.getDate()}</div>
                        </div>
                        <div>
                            <div style="font-family:var(--font-mono); font-size:12px; font-weight:700; color:var(--text)">${dateStr} @ ${timeStr}</div>
                            <div style="display:flex; align-items:center; gap:8px; margin-top:4px">
                                <span style="font-family:var(--font-mono); font-size:8px; padding:2px 8px; border-radius:10px; background:${statusBg}; color:${statusClr}; font-weight:800; text-transform:uppercase">${s.status}</span>
                                <span style="font-size:10px; color:var(--muted)">•</span>
                                <span style="font-family:var(--font-mono); font-size:10px; color:var(--text2); font-weight:700">${s.presence_count} / ${s.total_students_count} <span style="font-weight:400; font-size:9px; color:var(--muted)">ARRIVED</span></span>
                            </div>
                        </div>
                    </div>
                    <button class="action-btn" onclick="openSessionDetail(${s.id})" style="background:var(--surface3); border:1px solid var(--border); border-radius:10px; padding:8px 16px; font-family:var(--font-mono); font-size:10px; font-weight:800; cursor:pointer; color:var(--text2)">
                        DETAILS
                    </button>
                </div>
            `;
        }).join('');
    } catch (e) {
        container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--red)">Failed to load session timeline.</div>';
    }
}

async function openSessionDetail(sessionId) {
    const list = document.getElementById('sdmList');
    const stats = document.getElementById('sdmStats');
    list.innerHTML = '<div style="text-align:center; padding:40px; color:var(--muted)">Fetching arrival logs...</div>';
    stats.innerHTML = '';
    openModal('sessionDetailModal');

    try {
        const res = await fetch(`/api/admin/sessions/${sessionId}/attendance`);
        const data = await res.json();
        
        document.getElementById('sdmTitle').textContent = data.session_name;
        
        stats.innerHTML = `
            <div>
                <div style="font-family:var(--font-mono); font-size:8px; color:var(--muted); text-transform:uppercase; margin-bottom:2px">PRESENCE RATIO</div>
                <div style="font-family:var(--font-display); font-size:18px; font-weight:800; color:var(--accent)">${data.present_count} / ${data.total_count}</div>
            </div>
            <div style="width:1px; background:var(--border)"></div>
            <div>
                <div style="font-family:var(--font-mono); font-size:8px; color:var(--muted); text-transform:uppercase; margin-bottom:2px">EFFICIENCY</div>
                <div style="font-family:var(--font-display); font-size:18px; font-weight:800; color:var(--green)">${data.total_count > 0 ? Math.round((data.present_count/data.total_count)*100) : 0}%</div>
            </div>
        `;

        list.innerHTML = data.data.map(row => {
            const isPresent = row.status === 'PRESENT' || row.status === 'LATE';
            const statusClr = isPresent ? (row.status === 'LATE' ? 'var(--amber)' : 'var(--green)') : 'var(--red)';
            
            return `
                <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border)44; cursor:pointer" onmouseover="this.style.background='var(--surface3)'; this.querySelector('.s-name').style.color='var(--accent)'" onmouseout="this.style.background='transparent'; this.querySelector('.s-name').style.color='var(--text)'" onclick="openStudentRecordModal(${row.id})">
                    <div style="display:flex; align-items:center; gap:12px">
                        <div style="width:32px; height:32px; border-radius:50%; background:var(--surface3); color:var(--muted); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:11px">
                            ${row.name.charAt(0)}
                        </div>
                        <div>
                            <div class="s-name" style="font-size:12px; font-weight:700; color:var(--text); transition:color 0.2s">${row.name}</div>
                            <div style="font-family:var(--font-mono); font-size:9px; color:var(--muted)">${row.student_code}</div>
                        </div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-family:var(--font-mono); font-size:9px; font-weight:800; color:${statusClr}">${row.status}</div>
                        <div style="font-family:var(--font-mono); font-size:9px; color:var(--muted)">${row.check_in_time}</div>
                    </div>
                </div>
            `;
        }).join('');
    } catch (e) {
        list.innerHTML = '<div style="text-align:center; padding:40px; color:var(--red)">Error retrieving session details.</div>';
    }
}

async function openStudentRecordModal(studentId) {
    const hist = document.getElementById('smHistory');
    hist.innerHTML = '<div style="text-align:center; padding:30px; font-size:11px; color:var(--muted)">RETRIEVING PROFILE...</div>';
    openModal('studentDetailModal');

    try {
        const res = await fetch(`/api/admin/students/${studentId}/attendance`);
        const data = await res.json();
        const s = data.student;

        // Populate Header
        document.getElementById('smName').textContent = s.name;
        document.getElementById('smCode').textContent = s.student_code;
        document.getElementById('smInitials').textContent = s.name.split(' ').map(n=>n[0]).join('').substring(0,2);
        
        // Populate Analytics
        document.getElementById('smYear').textContent = s.year_level + ' Year';
        document.getElementById('smMajor').textContent = s.major;
        document.getElementById('smStatusBadge').textContent = s.status.toUpperCase() + ' STUDENT';
        document.getElementById('smRate').textContent = s.attendance_rate + '%';
        document.getElementById('smJoinedDate').textContent = 'JOINED AT ' + s.joined_at;

        // Populate History
        if (data.history.length === 0) {
            hist.innerHTML = '<div style="text-align:center; padding:20px; font-size:10px; color:var(--muted); font-family:var(--font-mono)">NO RECENT RECORDS FOUND</div>';
            return;
        }

        hist.innerHTML = data.history.map(row => {
            const isPresent = row.status === 'PRESENT' || row.status === 'LATE';
            const color = isPresent ? 'var(--green)' : 'var(--red)';

            return `
                <div style="display:flex; align-items:center; justify-content:space-between; background:var(--surface); padding:10px 14px; border-radius:12px; border:1px solid var(--border)">
                    <div style="display:flex; align-items:center; gap:10px">
                        <div style="width:8px; height:8px; border-radius:50%; background:${color}"></div>
                        <div>
                            <div style="font-size:11px; font-weight:700; color:var(--text)">${row.subject}</div>
                            <div style="font-size:9px; color:var(--muted)">${row.date}</div>
                        </div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-family:var(--font-mono); font-size:9px; font-weight:800; color:${color}">${row.status}</div>
                        <div style="font-family:var(--font-mono); font-size:8px; color:var(--muted2)">${row.time}</div>
                    </div>
                </div>
            `;
        }).join('');

    } catch (e) {
        hist.innerHTML = '<div style="text-align:center; padding:30px; color:var(--red)">Failed to load student record.</div>';
    }
}


</script>
@endsection
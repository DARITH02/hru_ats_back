@extends('layouts.app')

@section('content')

{{-- ════════════════════════════════════════════
     CREATE PERMISSION MODAL
════════════════════════════════════════════ --}}
<div id="permissionModal" class="modal-overlay">
    <div class="modal-box" style="max-width:520px;">
        <div class="modal-head">
            <span class="modal-title">Assign Student Permission</span>
            <button onclick="closeModal('permissionModal')" class="modal-close">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form action="{{ route('admin.permissions.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Student <span class="req">*</span></label>
                    <select name="student_id" class="form-input" required>
                        <option value="">Select a student...</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}">{{ $s->user->name }} ({{ $s->student_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label">Start Date <span class="req">*</span></label>
                        <input name="start_date" class="form-input" type="date" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date <span class="req">*</span></label>
                        <input name="end_date" class="form-input" type="date" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Permission Type</label>
                    <select name="type" class="form-input">
                        <option value="sick">Sick Leave</option>
                        <option value="event">School Event</option>
                        <option value="personal">Personal Reason</option>
                        <option value="official">Official Duty</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Reason / Notes <span class="req">*</span></label>
                    <textarea name="reason" class="form-input" rows="3" required placeholder="Briefly explain the reason..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal('permissionModal')" class="btn-secondary">CANCEL</button>
                <button type="submit" class="btn-primary" style="gap:8px">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    ASSIGN PERMISSION
                </button>
            </div>
        </form>
    </div>
</div>

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <span>MANAGEMENT</span>
            <span class="breadcrumb-sep">/</span>
            <span class="breadcrumb-current">PERMISSIONS</span>
        </div>
        <h1 class="page-title">Student Permissions</h1>
        <p class="page-subtitle">EXCUSED ABSENCES & OFFICIAL LEAVES</p>
    </div>
    <button onclick="openModal('permissionModal')" class="btn-primary" style="gap:7px">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        ASSIGN PERMISSION
    </button>
</div>

@if(session('success'))
<div style="background:var(--green)11; color:var(--green); padding:12px 16px; border-radius:10px; border:1px solid var(--green)33; margin-bottom:20px; font-family:var(--font-mono); font-size:11px; display:flex; align-items:center; gap:10px">
    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif

<div class="panel">
    <div class="catalog-toolbar">
        <div style="display:flex;align-items:center;gap:7px">
            <div style="width:7px;height:7px;border-radius:50%;background:var(--amber);box-shadow:0 0 8px var(--amber);"></div>
            <span style="font-family:var(--font-mono);font-size:10px;letter-spacing:.12em;color:var(--muted2)">ACTIVE PERMISSIONS</span>
        </div>
        <div class="search-wrap">
            <form action="" method="GET" style="display:flex;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:14px; position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted)">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input class="search-input" type="text" name="search" placeholder="Search student..." value="{{ request('search') }}" style="padding-left:35px">
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="att-table">
            <thead>
                <tr>
                    <th>STUDENT</th>
                    <th>TYPE</th>
                    <th>DURATION</th>
                    <th>REASON</th>
                    <th style="text-align:right">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permissions as $p)
                <tr class="fade-up">
                    <td>
                        <div class="subject-cell">
                            <div class="subject-avatar" style="background:var(--accent)22; color:var(--accent); width:36px; height:36px; font-size:10px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;">
                                {{ strtoupper(substr($p->student->user->name, 0, 2)) }}
                            </div>
                            <div>
                                <div class="subject-name" style="font-weight:700; color:var(--text)">{{ $p->student->user->name }}</div>
                                <div class="subject-id" style="font-family:var(--font-mono); font-size:10px; color:var(--muted)">{{ $p->student->student_code }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="status-tag" style="background:var(--accent)11; color:var(--accent); border:1px solid var(--accent)33; font-size:9px; font-weight:800; font-family:var(--font-mono)">
                            {{ strtoupper($p->type) }}
                        </span>
                    </td>
                    <td>
                        <div style="font-size:12px; font-weight:600; color:var(--text2)">
                            {{ \Carbon\Carbon::parse($p->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($p->end_date)->format('M d, Y') }}
                        </div>
                        <div style="font-size:9px; color:var(--muted); font-family:var(--font-mono); margin-top:2px">
                            @php
                                $days = \Carbon\Carbon::parse($p->start_date)->diffInDays(\Carbon\Carbon::parse($p->end_date)) + 1;
                            @endphp
                            TOTAL: {{ $days }} DAYS
                        </div>
                    </td>
                    <td>
                        <div style="font-size:11px; color:var(--muted2); max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="{{ $p->reason }}">
                            {{ $p->reason }}
                        </div>
                    </td>
                    <td style="text-align:right">
                        <form action="{{ route('admin.permissions.destroy', $p->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove this permission?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="action-btn btn-del" title="Revoke Permission">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state" style="padding:60px 0">
                            <div class="empty-icon" style="background:var(--surface3); width:50px; height:50px; border-radius:15px; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; color:var(--muted)">
                                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="empty-title" style="font-weight:700; color:var(--text2)">No active permissions</div>
                            <div class="empty-desc" style="font-size:11px; color:var(--muted)">Assigned excused absences will appear here.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($permissions instanceof \Illuminate\Pagination\LengthAwarePaginator && $permissions->hasPages())
    <div style="padding:15px; border-top:1px solid var(--border)">
        {{ $permissions->links() }}
    </div>
    @endif
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>

@endsection

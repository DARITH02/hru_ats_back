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
            <div style="font-family:var(--font-display);font-size:16px;font-weight:700;color:var(--text);margin-bottom:8px">Delete Department?</div>
            <div id="deleteSubtitle" style="font-family:var(--font-mono);font-size:10px;color:var(--muted);letter-spacing:.06em;line-height:1.7">
                All associated subjects and faculty assignments will be unlinked.
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal('deleteModal')" class="btn-secondary">CANCEL</button>
            <button id="confirmDeleteBtn"
                style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--radius-md);border:none;background:linear-gradient(135deg,var(--red),#f87a7a);color:#fff;font-family:var(--font-mono);font-size:10px;letter-spacing:.1em;font-weight:600;cursor:pointer;transition:all .2s;box-shadow:0 4px 14px rgba(242,87,87,.25)">
                CONFIRM DELETE
            </button>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════
     CREATE / EDIT MODAL
     ════════════════════════════════════════════ --}}
<div id="deptModal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-head">
            <div style="display:flex;align-items:center;gap:10px">
                <div id="modalAvatarPreview"
                    style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--violet));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;letter-spacing:.04em">
                    ?
                </div>
                <span id="deptModalTitle" class="modal-title">New Department</span>
            </div>
            <button onclick="closeModal('deptModal')" class="modal-close">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="deptForm">
            @csrf
            <input type="hidden" id="modalDeptId">
            <input type="hidden" id="modalMode" value="create">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:16px">
                <div class="form-group">
                    <label class="form-label">Department Name <span class="req">*</span></label>
                    <input id="modalName" class="form-input" type="text" required
                        placeholder="e.g. Faculty of Engineering"
                        oninput="updateAvatarPreview(this.value)">
                </div>
                <div class="form-group">
                    <label class="form-label">Dept Code <span class="req">*</span></label>
                    <input id="modalCode" class="form-input" type="text" required placeholder="e.g. ENG or SCI">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('deptModal')" class="btn-secondary">CANCEL</button>
                <button type="submit" id="modalSubmitBtn" class="btn-primary">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span id="modalSubmitLabel">SAVE DEPARTMENT</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════
     PAGE HEADER
     ════════════════════════════════════════════ --}}
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <span>MANAGEMENT</span>
            <span class="breadcrumb-sep">/</span>
            <span class="breadcrumb-current">DEPARTMENTS</span>
        </div>
        <h1 class="page-title">Organization Registry</h1>
        <p class="page-subtitle">FACULTIES, DEPARTMENTS & ACADEMIC UNITS</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <button onclick="window.open('{{ route('admin.export.departments') }}', '_blank')" class="btn-secondary" style="gap:7px; background:var(--surface3); border:1px solid var(--border)">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            EXPORT ALL
        </button>
        <button onclick="openCreateModal()" class="btn-primary" style="gap:7px">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            ADD DEPARTMENT
        </button>
    </div>
</div>

{{-- ════════════════════════════════════════════
     STATS
     ════════════════════════════════════════════ --}}
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-glow"></div>
        <div class="stat-label">TOTAL DEPARTMENTS</div>
        <div class="stat-value">{{ $departments->total() }}</div>
        <span class="stat-pill">Organization</span>
    </div>
    <div class="stat-card green">
        <div class="stat-glow"></div>
        <div class="stat-label">TOTAL FACULTY</div>
        <div class="stat-value">{{ $departments->sum('teachers_count') }}</div>
        <span class="stat-pill">Personnel</span>
    </div>
    <div class="stat-card violet">
        <div class="stat-glow"></div>
        <div class="stat-label">TOTAL MODULES</div>
        <div class="stat-value">{{ $departments->sum('subjects_count') }}</div>
        <span class="stat-pill">Curriculum</span>
    </div>
</div>

{{-- ════════════════════════════════════════════
     TABLE PANEL
     ════════════════════════════════════════════ --}}
<div class="panel">
    <div class="catalog-toolbar">
        <div style="display:flex;align-items:center;gap:7px">
            <div style="width:7px;height:7px;border-radius:50%;background:var(--accent);box-shadow:0 0 8px var(--accent);"></div>
            <span style="font-family:var(--font-mono);font-size:10px;letter-spacing:.12em;color:var(--muted2)">ORGANIZATION CHART</span>
        </div>
        
        <div style="display:flex; align-items:center; gap:12px;">
            <div class="search-wrap" style="width: 250px; height: 36px; background: var(--surface3); border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; padding: 0 12px;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:var(--muted2)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                <input id="searchInput" value="{{ request('search') }}" type="text" placeholder="Search departments..." onkeyup="filterDepts(event)" style="border: none; background: transparent; color: var(--text); font-size: 11px; padding-left: 10px; width: 100%; outline: none;">
            </div>
            <div class="toolbar-count" style="background:var(--surface2); padding: 0 14px; height:36px; display:flex; align-items:center; border-radius:10px; border:1px solid var(--border)">
                <span style="font-family:var(--font-mono); font-size:9px; font-weight:800; color:var(--accent)">{{ $departments->total() }}</span>
                <span style="font-family:var(--font-mono); font-size:8px; color:var(--muted2); margin-left:6px; letter-spacing:.05em">UNITS</span>
            </div>
        </div>
    </div>

    <table class="att-table">
        <thead>
            <tr>
                <th>DEPT IDENTITY</th>
                <th>CODE</th>
                <th>FACULTY COUNT</th>
                <th>MODULES</th>
                <th style="text-align:right">ACTIONS</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            @foreach($departments as $dept)
            @php
                $colors = ['#4f8ef7', '#a78bfa', '#f0a732', '#34d399', '#f25757'];
                $clr = $colors[$loop->index % count($colors)];
                $init = strtoupper(substr($dept->name, 0, 2));
            @endphp
            <tr data-id="{{ $dept->id }}" data-name="{{ strtolower($dept->name) }}" data-code="{{ $dept->code }}" class="fade-up">
                <td>
                    <div class="subject-cell">
                        <div class="subject-avatar" style="background:{{ $clr }}22;color:{{ $clr }};border:1px solid {{ $clr }}33">
                            {{ $init }}
                        </div>
                        <div>
                            <div class="subject-name">{{ $dept->name }}</div>
                            <div class="subject-id">ID: #{{ str_pad($dept->id, 3, '0', STR_PAD_LEFT) }}</div>
                        </div>
                    </div>
                </td>
                <td><span style="font-family:var(--font-mono);font-size:11px;color:var(--accent)">{{ $dept->code }}</span></td>
                <td><span style="font-family:var(--font-display);font-size:15px;font-weight:700;color:var(--text2)">{{ $dept->teachers_count }}</span></td>
                <td><span style="font-family:var(--font-mono);font-size:11px;color:var(--muted)">{{ $dept->subjects_count }} MODULES</span></td>
                <td style="text-align:right">
                    <button class="action-btn btn-edit" title="Edit" onclick="openEditModal(this.closest('tr'))">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    @if(Auth::user()->isSuperAdmin())
                    <button class="action-btn btn-del" title="Delete" onclick="openDeleteModal({{ $dept->id }}, '{{ addslashes($dept->name) }}')">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="padding:12px 18px;border-top:1px solid var(--border)">
        {{ $departments->links('vendor.pagination.academy') }}
    </div>
</div>

<script>
let filterTimeout = null;
function filterDepts(e) {
    if (e && e.type === 'keyup' && e.key !== 'Enter') {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => filterDepts(), 800);
        return;
    }
    const q = document.getElementById('searchInput').value;
    const params = new URLSearchParams(window.location.search);
    if (q) params.set('search', q); else params.delete('search');
    params.set('page', 1);
    window.location.href = `${window.location.pathname}?${params.toString()}`;
}

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.className = `toast show toast-${type}`;
    document.getElementById('toastMsg').textContent = msg;
    setTimeout(() => t.classList.remove('show'), 3000);
}

function updateAvatarPreview(val) {
    const init = val.slice(0, 2).toUpperCase() || '?';
    document.getElementById('modalAvatarPreview').textContent = init;
}

function openCreateModal() {
    document.getElementById('deptModalTitle').textContent = 'New Department';
    document.getElementById('modalMode').value = 'create';
    document.getElementById('deptForm').reset();
    document.getElementById('modalAvatarPreview').textContent = '?';
    openModal('deptModal');
}

function openEditModal(row) {
    document.getElementById('deptModalTitle').textContent = 'Edit Department';
    document.getElementById('modalMode').value = 'edit';
    document.getElementById('modalDeptId').value = row.dataset.id;
    document.getElementById('modalName').value = row.querySelector('.subject-name').textContent;
    document.getElementById('modalCode').value = row.dataset.code;
    updateAvatarPreview(document.getElementById('modalName').value);
    openModal('deptModal');
}

let pendingDeleteId = null;
function openDeleteModal(id, name) {
    pendingDeleteId = id;
    document.getElementById('deleteSubtitle').innerHTML = `Deleting <strong style="color:var(--text2)">${name}</strong> will unlink all associated faculty and subjects.`;
    openModal('deleteModal');
}

document.getElementById('confirmDeleteBtn').onclick = async () => {
    try {
        const res = await fetch(`/api/admin/departments/${pendingDeleteId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        if (res.ok) {
            showToast('Department removed');
            location.reload();
        }
    } catch(e) { showToast('Network Error', 'error'); }
};

document.getElementById('deptForm').onsubmit = async e => {
    e.preventDefault();
    const mode = document.getElementById('modalMode').value;
    const id = document.getElementById('modalDeptId').value;
    const payload = {
        name: document.getElementById('modalName').value,
        code: document.getElementById('modalCode').value,
    };

    try {
        const url = mode === 'create' ? '/api/admin/departments' : `/api/admin/departments/${id}`;
        const res = await fetch(url, {
            method: mode === 'create' ? 'POST' : 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(payload)
        });
        if (res.ok) {
            showToast(mode === 'create' ? 'Department added' : 'Department updated');
            location.reload();
        }
    } catch(e) { showToast('Network Error', 'error'); }
};
</script>
@endsection

@extends('layouts.app')
@section('title', 'HR')
@section('content')
<h1 style="color:var(--fg);font-size:24px;font-weight:700;margin-bottom:24px">HR</h1>

@if(session('success'))
<div class="alert alert-green">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-red">{{ $errors->first() }}</div>
@endif

<div data-tabs>
<div class="tabs">
    <button class="tab-btn active" data-tab="form">Űrlap szerkesztő</button>
    <button class="tab-btn" data-tab="applications">Jelentkezések</button>
    <button class="tab-btn" data-tab="scheduling">Ütemezés</button>
    <button class="tab-btn" data-tab="notifications">Értesítések</button>
</div>

{{-- ════ FORM BUILDER ════ --}}
<div class="tab-panel active" data-panel="form">
<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
    <div style="color:var(--fg);font-size:15px;font-weight:600;flex:1">Jelentkezési űrlap</div>
    <button class="btn btn-primary" style="white-space:nowrap" onclick="openNewFormField()">+ Új mező</button>
</div>
<div class="card" style="padding:0;overflow:hidden">
    <table class="table">
        <thead><tr><th>Címke</th><th>Típus</th><th>Kötelező</th><th>Sorrend</th><th style="text-align:right"></th></tr></thead>
        <tbody>
        @forelse($formFields as $field)
        <tr>
            <td style="color:var(--fg)">{{ $field->label }}</td>
            <td>{{ ['text'=>'Szöveg','textarea'=>'Hosszú szöveg','select'=>'Legördülő','checkbox'=>'Jelölőnégyzet'][$field->type] ?? $field->type }}</td>
            <td>{{ $field->is_required ? 'Igen' : 'Nem' }}</td>
            <td>{{ $field->sort_order }}</td>
            <td style="text-align:right;white-space:nowrap">
                <button type="button" class="btn btn-ghost" style="font-size:11px;padding:4px 8px" onclick='openEditFormField(@json($field))'>Szerkesztés</button>
                <form method="POST" action="/admin/form-fields/{{ $field->id }}" style="display:inline-block" onsubmit="return confirm('Törlöd ezt a mezőt? A már beérkezett válaszok megmaradnak, de nem lesznek szerkeszthetők.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 8px">Törlés</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" style="padding:24px;text-align:center;color:var(--fg-subtle)">Nincs mező — a jelentkezési űrlap üres lesz.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</div>

{{-- ════ APPLICATIONS ════ --}}
<div class="tab-panel" data-panel="applications">
<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);color:var(--fg-subtle);font-size:13px">
        {{ $pendingApplications->count() }} elbírálandó jelentkezés
    </div>
    @forelse($pendingApplications as $jr)
    <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
            <span style="color:var(--fg);font-weight:600;font-size:14px">{{ $jr->user->in_game_name ?? $jr->user->name }}</span>
            <span style="color:var(--fg-subtle);font-size:12px">{{ '@'.$jr->user->username }}</span>
            <span style="color:var(--fg-subtle);font-size:11px;margin-left:auto;white-space:nowrap">{{ $jr->created_at->diffForHumans() }}</span>
        </div>
        <div style="display:grid;gap:8px;margin-bottom:12px">
            @foreach($formFields as $field)
            @php $answer = $jr->answers[$field->id] ?? null; @endphp
            <div>
                <div style="color:var(--fg-subtle);font-size:11px;text-transform:uppercase;letter-spacing:.03em">{{ $field->label }}</div>
                <div style="color:var(--fg-muted);font-size:13px;line-height:1.5;white-space:pre-wrap">{{ is_bool($answer) ? ($answer ? 'Igen' : 'Nem') : ($answer ?: '—') }}</div>
            </div>
            @endforeach
        </div>
        <div style="display:flex;gap:6px">
            <form method="POST" action="/admin/join-requests/{{ $jr->id }}/approve" onsubmit="return confirm('Elfogadod {{ addslashes($jr->user->in_game_name ?? $jr->user->name) }} jelentkezését? A jelentkező ezután egy interjú-időpontot fog javasolni.')">
                @csrf
                <button type="submit" class="btn btn-primary" style="font-size:11px;padding:4px 10px">Elfogad</button>
            </form>
            <button type="button" class="btn btn-ghost" style="font-size:11px;padding:4px 10px" onclick='openNeedsChanges({{ $jr->id }}, @json($formFields->pluck("label","id")))'>Módosítás kérése</button>
            <form method="POST" action="/admin/join-requests/{{ $jr->id }}/reject" onsubmit="return confirm('Elutasítod {{ addslashes($jr->user->in_game_name ?? $jr->user->name) }} jelentkezését?')">
                @csrf
                <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 10px">Elutasít</button>
            </form>
        </div>
    </div>
    @empty
    <p style="color:var(--fg-subtle);font-size:14px;padding:20px">Nincs elbírálandó jelentkezés.</p>
    @endforelse
</div>

<div class="section-hdr" style="margin:24px 0 14px">
    <h2>Korábbi jelentkezések</h2>
    <div class="section-hdr-line"></div>
</div>
<div class="card" style="padding:0;overflow:hidden">
    @forelse($reviewedApplications as $jr)
    @php
        $statusLabel = ['REJECTED' => 'Elutasítva', 'MEMBER' => 'Tag lett', 'APPROVED' => 'Elfogadva — időpontra vár'][$jr->status] ?? $jr->status;
        $statusBadge = ['REJECTED' => 'badge-red', 'MEMBER' => 'badge-green', 'APPROVED' => 'badge-yellow'][$jr->status] ?? 'badge-gray';
        $answerPairs = $formFields->map(function ($f) use ($jr) {
            $a = $jr->answers[$f->id] ?? null;
            return ['label' => $f->label, 'value' => is_bool($a) ? ($a ? 'Igen' : 'Nem') : ($a ?: '—')];
        })->values();
        $reviewedAppData = [
            'name'        => $jr->user->in_game_name ?? $jr->user->name,
            'username'    => $jr->user->username,
            'statusLabel' => $statusLabel,
            'reviewer'    => $jr->reviewer ? ($jr->reviewer->in_game_name ?? $jr->reviewer->name) : null,
            'reviewedAt'  => ($jr->reviewed_at ?? $jr->created_at)->format('Y. m. d. H:i'),
            'reviewNote'  => $jr->review_note,
            'answers'     => $answerPairs,
        ];
    @endphp
    <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;cursor:pointer" onclick='openReviewedApp(@json($reviewedAppData))'>
        <div style="flex:1;min-width:0">
            <span style="color:var(--fg);font-weight:600;font-size:13px">{{ $jr->user->in_game_name ?? $jr->user->name }}</span>
            <span style="color:var(--fg-subtle);font-size:12px;margin-left:6px">{{ '@'.$jr->user->username }}</span>
        </div>
        <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
        <span style="color:var(--fg-subtle);font-size:11px;white-space:nowrap">
            @if($jr->reviewer) {{ $jr->reviewer->in_game_name ?? $jr->reviewer->name }} · @endif
            {{ ($jr->reviewed_at ?? $jr->created_at)->diffForHumans() }}
        </span>
    </div>
    @empty
    <p style="color:var(--fg-subtle);font-size:14px;padding:20px">Nincs korábbi jelentkezés.</p>
    @endforelse
</div>
</div>

{{-- ════ SCHEDULING ════ --}}
<div class="tab-panel" data-panel="scheduling">
<div class="section-hdr" style="margin-bottom:14px">
    <h2>Időpont-egyeztetésre vár</h2>
    <div class="section-hdr-line"></div>
</div>
@forelse($awaitingSlot as $app)
<div class="card" style="margin-bottom:12px">
    <div style="color:var(--fg);font-weight:600;font-size:14px;margin-bottom:10px">
        {{ $app->user->in_game_name ?? $app->user->name }}
        <span style="color:var(--fg-subtle);font-weight:400;font-size:12px">{{ '@'.$app->user->username }}</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
        @foreach($app->proposed_slots as $i => $slot)
        <div style="display:flex;align-items:center;gap:12px;padding:8px 12px;background:var(--surface-2);border-radius:var(--radius)">
            <span style="color:var(--fg);font-size:13px;flex:1">{{ \Illuminate\Support\Carbon::parse($slot['date'])->format('Y. m. d.') }}, {{ substr($slot['start_time'],0,5) }}–{{ substr($slot['end_time'],0,5) }}</span>
            <form method="POST" action="/hr/utemezes/{{ $app->id }}/confirm">
                @csrf
                <input type="hidden" name="slot_index" value="{{ $i }}">
                <button type="submit" class="btn btn-primary" style="font-size:11px;padding:4px 10px" onclick="return confirm('Megerősíted ezt az időpontot?')">Megerősít</button>
            </form>
        </div>
        @endforeach
    </div>
</div>
@empty
<p style="color:var(--fg-subtle);font-size:14px;margin-bottom:24px">Nincs egyeztetésre váró jelentkező.</p>
@endforelse

<div class="section-hdr" style="margin:24px 0 14px">
    <h2>Interjú után döntésre vár</h2>
    <div class="section-hdr-line"></div>
</div>
@forelse($awaitingDecision as $app)
<div class="card" style="margin-bottom:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <div style="flex:1">
            <div style="color:var(--fg);font-weight:600;font-size:14px">
                {{ $app->user->in_game_name ?? $app->user->name }}
                <span style="color:var(--fg-subtle);font-weight:400;font-size:12px">{{ '@'.$app->user->username }}</span>
            </div>
            <div style="color:var(--fg-subtle);font-size:12px;margin-top:2px">
                Interjú: {{ \Illuminate\Support\Carbon::parse($app->confirmed_date)->format('Y. m. d.') }}, {{ substr($app->confirmed_start_time,0,5) }}–{{ substr($app->confirmed_end_time,0,5) }}
            </div>
        </div>
        <form method="POST" action="/hr/utemezes/{{ $app->id }}/grant-access" onsubmit="return confirm('Hozzáférést adsz {{ addslashes($app->user->in_game_name ?? $app->user->name) }} részére?')">
            @csrf
            <button type="submit" class="btn btn-primary" style="font-size:12px;padding:4px 10px">Hozzáférés megadása</button>
        </form>
        <form method="POST" action="/hr/utemezes/{{ $app->id }}/reject" onsubmit="return confirm('Elutasítod {{ addslashes($app->user->in_game_name ?? $app->user->name) }} jelentkezését?')">
            @csrf
            <button type="submit" class="btn btn-danger" style="font-size:12px;padding:4px 10px">Elutasít</button>
        </form>
    </div>
</div>
@empty
<p style="color:var(--fg-subtle);font-size:14px">Nincs döntésre váró jelentkező.</p>
@endforelse
</div>

{{-- ════ NOTIFICATIONS ARCHIVE ════ --}}
<div class="tab-panel" data-panel="notifications">
<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);color:var(--fg-subtle);font-size:13px">
        {{ $notifications->count() }} db (minden felhasználó, teljes előzmény)
    </div>
    @forelse($notifications as $n)
    <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:flex-start">
        <span class="bell-notif-dot {{ $n->read_at ? 'read' : 'unread' }}" style="margin-top:5px"></span>
        <div style="flex:1;min-width:0">
            <div style="color:var(--fg-subtle);font-size:11px;margin-bottom:2px">{{ $n->user->in_game_name ?? $n->user->name ?? 'Törölt felhasználó' }} · {{ $n->created_at->diffForHumans() }}</div>
            <div style="color:var(--fg-muted);font-size:13px;line-height:1.5;white-space:pre-wrap">{{ $n->message }}</div>
        </div>
    </div>
    @empty
    <p style="color:var(--fg-subtle);font-size:14px;padding:20px">Nincs értesítés.</p>
    @endforelse
</div>
</div>
</div>

{{-- Form field modals --}}
<div class="modal-backdrop" id="newFormFieldModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('newFormFieldModal')">&times;</button>
        <div class="modal-title">Új mező</div>
        <form method="POST" action="/admin/form-fields">
            @csrf
            <div style="margin-bottom:12px"><label class="form-label">Címke</label><input type="text" name="label" class="form-input" required></div>
            <div style="margin-bottom:12px">
                <label class="form-label">Típus</label>
                <select name="type" class="form-select" id="newFieldType" onchange="toggleFieldOptions('new')">
                    <option value="text">Szöveg (rövid)</option>
                    <option value="textarea">Hosszú szöveg</option>
                    <option value="select">Legördülő</option>
                    <option value="checkbox">Jelölőnégyzet</option>
                </select>
            </div>
            <div style="margin-bottom:12px;display:none" id="newFieldOptionsWrap">
                <label class="form-label">Választható értékek (soronként egy)</label>
                <textarea name="options" class="form-textarea" rows="4"></textarea>
            </div>
            <div style="margin-bottom:12px;display:flex;align-items:center;gap:6px"><input type="checkbox" name="is_required" value="1" id="newFieldRequired" checked><label for="newFieldRequired" style="color:var(--fg-subtle);font-size:13px">Kötelező</label></div>
            <div style="margin-bottom:16px"><label class="form-label">Sorrend</label><input type="number" name="sort_order" class="form-input" value="0"></div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Létrehozás</button>
                <button type="button" onclick="closeModal('newFormFieldModal')" class="btn btn-ghost">Mégse</button>
            </div>
        </form>
    </div>
</div>
<div class="modal-backdrop" id="editFormFieldModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('editFormFieldModal')">&times;</button>
        <div class="modal-title">Mező szerkesztése</div>
        <form method="POST" id="editFormFieldForm">
            @csrf @method('PUT')
            <div style="margin-bottom:12px"><label class="form-label">Címke</label><input type="text" name="label" id="editFieldLabel" class="form-input" required></div>
            <div style="margin-bottom:12px">
                <label class="form-label">Típus</label>
                <select name="type" class="form-select" id="editFieldType" onchange="toggleFieldOptions('edit')">
                    <option value="text">Szöveg (rövid)</option>
                    <option value="textarea">Hosszú szöveg</option>
                    <option value="select">Legördülő</option>
                    <option value="checkbox">Jelölőnégyzet</option>
                </select>
            </div>
            <div style="margin-bottom:12px;display:none" id="editFieldOptionsWrap">
                <label class="form-label">Választható értékek (soronként egy)</label>
                <textarea name="options" id="editFieldOptions" class="form-textarea" rows="4"></textarea>
            </div>
            <div style="margin-bottom:12px;display:flex;align-items:center;gap:6px"><input type="checkbox" name="is_required" value="1" id="editFieldRequired"><label for="editFieldRequired" style="color:var(--fg-subtle);font-size:13px">Kötelező</label></div>
            <div style="margin-bottom:16px"><label class="form-label">Sorrend</label><input type="number" name="sort_order" id="editFieldOrder" class="form-input"></div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Mentés</button>
                <button type="button" onclick="closeModal('editFormFieldModal')" class="btn btn-ghost">Mégse</button>
            </div>
        </form>
    </div>
</div>

{{-- Reviewed application detail modal --}}
<div class="modal-backdrop" id="reviewedAppModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('reviewedAppModal')">&times;</button>
        <div class="modal-title" id="reviewedAppTitle"></div>
        <div id="reviewedAppMeta" style="color:var(--fg-subtle);font-size:12px;margin-bottom:16px"></div>
        <div id="reviewedAppNoteWrap" style="display:none;margin-bottom:16px">
            <div class="form-label">Megjegyzés a jelentkezőnek</div>
            <div id="reviewedAppNote" style="color:var(--fg-muted);font-size:13px;white-space:pre-wrap"></div>
        </div>
        <div id="reviewedAppAnswers" style="display:grid;gap:10px"></div>
    </div>
</div>

{{-- Needs-changes modal --}}
<div class="modal-backdrop" id="needsChangesModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('needsChangesModal')">&times;</button>
        <div class="modal-title">Módosítás kérése</div>
        <p style="color:var(--fg-subtle);font-size:13px;margin-bottom:14px">Válaszd ki, mely mezőket kell a jelentkezőnek átdolgoznia. Csak ezeket fogja tudni szerkeszteni.</p>
        <form method="POST" id="needsChangesForm">
            @csrf
            <div id="needsChangesFields" style="margin-bottom:14px"></div>
            <div style="margin-bottom:16px"><label class="form-label">Megjegyzés a jelentkezőnek</label><textarea name="review_note" class="form-textarea" rows="3"></textarea></div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Küldés</button>
                <button type="button" onclick="closeModal('needsChangesModal')" class="btn btn-ghost">Mégse</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleFieldOptions(prefix) {
    const typeEl = document.getElementById(prefix === 'new' ? 'newFieldType' : 'editFieldType');
    const wrap   = document.getElementById(prefix === 'new' ? 'newFieldOptionsWrap' : 'editFieldOptionsWrap');
    wrap.style.display = typeEl.value === 'select' ? 'block' : 'none';
}

function openNewFormField() {
    document.getElementById('newFieldType').value = 'text';
    toggleFieldOptions('new');
    openModal('newFormFieldModal');
}

function openEditFormField(field) {
    document.getElementById('editFormFieldForm').action = `/admin/form-fields/${field.id}`;
    document.getElementById('editFieldLabel').value = field.label;
    document.getElementById('editFieldType').value  = field.type;
    document.getElementById('editFieldOptions').value = (field.options || []).join('\n');
    document.getElementById('editFieldRequired').checked = field.is_required;
    document.getElementById('editFieldOrder').value = field.sort_order;
    toggleFieldOptions('edit');
    openModal('editFormFieldModal');
}

function openReviewedApp(data) {
    document.getElementById('reviewedAppTitle').textContent = `${data.name} (@${data.username})`;
    document.getElementById('reviewedAppMeta').textContent = data.reviewer
        ? `${data.statusLabel} — ${data.reviewer} · ${data.reviewedAt}`
        : `${data.statusLabel} · ${data.reviewedAt}`;

    const noteWrap = document.getElementById('reviewedAppNoteWrap');
    if (data.reviewNote) {
        document.getElementById('reviewedAppNote').textContent = data.reviewNote;
        noteWrap.style.display = '';
    } else {
        noteWrap.style.display = 'none';
    }

    document.getElementById('reviewedAppAnswers').innerHTML = data.answers.map(a => `
        <div>
            <div style="color:var(--fg-subtle);font-size:11px;text-transform:uppercase;letter-spacing:.03em">${escHtml(a.label)}</div>
            <div style="color:var(--fg-muted);font-size:13px;line-height:1.5;white-space:pre-wrap">${escHtml(a.value)}</div>
        </div>
    `).join('');

    openModal('reviewedAppModal');
}

function openNeedsChanges(applicationId, fieldLabelsById) {
    document.getElementById('needsChangesForm').action = `/admin/join-requests/${applicationId}/needs-changes`;
    const container = document.getElementById('needsChangesFields');
    container.innerHTML = Object.entries(fieldLabelsById).map(([id, label]) => `
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
            <input type="checkbox" name="fields_needing_changes[]" value="${id}" id="ndc_${id}">
            <label for="ndc_${id}" style="color:var(--fg-subtle);font-size:13px">${label}</label>
        </div>
    `).join('');
    openModal('needsChangesModal');
}
</script>
@endpush

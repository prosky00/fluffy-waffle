@extends('layouts.app')
@section('title', 'Események')

@push('styles')
<style>
.event-card { border:1px solid var(--border); border-radius:10px; padding:18px; background:var(--surface); margin-bottom:16px; }
.event-card:hover { border-color:var(--accent); }
.rsvp-btn { border:none; padding:6px 14px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; transition:all .15s; }
.rsvp-going.active  { background:#34d399; color:var(--bg); }
.rsvp-maybe.active  { background:var(--accent); color:var(--bg); }
.rsvp-no.active     { background:var(--destructive); color:var(--bg); }
.event-meta { display:flex; gap:16px; flex-wrap:wrap; margin:10px 0; }
.event-meta-item { color:var(--fg-subtle); font-size:13px; display:flex; align-items:center; gap:5px; }
/* EasyMDE */
.CodeMirror { background:var(--bg) !important; color:var(--fg) !important; border-color:var(--border) !important; }
.editor-toolbar { background:var(--bg) !important; border-color:var(--border) !important; }
.editor-toolbar button { color:var(--fg-muted) !important; }
.editor-toolbar button:hover, .editor-toolbar button.active { background:var(--surface-2) !important; color:var(--fg) !important; }
.editor-preview { background:var(--bg) !important; color:var(--fg-muted) !important; }
</style>
@endpush

@section('content')
@php
    $canManage = auth()->user()->is_admin || auth()->user()->is_supervisor;
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <h1 style="color:var(--fg);font-size:24px;font-weight:700;flex:1">Események</h1>
    @if($canManage)
    <button type="button" class="btn btn-ghost" style="font-size:13px" onclick="toggleDescEdit()">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;margin-right:4px;vertical-align:middle"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Leírás szerkesztése
    </button>
    <button type="button" class="btn btn-primary" style="font-size:13px" onclick="openModal('newEventModal')">
        + Új esemény
    </button>
    @endif
</div>

@if(session('success'))
<div class="alert alert-green" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-red" style="margin-bottom:16px">{{ $errors->first() }}</div>
@endif

{{-- General description edit (hidden by default) --}}
@if($canManage)
<div id="descEditForm" style="display:none;margin-bottom:16px">
    <div class="card">
        <div style="color:var(--fg);font-size:14px;font-weight:600;margin-bottom:12px">Általános leírás szerkesztése</div>
        <form method="POST" action="{{ route('esemenyek.update') }}">
            @csrf
            <textarea name="events_content" id="eventsContentMde" class="form-textarea" rows="10"
                      placeholder="Általános leírás (Markdown formázás támogatott)...">{{ $settings->events_content }}</textarea>
            <div style="display:flex;gap:8px;margin-top:12px">
                <button type="submit" class="btn btn-primary">Mentés</button>
                <button type="button" class="btn btn-ghost" onclick="toggleDescEdit()">Mégse</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- General description display --}}
@if($settings->events_content)
<div class="card" style="margin-bottom:24px" id="eventsDesc">
    <div id="eventsDescContent" class="md-content" style="color:var(--fg);font-size:14px;line-height:1.7">{{ $settings->events_content }}</div>
</div>
@endif

{{-- Events grid --}}
@if($events->isEmpty())
<div class="card" style="text-align:center;padding:40px">
    <p style="color:var(--fg-subtle);font-size:15px">Még nincsenek események.
    @if($canManage) Kattints az "Új esemény" gombra az első létrehozásához. @endif</p>
</div>
@else
@foreach($events as $event)
@php
    $userRsvp  = $event->user_rsvp;
    $userStatus = $userRsvp?->status;
    $isFull    = $event->max_persons > 0 && $event->going_count >= $event->max_persons && $userStatus !== 'GOING';
    $endTime   = $event->event_time->addMinutes($event->duration_minutes);
    $isPast    = $event->event_time->isPast();
@endphp
<div class="event-card" style="{{ $isPast ? 'opacity:0.65' : '' }}">
    <div style="display:flex;align-items:flex-start;gap:12px">
        <div style="flex:1">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap">
                <h2 style="color:var(--fg);font-size:16px;font-weight:700;margin:0">{{ $event->title }}</h2>
                @if($isPast)<span class="badge badge-gray">Lezárt</span>@endif
                @if($isFull && !$isPast)<span class="badge badge-red">Betelt</span>@endif
            </div>

            <div class="event-meta">
                <div class="event-meta-item">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ $event->event_time->format('Y. m. d. (D) H:i') }}
                    @if($event->duration_minutes > 0) — {{ $endTime->format('H:i') }} @endif
                </div>
                @if($event->location)
                <div class="event-meta-item">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    {{ $event->location }}
                </div>
                @endif
                <div class="event-meta-item">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    {{ $event->going_count }} megy
                    @if($event->maybe_count > 0) · {{ $event->maybe_count }} talán @endif
                    @if($event->max_persons > 0) / {{ $event->max_persons }} hely @endif
                </div>
                @if($event->duration_minutes > 0)
                <div class="event-meta-item">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    @php
                        $h = intdiv($event->duration_minutes, 60);
                        $m = $event->duration_minutes % 60;
                        echo $h > 0 ? "{$h} óra" : '';
                        echo ($h > 0 && $m > 0) ? ' ' : '';
                        echo $m > 0 ? "{$m} perc" : '';
                    @endphp
                </div>
                @endif
            </div>

            @if($event->description)
            <div class="md-content" style="color:var(--fg-muted);font-size:13px;line-height:1.65;margin-bottom:12px">{{ $event->description }}</div>
            @endif

            {{-- Who's going list (compact) --}}
            @if($event->going_users->count())
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px">
                @foreach($event->going_users->take(8) as $u)
                <span class="badge badge-gray">{{ $u->in_game_name ?? $u->name }}</span>
                @endforeach
                @if($event->going_users->count() > 8)<span style="color:var(--fg-subtle);font-size:11px;padding:2px 4px">+{{ $event->going_users->count() - 8 }}</span>@endif
            </div>
            @endif

            {{-- RSVP buttons --}}
            @if(!$isPast)
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <form method="POST" action="{{ route('events.rsvp', $event->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="GOING">
                    <button type="submit" class="rsvp-btn rsvp-going badge-green {{ $userStatus === 'GOING' ? 'active' : '' }}" {{ $isFull ? 'disabled style="opacity:.4;cursor:not-allowed"' : '' }}>
                        ✓ Megyek{{ $isFull ? ' (telt)' : '' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('events.rsvp', $event->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="MAYBE">
                    <button type="submit" class="rsvp-btn rsvp-maybe badge-yellow {{ $userStatus === 'MAYBE' ? 'active' : '' }}">? Talán</button>
                </form>
                <form method="POST" action="{{ route('events.rsvp', $event->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="NOT_GOING">
                    <button type="submit" class="rsvp-btn rsvp-no badge-red {{ $userStatus === 'NOT_GOING' ? 'active' : '' }}">✕ Nem megyek</button>
                </form>
            </div>
            @endif
        </div>

        {{-- Edit / delete for managers --}}
        @if($canManage)
        <div style="display:flex;gap:6px;flex-shrink:0">
            <button type="button" class="btn btn-ghost" style="font-size:11px;padding:4px 10px"
                onclick="openEdit({{ json_encode($event) }})">Szerkeszt</button>
            <form method="POST" action="{{ route('events.destroy', $event->id) }}" onsubmit="return confirm('Biztosan törlöd az eseményt?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 10px">Törlés</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endforeach
@endif

{{-- ── NEW EVENT MODAL ────────────────────────────────────────── --}}
@if($canManage)
<div class="modal-backdrop" id="newEventModal">
    <div class="modal" style="max-width:600px">
        <button class="modal-close" onclick="closeModal('newEventModal')">&times;</button>
        <div class="modal-title">Új esemény</div>
        <form method="POST" action="{{ route('events.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div style="grid-column:1/-1"><label class="form-label">Cím *</label><input type="text" name="title" class="form-input" required></div>
                <div><label class="form-label">Időpont *</label><input type="datetime-local" name="event_time" class="form-input" required></div>
                <div><label class="form-label">Helyszín</label><input type="text" name="location" class="form-input" placeholder="pl. HQ, Rádió..."></div>
                <div><label class="form-label">Időtartam (perc)</label><input type="number" name="duration_minutes" class="form-input" value="60" min="1"></div>
                <div><label class="form-label">Max. résztvevők (0 = korlátlan)</label><input type="number" name="max_persons" class="form-input" value="0" min="0"></div>
            </div>
            <div style="margin-bottom:16px">
                <label class="form-label">Leírás (Markdown)</label>
                <textarea name="description" id="newEventDesc" class="form-textarea" rows="5" placeholder="Esemény leírása..."></textarea>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Létrehozás</button>
                <button type="button" class="btn btn-ghost" onclick="closeModal('newEventModal')">Mégse</button>
            </div>
        </form>
    </div>
</div>

{{-- ── EDIT EVENT MODAL ───────────────────────────────────────── --}}
<div class="modal-backdrop" id="editEventModal">
    <div class="modal" style="max-width:600px">
        <button class="modal-close" onclick="closeModal('editEventModal')">&times;</button>
        <div class="modal-title">Esemény szerkesztése</div>
        <form method="POST" id="editEventForm" action="">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div style="grid-column:1/-1"><label class="form-label">Cím *</label><input type="text" name="title" id="editTitle" class="form-input" required></div>
                <div><label class="form-label">Időpont *</label><input type="datetime-local" name="event_time" id="editTime" class="form-input" required></div>
                <div><label class="form-label">Helyszín</label><input type="text" name="location" id="editLocation" class="form-input"></div>
                <div><label class="form-label">Időtartam (perc)</label><input type="number" name="duration_minutes" id="editDuration" class="form-input" min="1"></div>
                <div><label class="form-label">Max. résztvevők</label><input type="number" name="max_persons" id="editMax" class="form-input" min="0"></div>
            </div>
            <div style="margin-bottom:16px">
                <label class="form-label">Leírás (Markdown)</label>
                <textarea name="description" id="editDesc" class="form-textarea" rows="5"></textarea>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Mentés</button>
                <button type="button" class="btn btn-ghost" onclick="closeModal('editEventModal')">Mégse</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function toggleDescEdit() {
    const form = document.getElementById('descEditForm');
    const desc = document.getElementById('eventsDesc');
    const open = form.style.display !== 'none';
    form.style.display = open ? 'none' : '';
    if (desc) desc.style.display = open ? '' : 'none';
}

function openEdit(event) {
    document.getElementById('editEventForm').action = '/esemenyek/events/' + event.id;
    document.getElementById('editTitle').value    = event.title    || '';
    document.getElementById('editLocation').value = event.location || '';
    document.getElementById('editDuration').value = event.duration_minutes || 60;
    document.getElementById('editMax').value      = event.max_persons || 0;
    document.getElementById('editDesc').value     = event.description || '';
    // Format datetime-local (strips timezone)
    const dt = event.event_time.replace(' ', 'T').substring(0, 16);
    document.getElementById('editTime').value = dt;
    openModal('editEventModal');
}

// EasyMDE for description editors (only init once, lazily)
let newMde, editMde;
document.getElementById('newEventModal')?.addEventListener('click', e => {
    if (!newMde) {
        newMde = createMde({ element: document.getElementById('newEventDesc') });
    }
}, { once: true });
document.getElementById('editEventModal')?.addEventListener('click', e => {
    if (!editMde) {
        editMde = createMde({ element: document.getElementById('editDesc') });
    }
}, { once: true });

// Desc edit MDE (only when form is opened)
let descMde;
document.querySelector('[onclick="toggleDescEdit()"]')?.addEventListener('click', () => {
    if (!descMde) {
        descMde = createMde({ element: document.getElementById('eventsContentMde') });
    }
});
</script>
@endpush

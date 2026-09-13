@extends('layouts.app')
@section('title', $report->title)

@push('styles')
<style>
.md-body h1,.md-body h2,.md-body h3{color:var(--fg);margin:12px 0 6px}
.md-body p{margin-bottom:8px}
.md-body ul,.md-body ol{padding-left:20px;margin-bottom:8px}
.md-body code{background:var(--surface-2);color:var(--accent);padding:1px 5px;border-radius:3px;font-size:12px}
.md-body pre{background:var(--surface-2);padding:10px;border-radius:6px;overflow-x:auto;margin-bottom:8px}
.md-body a{color:var(--accent)}
.CodeMirror{background:var(--bg) !important;color:var(--fg) !important;border-color:var(--border) !important}
.editor-toolbar{background:var(--surface) !important;border-color:var(--border) !important}
.editor-toolbar button{color:var(--fg-subtle) !important}
.editor-toolbar button:hover,.editor-toolbar button.active{background:var(--border) !important;color:var(--fg) !important}
.editor-preview{background:var(--bg) !important;color:var(--fg) !important}
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $isAuthor = $report->author_id === $user->id;
    $isConnected = $report->connectedUsers->contains('id', $user->id);
    $canManage = $user->is_admin || $user->is_supervisor;
    $canEdit = $canManage || (($isAuthor || $isConnected) && $report->status !== 'APPROVED');
    $statusLabels = ['DRAFT'=>'Vázlat','SUBMITTED'=>'Beküldve','APPROVED'=>'Jóváhagyva','REJECTED'=>'Elutasítva'];
    $statusBadges = ['DRAFT'=>'badge-gray','SUBMITTED'=>'badge-yellow','APPROVED'=>'badge-green','REJECTED'=>'badge-red'];
    $categoryLabels = $categories->pluck('name','slug')->toArray();
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <a href="{{ route('jelentesek') }}" class="btn btn-ghost" style="padding:6px 12px">← Vissza</a>
    <h1 style="color:var(--fg);font-size:20px;font-weight:700;flex:1">{{ $report->title }}</h1>
    <span class="badge {{ $statusBadges[$report->status] ?? 'badge-gray' }}">{{ $statusLabels[$report->status] }}</span>
</div>

@if(session('success'))
<div class="alert alert-green">{{ session('success') }}</div>
@endif

@if($report->status === 'REJECTED' && $report->admin_comment)
<div class="alert alert-red" style="margin-bottom:16px">
    <strong>Elutasítás oka:</strong> {{ $report->admin_comment }}
</div>
@endif

<div class="card" style="margin-bottom:16px">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border)">
        <div><div class="form-label">Szerző</div><div style="color:var(--fg);font-size:14px">{{ $report->author->in_game_name ?? $report->author->name }}</div></div>
        <div><div class="form-label">Kategória</div><div style="color:var(--fg);font-size:14px">{{ $categoryLabels[$report->category] }}</div></div>
        <div><div class="form-label">Dátum</div><div style="color:var(--fg);font-size:14px">{{ $report->created_at->format('Y. m. d.') }}</div></div>
    </div>
    <div class="md-body" id="reportBody" style="color:var(--fg-muted);font-size:14px;line-height:1.7">{{ $report->content }}</div>
</div>

@if($report->connectedUsers->count())
<div class="card" style="margin-bottom:16px">
    <div class="form-label" style="margin-bottom:8px">Érintett személyek</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        @foreach($report->connectedUsers as $cu)
        <span class="badge badge-gray">{{ $cu->in_game_name ?? $cu->name }}</span>
        @endforeach
    </div>
</div>
@endif

@if($canEdit)
<div class="card">
    <div style="color:var(--fg);font-size:14px;font-weight:600;margin-bottom:16px">Szerkesztés</div>
    <form method="POST" action="/jelentesek/{{ $report->id }}" id="editForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="status" id="editStatus" value="{{ $report->status }}">
        <div style="margin-bottom:12px">
            <label class="form-label">Cím</label>
            <input type="text" name="title" class="form-input" value="{{ $report->title }}" required>
        </div>
        <div style="margin-bottom:12px">
            <label class="form-label">Kategória</label>
            <select name="category" class="form-select">
                @foreach($categories as $cat)
                <option value="{{ $cat->slug }}" {{ $report->category===$cat->slug?'selected':'' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:12px">
            <label class="form-label">Tartalom (Markdown támogatott)</label>
            <textarea name="content" id="editContent" class="form-textarea" rows="10">{{ $report->content }}</textarea>
        </div>
        <div style="margin-bottom:12px">
            <label class="form-label">Érintett személyek</label>
            <select name="connected_user_ids[]" class="form-select" multiple style="height:100px">
                @foreach($users as $u)
                    @if($u->id !== auth()->id())
                    <option value="{{ $u->id }}" {{ $report->connectedUsers->contains('id',$u->id)?'selected':'' }}>{{ $u->in_game_name ?? $u->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        @if($canManage && $report->status === 'SUBMITTED')
        <div style="margin-bottom:12px">
            <label class="form-label">Elutasítás megjegyzése</label>
            <input type="text" name="admin_comment" class="form-input" value="{{ $report->admin_comment }}" placeholder="Opcionális">
        </div>
        @endif

        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if($report->status === 'DRAFT' || ($report->status === 'REJECTED' && ($isAuthor || $canManage)))
            <button type="button" onclick="submitEdit('DRAFT')" class="btn btn-ghost">Vázlat mentése</button>
            <button type="button" onclick="submitEdit('SUBMITTED')" class="btn btn-primary">Beküldés</button>
            @elseif($report->status === 'SUBMITTED' && $canManage)
            <button type="button" onclick="submitEdit('APPROVED')" class="btn btn-primary">Jóváhagyás</button>
            <button type="button" onclick="submitEdit('REJECTED')" class="btn btn-danger">Elutasítás</button>
            @elseif($report->status === 'SUBMITTED')
            <button type="button" onclick="submitEdit('SUBMITTED')" class="btn btn-primary">Mentés</button>
            @endif
            @if($canManage || ($isAuthor && $report->status === 'DRAFT'))
            <button type="button" onclick="deleteReport()" class="btn btn-danger">Törlés</button>
            @endif
            <a href="{{ route('jelentesek') }}" class="btn btn-ghost" style="margin-left:auto">Mégse</a>
        </div>
    </form>
    {{-- Delete form is outside editForm to prevent nested-form HTML bug --}}
    @if($user->is_admin || ($isAuthor && $report->status === 'DRAFT'))
    <form method="POST" action="/jelentesek/{{ $report->id }}" id="deleteForm">
        @csrf
        @method('DELETE')
    </form>
    @endif
</div>
@endif
@endsection

@push('scripts')
<script>
// Render stored markdown as HTML
const reportBody = document.getElementById('reportBody');
if (reportBody) reportBody.innerHTML = marked.parse(reportBody.textContent.trim());

// EasyMDE for edit form (only if edit card is visible)
const editEl = document.getElementById('editContent');
if (editEl) {
    createMde({ element: editEl });
}

function submitEdit(status) {
    document.getElementById('editStatus').value = status;
    document.getElementById('editForm').submit();
}
function deleteReport() {
    if (confirm('Biztosan törölni szeretnéd ezt a jelentést?')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
@endpush

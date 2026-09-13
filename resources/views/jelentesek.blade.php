@extends('layouts.app')
@section('title', 'Jelentések')

@push('styles')
<style>
.CodeMirror { background:var(--bg) !important; color:var(--fg) !important; border-color:var(--border) !important; }
.editor-toolbar { background:var(--surface) !important; border-color:var(--border) !important; }
.editor-toolbar button { color:var(--fg-subtle) !important; }
.editor-toolbar button:hover,.editor-toolbar button.active { background:var(--border) !important; color:var(--fg) !important; }
.editor-preview { background:var(--bg) !important; color:var(--fg) !important; }
</style>
@endpush

@section('content')
@php
    $catMap = $categories->pluck('name', 'slug')->toArray();
    $statusLabels = ['DRAFT'=>'Vázlat','SUBMITTED'=>'Beküldve','APPROVED'=>'Jóváhagyva','REJECTED'=>'Elutasítva'];
    $statusBadges = ['DRAFT'=>'badge-gray','SUBMITTED'=>'badge-yellow','APPROVED'=>'badge-green','REJECTED'=>'badge-red'];
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <h1 style="color:var(--fg);font-size:24px;font-weight:700;flex:1">Jelentések</h1>
    <button class="btn btn-primary" onclick="openModal('newModal')">+ Új jelentés</button>
</div>

@if(session('success'))
<div class="alert alert-green">{{ session('success') }}</div>
@endif

<div data-tabs>
<div class="tabs">
    <button class="tab-btn active" data-tab="my">Saját jelentések</button>
    @if($canManage)
    <button class="tab-btn" data-tab="all">Összes jelentés</button>
    @endif
</div>

{{-- MY REPORTS --}}
<div class="tab-panel active" data-panel="my">
<div class="card">
    @forelse($myReports as $report)
    <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border)">
        <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap">
                <a href="{{ route('jelentesek.show', $report->id) }}" style="color:var(--fg);font-size:14px;font-weight:500">{{ $report->title }}</a>
                <span class="badge {{ $statusBadges[$report->status] ?? 'badge-gray' }}">{{ $statusLabels[$report->status] ?? $report->status }}</span>
            </div>
            <div style="color:var(--fg-subtle);font-size:12px">
                {{ $report->author->in_game_name ?? $report->author->name }} ·
                {{ $report->created_at->diffForHumans() }} ·
                {{ $catMap[$report->category] ?? $report->category }}
            </div>
        </div>
        <a href="{{ route('jelentesek.show', $report->id) }}" class="btn btn-ghost" style="font-size:12px;padding:4px 12px">Megnyit</a>
    </div>
    @empty
    <p style="color:var(--fg-subtle);font-size:14px">Még nincsenek saját jelentéseid.</p>
    @endforelse
</div>
</div>

@if($canManage)
{{-- ALL REPORTS --}}
<div class="tab-panel" data-panel="all">
<div class="card" style="margin-bottom:12px;padding:10px 16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <span style="color:var(--fg-subtle);font-size:13px">Szűrő:</span>
    @foreach([''=>'Összes','SUBMITTED'=>'Beküldve','APPROVED'=>'Jóváhagyva','REJECTED'=>'Elutasítva','DRAFT'=>'Vázlat'] as $s=>$l)
    <button type="button" onclick="filterAllReports('{{ $s }}')"
            class="btn btn-ghost" style="font-size:11px;padding:4px 10px">{{ $l }}</button>
    @endforeach
</div>
<div class="card">
    <div id="allReportsList">
    @forelse($allReports as $report)
    <div class="all-report-row" data-status="{{ $report->status }}"
         style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border)">
        <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap">
                <a href="{{ route('jelentesek.show', $report->id) }}" style="color:var(--fg);font-size:14px;font-weight:500">{{ $report->title }}</a>
                <span class="badge {{ $statusBadges[$report->status] ?? 'badge-gray' }}">{{ $statusLabels[$report->status] ?? $report->status }}</span>
            </div>
            <div style="color:var(--fg-subtle);font-size:12px">
                {{ $report->author->in_game_name ?? $report->author->name }} ·
                {{ $report->created_at->diffForHumans() }} ·
                {{ $catMap[$report->category] ?? $report->category }}
            </div>
        </div>
        <a href="{{ route('jelentesek.show', $report->id) }}" class="btn btn-ghost" style="font-size:12px;padding:4px 12px">Megnyit →</a>
    </div>
    @empty
    <p style="color:var(--fg-subtle);font-size:14px">Nincsenek jelentések.</p>
    @endforelse
    </div>
</div>
</div>
@endif

</div>{{-- end data-tabs --}}

{{-- New report modal --}}
<div class="modal-backdrop" id="newModal">
    <div class="modal" style="max-width:700px">
        <button class="modal-close" onclick="closeModal('newModal')">&times;</button>
        <div class="modal-title">Új jelentés</div>
        <form method="POST" action="/jelentesek" id="newReportForm">
            @csrf
            <input type="hidden" name="status" id="newStatus" value="DRAFT">
            <div style="margin-bottom:12px">
                <label class="form-label">Cím</label>
                <input type="text" name="title" class="form-input" required>
            </div>
            <div style="margin-bottom:12px">
                <label class="form-label">Kategória</label>
                <select name="category" class="form-select">
                    @foreach($categories as $cat)
                    <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:12px">
                <label class="form-label">Tartalom (Markdown támogatott)</label>
                <textarea name="content" id="newReportContent" class="form-textarea" rows="8" required></textarea>
            </div>
            <div style="margin-bottom:16px">
                <label class="form-label">Érintett személyek</label>
                <select name="connected_user_ids[]" class="form-select" multiple style="height:100px">
                    @foreach($users as $u)
                        @if($u->id !== auth()->id())
                        <option value="{{ $u->id }}">{{ $u->in_game_name ?? $u->name }}</option>
                        @endif
                    @endforeach
                </select>
                <div style="color:var(--fg-subtle);font-size:11px;margin-top:4px">Ctrl+kattintással több személy is kijelölhető</div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="button" onclick="submitNew('DRAFT')" class="btn btn-ghost">Vázlat mentése</button>
                <button type="button" onclick="submitNew('SUBMITTED')" class="btn btn-primary">Beküldés</button>
                <button type="button" onclick="closeModal('newModal')" class="btn btn-ghost" style="margin-left:auto">Mégse</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let _newMde;
function openModal(id) {
    document.getElementById(id).classList.add('open');
    if (id === 'newModal' && !_newMde) {
        _newMde = createMde({ element: document.getElementById('newReportContent') });
    }
}
function closeModal(id) { document.getElementById(id).classList.remove('open') }
function submitNew(status) {
    document.getElementById('newStatus').value = status;
    document.getElementById('newReportForm').submit();
}
function filterAllReports(status) {
    document.querySelectorAll('.all-report-row').forEach(row => {
        row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
    });
}
</script>
@endpush

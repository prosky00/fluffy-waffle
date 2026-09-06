@extends('layouts.app')
@section('title', 'Főoldal')

@push('styles')
<style>
.dash-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .dash-grid { grid-template-columns: 1fr; }
}

/* Announcement card */
.ann-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 20px;
    margin-bottom: 12px;
}
.ann-card:last-child { margin-bottom: 0; }
.ann-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 12px;
}
.ann-icon {
    width: 38px; height: 38px; border-radius: var(--radius);
    background: oklch(0.553 0.195 38.402 / 15%);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: var(--accent);
}
.ann-meta-top {
    display: flex; flex-direction: column; flex: 1; min-width: 0;
}
.ann-title {
    color: var(--fg); font-size: 15px; font-weight: 600;
    line-height: 1.3; margin-bottom: 4px;
}
.ann-byline {
    display: flex; align-items: center; gap: 8px;
}
.ann-author { color: var(--fg-subtle); font-size: 12px; }
.ann-date   { color: var(--fg-subtle); font-size: 12px; }
.ann-sep    { color: var(--fg-subtle); font-size: 12px; }
.ann-body {
    color: var(--fg-muted); font-size: 14px; line-height: 1.65;
    padding-left: 52px;
}
.ann-body p { margin-bottom: .5em; }
.ann-body p:last-child { margin-bottom: 0; }

/* Section header */
.section-hdr {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 14px;
}
.section-hdr h2 {
    color: var(--fg); font-size: 14px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .05em;
}
.section-hdr-line {
    flex: 1; height: 1px; background: var(--border);
}

/* Online panel */
.online-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    position: sticky; top: 24px;
}
.online-panel-header {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 8px;
}
.online-panel-title {
    color: var(--fg); font-size: 13px; font-weight: 600; flex: 1;
}
.online-count {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 12px; color: var(--fg-subtle);
}
.online-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #34d399; flex-shrink: 0;
    box-shadow: 0 0 0 2px oklch(0.145 0 0), 0 0 0 3px #34d39966;
}
.online-list {
    max-height: 520px; overflow-y: auto;
}
.online-member {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--border);
    transition: background .1s;
}
.online-member:last-child { border-bottom: none; }
.online-member:hover { background: var(--surface-2); }
.online-avatar-wrap {
    position: relative; flex-shrink: 0;
}
.online-avatar, .online-avatar-ph {
    width: 34px; height: 34px; border-radius: 50%; object-fit: cover;
}
.online-avatar-ph {
    background: var(--surface-3);
    display: flex; align-items: center; justify-content: center;
    color: var(--accent); font-size: 13px; font-weight: 700;
}
.online-presence-dot {
    position: absolute; bottom: 0; right: 0;
    width: 9px; height: 9px; border-radius: 50%;
    background: #34d399; border: 2px solid var(--surface);
}
.online-info { flex: 1; min-width: 0; }
.online-name {
    color: var(--fg); font-size: 13px; font-weight: 500;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.online-rank {
    font-size: 11px; margin-top: 1px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.online-empty {
    padding: 28px 16px; text-align: center;
    color: var(--fg-subtle); font-size: 13px;
}
</style>
@endpush

@section('content')
<div class="section-hdr" style="margin-bottom:20px">
    <h1 style="color:var(--fg);font-size:22px;font-weight:700;letter-spacing:normal;text-transform:none">Főoldal</h1>
</div>

<div class="dash-grid">
    {{-- Left: Announcements --}}
    <div>
        <div class="section-hdr">
            <h2>Felhívások</h2>
            <div class="section-hdr-line"></div>
        </div>

        @forelse($announcements as $ann)
        <div class="ann-card">
            <div class="ann-header">
                <div class="ann-icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                </div>
                <div class="ann-meta-top">
                    <div class="ann-title">{{ $ann->title }}</div>
                    <div class="ann-byline">
                        <span class="ann-author">{{ $ann->author->in_game_name ?? $ann->author->name }}</span>
                        <span class="ann-sep">·</span>
                        <span class="ann-date">{{ $ann->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            <div class="ann-body">{!! $ann->content !!}</div>
        </div>
        @empty
        <div class="ann-card">
            <p style="color:var(--fg-subtle);font-size:14px">Még nincsenek felhívások.</p>
        </div>
        @endforelse
    </div>

    {{-- Right: Online members --}}
    <div>
        <div class="section-hdr">
            <h2>Állomány</h2>
            <div class="section-hdr-line"></div>
        </div>
        <div class="online-panel">
            <div class="online-panel-header">
                <div class="online-panel-title">Online tagok</div>
                <div class="online-count">
                    <span class="online-dot"></span>
                    {{ $onlineMembers->count() }} fő
                </div>
            </div>
            <div class="online-list">
                @forelse($onlineMembers as $member)
                <div class="online-member">
                    <div class="online-avatar-wrap">
                        @if($member->avatar)
                            <img src="{{ $member->avatar }}" alt="" class="online-avatar">
                        @else
                            <div class="online-avatar-ph">{{ strtoupper(substr($member->in_game_name ?? $member->name, 0, 1)) }}</div>
                        @endif
                        <span class="online-presence-dot"></span>
                    </div>
                    <div class="online-info">
                        <div class="online-name">{{ $member->in_game_name ?? $member->name }}</div>
                        @if($member->rank)
                        <div class="online-rank" style="color:{{ $member->rank->color }}">{{ $member->rank->name }}</div>
                        @else
                        <div class="online-rank" style="color:var(--fg-subtle)">Nincs rang</div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="online-empty">Jelenleg senki sem online.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Értesítések')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <h1 style="color:var(--fg);font-size:24px;font-weight:700;flex:1">Értesítések</h1>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-ghost">Összes olvasottnak jelöl</button>
    </form>
</div>
<div class="card">
    @forelse($notifications as $notif)
    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
        <div style="width:8px;height:8px;border-radius:50%;background:{{ $notif->read_at ? 'var(--surface-3)' : 'var(--accent)' }};flex-shrink:0"></div>
        <div style="flex:1;color:{{ $notif->read_at ? 'var(--fg-subtle)' : 'var(--fg-muted)' }};font-size:13px">{{ $notif->message }}</div>
        <div style="color:var(--fg-subtle);font-size:11px">{{ $notif->created_at->diffForHumans() }}</div>
        @if(!$notif->read_at)
        <form method="POST" action="{{ route('notifications.read', $notif->id) }}">
            @csrf
            <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:2px 8px">Olvasott</button>
        </form>
        @endif
    </div>
    @empty
    <p style="color:var(--fg-subtle);font-size:14px">Nincsenek értesítések.</p>
    @endforelse
    <div style="margin-top:16px">{{ $notifications->links() }}</div>
</div>
@endsection

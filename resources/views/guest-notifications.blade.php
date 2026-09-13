@extends('layouts.guest')
@section('title', 'Értesítések — ' . $settings->name)

@section('content')
<div class="page-content" style="max-width:640px">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
        <h1 style="font-size:22px;font-weight:900;flex:1">Értesítések</h1>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn-ghost">Összes olvasottnak jelöl</button>
        </form>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
        @forelse($notifications as $notif)
        <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--color-border)">
            <div style="width:8px;height:8px;border-radius:50%;background:{{ $notif->read_at ? '#c7c7c9' : 'var(--color-red)' }};flex-shrink:0"></div>
            <div style="flex:1;color:{{ $notif->read_at ? 'var(--color-text-muted)' : 'var(--color-text)' }};font-size:14px">{{ $notif->message }}</div>
            <div style="color:var(--color-text-muted);font-size:12px;white-space:nowrap">{{ $notif->created_at->diffForHumans() }}</div>
            @if(!$notif->read_at)
            <form method="POST" action="{{ route('notifications.read', $notif->id) }}">
                @csrf
                <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:3px 10px">Olvasott</button>
            </form>
            @endif
        </div>
        @empty
        <p style="color:var(--color-text-muted);font-size:14px;padding:20px">Nincsenek értesítések.</p>
        @endforelse
    </div>
    <div style="margin-top:16px">{{ $notifications->links() }}</div>
</div>
@endsection

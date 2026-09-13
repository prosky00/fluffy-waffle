@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<h1 style="color:var(--fg);font-size:24px;font-weight:700;margin-bottom:24px">Profil</h1>

@if(session('success'))
<div class="alert alert-green">{{ session('success') }}</div>
@endif
@if($errors->has('discord'))
<div class="alert alert-red">{{ $errors->first('discord') }}</div>
@endif

{{-- Identity card --}}
<div class="card" style="display:flex;align-items:center;gap:20px;margin-bottom:16px">
    @if($user->avatar)
        <img src="{{ $user->avatar }}" style="width:80px;height:80px;border-radius:50%;object-fit:cover">
    @else
        <div style="width:80px;height:80px;border-radius:50%;background:var(--surface-3);display:flex;align-items:center;justify-content:center;color:#34d399;font-size:28px;font-weight:700">{{ strtoupper(substr($user->name,0,1)) }}</div>
    @endif
    <div>
        <div style="color:var(--fg);font-size:20px;font-weight:700">{{ $user->in_game_name ?? $user->name }}</div>
        <div style="color:var(--fg-subtle);font-size:14px">{{ '@' . $user->username }}</div>
        @if($user->rank)
        <div class="badge" style="background:{{ $user->rank->color }}22;color:{{ $user->rank->color }};margin-top:6px">{{ $user->rank->name }}</div>
        @endif
    </div>
</div>

{{-- Details --}}
<div class="card" style="margin-bottom:16px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
            <div class="form-label">Karakter név</div>
            <div style="color:var(--fg);font-size:14px">{{ $user->in_game_name ?? '—' }}</div>
        </div>
        <div>
            <div class="form-label">Felhasználónév</div>
            <div style="color:var(--fg);font-size:14px">{{ $user->username ?? '—' }}</div>
        </div>
        <div>
            <div class="form-label">Rendfokozat</div>
            <div style="color:var(--fg);font-size:14px">{{ $user->rank?->name ?? '—' }}</div>
        </div>
        <div>
            <div class="form-label">Utolsó előléptetés</div>
            <div style="color:var(--fg);font-size:14px">{{ $user->rank_up_date?->format('Y. m. d.') ?? '—' }}</div>
        </div>
        <div>
            <div class="form-label">Alosztály</div>
            <div style="color:var(--fg);font-size:14px">{{ $user->department?->name ?? '—' }}</div>
        </div>
        <div>
            <div class="form-label">Alosztályi rang</div>
            <div style="color:var(--fg);font-size:14px">{{ $user->departmentRank?->name ?? '—' }}</div>
        </div>
        @if($user->is_department_leader)
        <div><span class="badge badge-yellow">Alosztályvezető</span></div>
        @elseif($user->is_department_deputy)
        <div><span class="badge badge-gray">Helyettes</span></div>
        @endif
    </div>
</div>

{{-- Discord account --}}
<div class="card">
    <div style="color:var(--fg);font-size:15px;font-weight:600;margin-bottom:14px">Discord fiók</div>

    @if($user->discord_id)
    {{-- Linked --}}
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
        @if($user->avatar)
            <img src="{{ $user->avatar }}" style="width:48px;height:48px;border-radius:50%;object-fit:cover">
        @else
            <div style="width:48px;height:48px;border-radius:50%;background:#5865F2;display:flex;align-items:center;justify-content:center;color:var(--fg);font-weight:700;font-size:18px">D</div>
        @endif
        <div>
            <div style="color:var(--fg);font-size:14px;font-weight:500">Discord csatolva</div>
            <div style="color:var(--fg-subtle);font-size:12px">ID: {{ $user->discord_id }}</div>
        </div>
        <span class="badge badge-blue" style="margin-left:auto">✓ Aktív</span>
    </div>
    <p style="color:var(--fg-muted);font-size:13px;margin-bottom:12px">
        Discord értesítések és DM-ek engedélyezve. Az admin által végrehajtott rangváltozások és jelentés-frissítések Discord üzenetben is megjelennek.
    </p>
    <form method="POST" action="{{ route('auth.discord.unlink') }}" onsubmit="return confirm('Biztosan lecsatolod a Discord fiókot? Ezután nem kapsz Discord értesítőket.')">
        @csrf
        <button type="submit" class="btn btn-ghost" style="font-size:13px">Discord lecsatolása</button>
    </form>

    @else
    {{-- Not linked --}}
    <p style="color:var(--fg-muted);font-size:13px;margin-bottom:16px">
        Csatold a Discord fiókodat, hogy értesítéseket kapj rangváltozásokról, beküldött/visszautasított jelentésekről és egyéb eseményekről közvetlen Discord üzenetben is.
    </p>
    <a href="{{ route('auth.discord.link') }}" class="btn btn-discord">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286z"/>
        </svg>
        Discord fiók csatolása
    </a>
    @endif
</div>

@endsection

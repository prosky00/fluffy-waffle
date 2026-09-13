<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $settings->name) — {{ $settings->name }}</title>
    @if($settings->favicon_url)
        <link rel="icon" href="{{ $settings->favicon_url }}">
    @endif
    <style>
        :root {
            --bg:          oklch(0.145 0 0);
            --surface:     oklch(0.205 0 0);
            --surface-2:   oklch(0.24 0 0);
            --surface-3:   oklch(0.269 0 0);
            --border:      oklch(1 0 0 / 10%);
            --primary:     oklch(0.553 0.195 38.402);
            --primary-hover: oklch(0.47 0.157 37.304);
            --primary-fg:  oklch(0.98 0.016 73.684);
            --accent:      oklch(0.705 0.213 47.604);
            --fg:          oklch(0.985 0 0);
            --fg-muted:    oklch(0.708 0 0);
            --fg-subtle:   oklch(0.556 0 0);
            --destructive: oklch(0.704 0.191 22.216);
            --destructive-hover: oklch(0.577 0.245 27.325);
            --radius:      0px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Inter, system-ui, sans-serif; background: var(--bg); color: var(--fg); min-height: 100vh; }
        a { text-decoration: none; }
        button { cursor: pointer; }
        input, select, textarea { font-family: inherit; }

        /* Sidebar */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 240px;
            background: var(--surface); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; z-index: 50;
        }
        .sidebar-header {
            padding: 16px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar-logo {
            width: 36px; height: 36px; border-radius: 50%; object-fit: cover;
        }
        .sidebar-logo-placeholder {
            width: 36px; height: 36px; border-radius: var(--radius);
            background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            color: var(--primary-fg); font-size: 14px; font-weight: 700; flex-shrink: 0;
        }
        .sidebar-name { color: var(--fg); font-size: 14px; font-weight: 600; }
        .sidebar-sub  { color: var(--fg-subtle); font-size: 11px; }
        nav { flex: 1; overflow-y: auto; padding: 12px 8px; }
        .nav-group { margin-bottom: 20px; }
        .nav-category {
            color: var(--fg-subtle); font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .05em;
            padding: 0 12px; margin-bottom: 6px;
        }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: var(--radius); font-size: 14px;
            color: var(--fg-muted); transition: all .15s;
        }
        .nav-link:hover  { background: var(--surface-2); color: var(--fg); }
        .nav-link.active { background: var(--surface-3); color: var(--accent); }
        .nav-link svg { flex-shrink: 0; }
        .subnav-category {
            color: var(--fg-subtle); font-size: 9px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .06em;
            padding: 6px 12px 2px 28px; display: block;
        }
        .subnav-link {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px 6px 28px; border-radius: var(--radius); font-size: 13px;
            color: var(--fg-subtle); background: none; border: none; width: 100%;
            cursor: pointer; text-align: left; text-decoration: none; transition: all .15s;
        }
        .subnav-link:hover  { background: var(--surface-2); color: var(--fg); }
        .subnav-link.active { background: var(--surface-3); color: var(--accent); }

        /* User area */
        .sidebar-user {
            border-top: 1px solid var(--border); padding: 12px; position: relative;
        }
        .sidebar-user-inner {
            display: flex; align-items: center; gap: 10px;
        }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
        }
        .user-avatar-placeholder {
            width: 36px; height: 36px; border-radius: 50%; background: var(--surface-3);
            display: flex; align-items: center; justify-content: center;
            color: var(--accent); font-size: 14px; font-weight: 700; flex-shrink: 0;
        }
        .user-name  { color: var(--fg); font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-rank  { color: var(--fg-subtle); font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-menu-btn {
            margin-left: auto; background: none; border: none; color: var(--fg-subtle); padding: 4px; flex-shrink: 0;
        }
        .user-menu-btn:hover { color: var(--fg); }
        .user-menu {
            display: none; position: absolute; bottom: 100%; left: 8px; right: 8px; margin-bottom: 4px;
            background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius);
            overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.5);
        }
        .user-menu.open { display: block; }
        .user-menu a, .user-menu button {
            display: flex; align-items: center; gap: 8px; padding: 10px 12px;
            font-size: 13px; color: var(--fg-muted); width: 100%; border: none; background: none; text-align: left;
        }
        .user-menu a:hover, .user-menu button:hover { background: var(--surface-3); color: var(--fg); }
        .user-menu .logout { color: var(--destructive); }
        .user-menu .logout:hover { color: var(--destructive); }

        /* Bell */
        .bell-btn {
            position: relative; background: none; border: none;
            color: var(--fg-subtle); padding: 4px; flex-shrink: 0;
        }
        .bell-btn:hover { color: var(--fg); }
        .bell-badge {
            position: absolute; top: 0; right: 0;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--destructive); display: none;
        }
        .bell-badge.visible { display: block; }
        .bell-dropdown {
            display: none; position: absolute; bottom: calc(100% + 4px); left: 8px; right: 8px;
            background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius);
            box-shadow: 0 8px 32px rgba(0,0,0,.6); z-index: 200; max-height: 360px; overflow: hidden;
            flex-direction: column;
        }
        .bell-dropdown.open { display: flex; }
        .bell-dropdown-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 12px; border-bottom: 1px solid var(--border); flex-shrink: 0;
        }
        .bell-dropdown-header span { color: var(--fg); font-size: 13px; font-weight: 600; }
        .bell-dropdown-header button {
            font-size: 11px; color: var(--fg-subtle); background: none; border: none; cursor: pointer;
        }
        .bell-dropdown-header button:hover { color: var(--accent); }
        .bell-dropdown-list { overflow-y: auto; flex: 1; }
        .bell-notif-item {
            display: flex; align-items: flex-start; gap: 8px;
            padding: 10px 12px; border-bottom: 1px solid var(--border);
        }
        .bell-notif-item:last-child { border-bottom: none; }
        .bell-notif-dot {
            width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; margin-top: 5px;
        }
        .bell-notif-dot.unread { background: var(--accent); }
        .bell-notif-dot.read   { background: var(--surface-3); }
        .bell-notif-text { flex: 1; font-size: 12px; color: var(--fg-muted); line-height: 1.4; }
        .bell-notif-time { font-size: 10px; color: var(--fg-subtle); white-space: nowrap; flex-shrink: 0; }
        .bell-notif-actions { display: flex; gap: 4px; flex-shrink: 0; }
        .bell-notif-btn {
            background: none; border: none; color: var(--fg-subtle);
            font-size: 10px; cursor: pointer; padding: 2px 4px; border-radius: 2px;
        }
        .bell-notif-btn:hover { background: var(--surface-3); color: var(--fg); }
        .bell-empty { padding: 20px 12px; color: var(--fg-subtle); font-size: 13px; text-align: center; }

        /* Main */
        .main { margin-left: 240px; padding: 24px; min-height: 100vh; }

        /* Cards */
        .card {
            background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px;
        }
        .card + .card { margin-top: 16px; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: var(--radius); font-size: 14px; font-weight: 500;
            border: none; transition: background .15s; cursor: pointer;
        }
        .btn-primary { background: var(--primary); color: var(--primary-fg); }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger  { background: var(--destructive); color: var(--fg); }
        .btn-danger:hover  { background: var(--destructive-hover); }
        .btn-ghost   { background: transparent; color: var(--fg-muted); border: 1px solid var(--border); }
        .btn-ghost:hover   { background: var(--surface-2); color: var(--fg); }
        .btn-discord { background: #5865F2; color: #fff; }
        .btn-discord:hover { background: #4752C4; }

        /* Form elements */
        .form-input, .form-select, .form-textarea {
            background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);
            color: var(--fg); padding: 8px 12px; font-size: 14px; width: 100%;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none; border-color: var(--accent);
        }
        .form-select option { background: var(--surface); }
        .form-label { color: var(--fg-muted); font-size: 13px; margin-bottom: 6px; display: block; }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center; padding: 2px 8px;
            border-radius: 9999px; font-size: 11px; font-weight: 600;
        }
        .badge-green  { background: rgba(52,211,153,.15); color: #34d399; }
        .badge-yellow { background: oklch(0.553 0.195 38.402 / 15%); color: oklch(0.705 0.213 47.604); }
        .badge-red    { background: oklch(0.704 0.191 22.216 / 15%); color: oklch(0.704 0.191 22.216); }
        .badge-gray   { background: oklch(0.708 0 0 / 12%); color: oklch(0.708 0 0); }
        .badge-blue   { background: rgba(88,101,242,.15); color: #5865F2; }
        .badge-purple { background: rgba(168,85,247,.15); color: #a855f7; }

        /* Table */
        .table { width: 100%; border-collapse: collapse; }
        .table th { color: var(--fg-subtle); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; padding: 8px 12px; text-align: left; border-bottom: 1px solid var(--border); }
        .table td { padding: 10px 12px; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--fg-muted); vertical-align: middle; }
        .table tr:hover td { background: var(--surface-2); }

        /* Tabs */
        .tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border); margin-bottom: 24px; }
        .tab-btn {
            padding: 8px 16px; font-size: 14px; color: var(--fg-muted);
            background: none; border: none; border-bottom: 2px solid transparent;
            margin-bottom: -1px; transition: all .15s;
        }
        .tab-btn:hover  { color: var(--fg); }
        .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Alert */
        .alert { padding: 12px 16px; border-radius: var(--radius); font-size: 14px; margin-bottom: 16px; }
        .alert-red    { background: oklch(0.704 0.191 22.216 / 10%); border: 1px solid oklch(0.704 0.191 22.216 / 30%); color: oklch(0.704 0.191 22.216); }
        .alert-green  { background: rgba(52,211,153,.1);  border: 1px solid rgba(52,211,153,.3);  color: #34d399; }
        .alert-yellow { background: oklch(0.553 0.195 38.402 / 10%); border: 1px solid oklch(0.553 0.195 38.402 / 30%); color: oklch(0.705 0.213 47.604); }

        /* Modal */
        .modal-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.7); z-index: 100; align-items: center; justify-content: center;
        }
        .modal-backdrop.open { display: flex; }
        .modal {
            background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
            padding: 24px; width: 100%; max-width: 600px; max-height: 90vh;
            overflow-y: auto; position: relative;
        }
        .modal-title { color: var(--fg); font-size: 18px; font-weight: 600; margin-bottom: 20px; }
        .modal-close {
            position: absolute; top: 16px; right: 16px;
            background: none; border: none; color: var(--fg-subtle); font-size: 20px;
        }
        .modal-close:hover { color: var(--fg); }
    </style>
    @stack('styles')
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-header">
        @if($settings->logo_url)
            <img src="{{ $settings->logo_url }}" alt="Logo" class="sidebar-logo">
        @else
            <div class="sidebar-logo-placeholder">{{ strtoupper(substr($settings->name, 0, 1)) }}</div>
        @endif
        <div>
            <div class="sidebar-name">{{ $settings->name }}</div>
            <div class="sidebar-sub">{{ $settings->header_text }}</div>
        </div>
    </div>

    <nav>
        <div class="nav-group">
            <div class="nav-category">Állomány</div>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Főoldal
            </a>
            <a href="{{ route('jelentesek') }}" class="nav-link {{ request()->is('jelentesek*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Jelentések
            </a>
            <a href="{{ route('esemenyek') }}" class="nav-link {{ request()->is('esemenyek*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Események
            </a>
            <a href="{{ route('intranet') }}" class="nav-link {{ request()->is('intranet*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                Intranet
            </a>
            <a href="{{ route('allomany') }}" class="nav-link {{ request()->is('allomany*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Állomány
            </a>
            <a href="{{ route('lekerdezo') }}" class="nav-link {{ request()->is('lekerdezo*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Lekérdező
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-category">Alosztály / Személyes</div>
            @php $_myDepts = auth()->user()->departments()->orderBy('name')->get(); @endphp
            @if($_myDepts->count() > 1)
                @foreach($_myDepts as $_d)
                <a href="{{ route('alosztalyom') }}?dept={{ $_d->id }}" class="nav-link {{ request()->is('alosztalyom*') && request('dept') == $_d->id ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    {{ $_d->short_name ?? $_d->name }}
                </a>
                @endforeach
            @else
                <a href="{{ route('alosztalyom') }}{{ $_myDepts->isNotEmpty() ? '?dept='.$_myDepts->first()->id : '' }}" class="nav-link {{ request()->is('alosztalyom*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Alosztályom
                </a>
            @endif
        </div>

        @if(auth()->user()->isHr())
        <div class="nav-group">
            <div class="nav-category">HR</div>
            <a href="{{ route('hr.index') }}" class="nav-link {{ request()->is('hr*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span style="flex:1;text-align:left">HR</span>
                @php $_pendingApps = \App\Models\FactionApplication::where('status', 'PENDING')->count(); @endphp
                @if($_pendingApps > 0)
                <span class="badge badge-red" style="font-size:10px;padding:1px 6px">{{ $_pendingApps }}</span>
                @endif
            </a>
        </div>
        @endif

        @if(auth()->user()->is_admin || auth()->user()->is_supervisor)
        <div class="nav-group">
            <div class="nav-category">{{ auth()->user()->is_admin ? 'Admin' : 'Kezelés' }}</div>
            <a href="{{ route('admin') }}" class="nav-link {{ request()->is('admin') && !request()->query('tab') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                {{ auth()->user()->is_admin ? 'Admin felület' : 'Kezelőpanel' }}
            </a>
            @stack('nav-sub')
        </div>
        @endif
    </nav>

    <div class="sidebar-user" id="userArea">
        <div class="user-menu" id="userMenu">
            <a href="{{ route('profile') }}">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Profil
            </a>
            <a href="{{ route('settings') }}">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Beállítások
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Kijelentkezés
                </button>
            </form>
        </div>
        <div class="sidebar-user-inner">
            @if(auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="user-avatar">
            @else
                <div class="user-avatar-placeholder">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            @endif
            <div style="flex:1;min-width:0">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-rank">{{ auth()->user()->rank?->name ?? 'Nincs rang' }}</div>
            </div>
                <button class="bell-btn" id="bellBtn" onclick="toggleBell(event)" title="Értesítések">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span class="bell-badge" id="bellBadge"></span>
            </button>
            <button class="user-menu-btn" onclick="document.getElementById('userMenu').classList.toggle('open')">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
            </button>
        </div>
        <div class="bell-dropdown" id="bellDropdown">
            <div class="bell-dropdown-header">
                <span>Értesítések</span>
                <button onclick="markAllRead()">Összes olvasott</button>
            </div>
            <div class="bell-dropdown-list" id="bellList"><div class="bell-empty">Betöltés...</div></div>
        </div>
    </div>
</aside>

<div class="modal-backdrop" id="notifDetailModal">
    <div class="modal" style="max-width:420px">
        <button class="modal-close" onclick="closeModal('notifDetailModal')">&times;</button>
        <div class="modal-title">Értesítés</div>
        <p id="notifDetailText" style="color:var(--fg);font-size:14px;line-height:1.6;white-space:pre-wrap;margin-bottom:8px"></p>
        <p id="notifDetailTime" style="color:var(--fg-subtle);font-size:12px"></p>
    </div>
</div>

<main class="main">
    @yield('content')
</main>

<script>
const _csrf = document.querySelector('meta[name="csrf-token"]').content;

document.addEventListener('click', function(e) {
    const menu = document.getElementById('userMenu');
    const area = document.getElementById('userArea');
    if (menu && area && !area.contains(e.target)) {
        menu.classList.remove('open');
        document.getElementById('bellDropdown')?.classList.remove('open');
    }
});

// Bell dropdown
function toggleBell(e) {
    e.stopPropagation();
    const dd = document.getElementById('bellDropdown');
    document.getElementById('userMenu').classList.remove('open');
    dd.classList.toggle('open');
    if (dd.classList.contains('open')) loadNotifications();
}

let _notifCache = {};

function loadNotifications() {
    fetch('{{ route("notifications.unread") }}')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('bellBadge');
            const list  = document.getElementById('bellList');
            badge.classList.toggle('visible', data.unread_count > 0);
            if (!data.notifications.length) {
                list.innerHTML = '<div class="bell-empty">Nincsenek értesítések.</div>';
                return;
            }
            data.notifications.forEach(n => _notifCache[n.id] = n);
            list.innerHTML = data.notifications.map(n => `
                <div class="bell-notif-item" id="bni-${n.id}">
                    <span class="bell-notif-dot ${n.read ? 'read' : 'unread'}"></span>
                    <span class="bell-notif-text" onclick="showNotifDetail(${n.id})" style="cursor:pointer">${escHtml(n.message)}</span>
                    <span class="bell-notif-time">${escHtml(n.time)}</span>
                    <span class="bell-notif-actions">
                        ${!n.read ? `<button class="bell-notif-btn" onclick="readNotif(${n.id})" title="Olvasott">✓</button>` : ''}
                        <button class="bell-notif-btn" onclick="deleteNotif(${n.id})" title="Töröl">✕</button>
                    </span>
                </div>`).join('');
        });
}

function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

function showNotifDetail(id) {
    const n = _notifCache[id];
    if (!n) return;
    document.getElementById('notifDetailText').textContent = n.message;
    document.getElementById('notifDetailTime').textContent = n.time;
    openModal('notifDetailModal');
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function readNotif(id) {
    fetch(`/notifications/${id}/read`, {method:'POST', headers:{'X-CSRF-TOKEN':_csrf}})
        .then(() => loadNotifications());
}

function deleteNotif(id) {
    fetch(`/notifications/${id}`, {method:'DELETE', headers:{'X-CSRF-TOKEN':_csrf}})
        .then(() => loadNotifications());
}

function markAllRead() {
    fetch('{{ route("notifications.read-all") }}', {method:'POST', headers:{'X-CSRF-TOKEN':_csrf}})
        .then(() => loadNotifications());
}

// Init badge on page load
fetch('{{ route("notifications.unread") }}').then(r=>r.json()).then(d => {
    document.getElementById('bellBadge').classList.toggle('visible', d.unread_count > 0);
});

// Heartbeat — update last_active_at every 60s
setInterval(() => {
    fetch('{{ route("heartbeat") }}', {method:'POST', headers:{'X-CSRF-TOKEN':_csrf}});
}, 60000);

// Tab system
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const group = this.closest('[data-tabs]');
        group.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        group.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        group.querySelector('[data-panel="' + this.dataset.tab + '"]').classList.add('active');
    });
});
// Restore active tab from ?tab= query param
const _urlTab = new URLSearchParams(location.search).get('tab');
if (_urlTab) {
    const _btn = document.querySelector('.tab-btn[data-tab="' + _urlTab + '"]');
    if (_btn) _btn.click();
}
</script>
@stack('scripts')
</body>
</html>

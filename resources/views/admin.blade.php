@extends('layouts.app')
@section('title', auth()->user()->is_admin ? 'Admin felület' : 'Kezelőpanel')

{{-- Push sub-navigation into the main sidebar --}}
@push('nav-sub')
@php $curTab = request()->query('tab', auth()->user()->is_admin ? 'users' : 'reports'); @endphp

@if(auth()->user()->is_admin)
<span class="subnav-category">Tagok</span>
<button class="subnav-link {{ $curTab==='users' ? 'active' : '' }}" onclick="showPanel('users')">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Felhasználók
</button>
<button class="subnav-link {{ $curTab==='ranks' ? 'active' : '' }}" onclick="showPanel('ranks')">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    Rangok
</button>
<button class="subnav-link {{ $curTab==='departments' ? 'active' : '' }}" onclick="showPanel('departments')">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    Alosztályok
</button>
@endif

<span class="subnav-category">Tartalom</span>
<button class="subnav-link {{ $curTab==='announcements' ? 'active' : '' }}" onclick="showPanel('announcements')">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    Felhívások
</button>
@if(auth()->user()->is_admin)
<button class="subnav-link {{ $curTab==='categories' ? 'active' : '' }}" onclick="showPanel('categories')">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
    Kat. (Jelentések)
</button>
@endif

<span class="subnav-category">Jelentések</span>
<button class="subnav-link {{ $curTab==='reports' ? 'active' : '' }}" onclick="showPanel('reports')">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    Összes jelentés
</button>

@if(auth()->user()->is_admin)
<span class="subnav-category">Rendszer</span>
<button class="subnav-link {{ $curTab==='settings' ? 'active' : '' }}" onclick="showPanel('settings')">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4"/></svg>
    Beállítások
</button>
<button class="subnav-link {{ $curTab==='discord' ? 'active' : '' }}" onclick="showPanel('discord')">
    <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057c.001.022.015.04.032.05a19.9 19.9 0 0 0 5.993 3.03.077.077 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
    Discord
</button>
<button class="subnav-link {{ $curTab==='sync' ? 'active' : '' }}" onclick="showPanel('sync')">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
    Szinkron
</button>
<button class="subnav-link {{ $curTab==='messages' ? 'active' : '' }}" onclick="showPanel('messages')">
    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
    Üzenetek
</button>
@endif
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/easymde/2.18.0/easymde.min.css">
<style>
.admin-panel { display:none; }
.admin-panel.active { display:block; }
.status-badge { padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:600; }
.badge-yellow { background:rgba(245,158,11,.15); color:#f59e0b; }
.badge-green  { background:rgba(52,211,153,.15);  color:#34d399; }
.badge-red    { background:rgba(248,113,113,.15);  color:#f87171; }
.badge-gray   { background:rgba(139,148,158,.15);  color:#8b949e; }
.badge-purple { background:rgba(168,85,247,.15);   color:#a855f7; }
.CodeMirror { background:oklch(0.145 0 0) !important; color:oklch(0.985 0 0) !important; border-color:oklch(1 0 0 / 10%) !important; }
.editor-toolbar { background:oklch(0.205 0 0) !important; border-color:oklch(1 0 0 / 10%) !important; }
.editor-toolbar button { color:oklch(0.708 0 0) !important; }
.editor-toolbar button:hover, .editor-toolbar button.active { background:oklch(1 0 0 / 10%) !important; color:oklch(0.985 0 0) !important; }
.editor-preview { background:oklch(0.145 0 0) !important; color:oklch(0.985 0 0) !important; }
</style>
@endpush

@section('content')
<h1 style="color:#fff;font-size:22px;font-weight:700;margin-bottom:20px">{{ auth()->user()->is_admin ? 'Admin felület' : 'Kezelőpanel' }}</h1>

@if(session('success'))
<div class="alert alert-green">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-red">{{ $errors->first() }}</div>
@endif

{{-- ════ USERS ════ --}}
@if(auth()->user()->is_admin)
<div id="panel-users" class="admin-panel">
<div class="card" style="margin-bottom:16px;border-color:#34d39944">
    <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer" onclick="toggleNewUser()">
        <div style="color:#34d399;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">+ Új fiók létrehozása</div>
        <svg id="newUserChevron" width="16" height="16" fill="none" stroke="#34d399" stroke-width="2" viewBox="0 0 24 24" style="transition:transform .2s;flex-shrink:0"><polyline points="6 9 12 15 18 9"/></svg>
    </div>
    <div id="newUserForm" style="display:none;margin-top:14px;padding-top:14px;border-top:1px solid #1e2d3d">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:8px">
                <div><label class="form-label">Felhasználónév *</label><input type="text" name="username" class="form-input" placeholder="csak betű, szám, - _" required></div>
                <div><label class="form-label">Jelszó *</label><input type="password" name="password" class="form-input" placeholder="min. 6 karakter" required></div>
                <div><label class="form-label">Megjelenítési név *</label><input type="text" name="name" class="form-input" required></div>
                <div><label class="form-label">Karakter név</label><input type="text" name="in_game_name" class="form-input"></div>
                <div>
                    <label class="form-label">Rendfokozat</label>
                    <select name="rank_id" class="form-select">
                        <option value="">— nincs —</option>
                        @foreach($ranks as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px;justify-content:flex-end;padding-bottom:4px">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#8b949e"><input type="checkbox" name="is_supervisor" value="1"> Szupervisor</label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#8b949e"><input type="checkbox" name="is_admin" value="1"> Admin</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="font-size:12px;padding:6px 14px">Fiók létrehozása</button>
        </form>
    </div>
</div>

<div style="margin-bottom:12px">
    <input type="text" id="userSearch" class="form-input" placeholder="Keresés karakter név, felhasználónév szerint..." oninput="filterUsers()">
</div>

<div class="card" style="padding:0;overflow:hidden">
<div style="overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:13px">
    <thead><tr style="border-bottom:1px solid #1e2d3d">
        <th style="padding:10px 16px;text-align:left;color:#4a5568;font-weight:500">Tag</th>
        <th style="padding:10px 16px;text-align:left;color:#4a5568;font-weight:500">Felhasználónév</th>
        <th style="padding:10px 16px;text-align:left;color:#4a5568;font-weight:500">Rendfokozat</th>
        <th style="padding:10px 16px;text-align:left;color:#4a5568;font-weight:500">Discord</th>
        <th style="padding:10px 16px;text-align:left;color:#4a5568;font-weight:500">Szerepkör</th>
        <th style="padding:10px 16px;text-align:right;color:#4a5568;font-weight:500">Műveletek</th>
    </tr></thead>
    <tbody>
    @foreach($users as $u)
    <tr class="user-row" data-search="{{ strtolower(($u->in_game_name ?? $u->name) . ' ' . $u->username) }}"
        style="border-bottom:1px solid #111c2a;transition:background .12s"
        onmouseover="this.style.background='#0d1826'" onmouseout="this.style.background=''">
        <td style="padding:10px 16px">
            <div style="display:flex;align-items:center;gap:10px">
                @if($u->avatar)
                    <img src="{{ $u->avatar }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0">
                @else
                    <div style="width:32px;height:32px;border-radius:50%;background:#1a2332;display:flex;align-items:center;justify-content:center;color:#34d399;font-weight:700;font-size:13px;flex-shrink:0">{{ strtoupper(substr($u->name,0,1)) }}</div>
                @endif
                <span style="color:#fff;font-weight:500">{{ $u->in_game_name ?? $u->name }}</span>
                @if($u->is_suspended)<span style="color:#f87171;font-size:11px;margin-left:4px">(felfüggesztve)</span>@endif
            </div>
        </td>
        <td style="padding:10px 16px;color:#8b949e">{{ $u->username ? '@'.$u->username : '—' }}</td>
        <td style="padding:10px 16px">
            @if($u->rank)<span class="badge" style="background:{{ $u->rank->color }}22;color:{{ $u->rank->color }}">{{ $u->rank->name }}</span>
            @else<span style="color:#4a5568">—</span>@endif
        </td>
        <td style="padding:10px 16px">
            @if($u->discord_id)<span style="color:#5865F2;font-size:12px">✓ Csatolva</span>
            @else<span style="color:#4a5568;font-size:12px">—</span>@endif
        </td>
        <td style="padding:10px 16px">
            @if($u->is_admin)<span class="badge badge-red" style="font-size:10px;margin-right:3px">Admin</span>@endif
            @if($u->is_supervisor)<span class="badge badge-purple" style="font-size:10px">Szupervisor</span>@endif
        </td>
        <td style="padding:10px 16px;text-align:right">
            <button type="button" class="btn btn-ghost" style="font-size:11px;padding:4px 10px" onclick="toggleUserEdit({{ $u->id }})">Szerkesztés</button>
        </td>
    </tr>
    <tr id="edit_row_{{ $u->id }}" style="display:none">
        <td colspan="6" style="padding:16px 20px;background:#0a111a;border-bottom:1px solid #1e2d3d">
            <form method="POST" action="/admin/users/{{ $u->id }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px">
                    <div><label class="form-label">Felhasználónév</label><input type="text" name="username" class="form-input" value="{{ $u->username }}" style="font-size:13px"></div>
                    <div><label class="form-label">Karakter név</label><input type="text" name="in_game_name" class="form-input" value="{{ $u->in_game_name }}" style="font-size:13px"></div>
                    <div><label class="form-label">Új jelszó (üres = nem változik)</label><input type="password" name="new_password" class="form-input" placeholder="Új jelszó…" style="font-size:13px"></div>
                    <div>
                        <label class="form-label">Rendfokozat</label>
                        <select name="rank_id" class="form-select">
                            <option value="">— nincs —</option>
                            @foreach($ranks as $r)<option value="{{ $r->id }}" {{ $u->rank_id===$r->id?'selected':'' }}>{{ $r->name }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Utolsó előléptetés</label><input type="date" name="rank_up_date" class="form-input" value="{{ $u->rank_up_date?->format('Y-m-d') }}" style="font-size:13px"></div>
                    <div>
                        <label class="form-label">Értesítések</label>
                        <select name="notification_preference" class="form-select">
                            <option value="ALL" {{ ($u->notification_preference ?? 'ALL')==='ALL' ? 'selected' : '' }}>Minden értesítés</option>
                            <option value="MESSAGES_ONLY" {{ ($u->notification_preference ?? '')==='MESSAGES_ONLY' ? 'selected' : '' }}>Csak üzenetek</option>
                        </select>
                    </div>
                </div>
                {{-- Department: 2-column selector --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
                    <div>
                        <label class="form-label">Elsődleges alosztály</label>
                        <select name="department_id" class="form-select" id="dept_{{ $u->id }}" onchange="filterDeptRanks({{ $u->id }})">
                            <option value="">— nincs —</option>
                            @foreach($departments as $d)<option value="{{ $d->id }}" {{ $u->department_id===$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Alosztályi rang</label>
                        <select name="department_rank_id" class="form-select" id="deptrank_{{ $u->id }}">
                            <option value="">— nincs —</option>
                            @foreach($departments as $d)
                                @foreach($d->ranks as $dr)
                                <option value="{{ $dr->id }}" data-dept="{{ $d->id }}" {{ $u->department_rank_id===$dr->id?'selected':'' }}>{{ $dr->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
                {{-- Multi-dept membership checkboxes --}}
                <div style="margin-bottom:10px">
                    <label class="form-label">Alosztály tagságok</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 16px;padding:10px 12px;background:var(--bg);border:1px solid var(--border)">
                        @foreach($departments as $d)
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:oklch(0.708 0 0);padding:3px 0;cursor:pointer">
                            <input type="checkbox" name="department_ids[]" value="{{ $d->id }}" {{ $u->departments->contains('id',$d->id)?'checked':'' }}>
                            {{ $d->name }}@if($d->short_name)<span style="color:oklch(0.556 0 0);font-size:11px;margin-left:4px">{{ $d->short_name }}</span>@endif
                        </label>
                        @endforeach
                    </div>
                </div>
                {{-- Role flags --}}
                <div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:10px;padding:10px 12px;background:var(--bg);border:1px solid var(--border)">
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:oklch(0.708 0 0)"><input type="checkbox" name="is_department_leader" value="1" {{ $u->is_department_leader?'checked':'' }}> Alosztályvezető</label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:oklch(0.708 0 0)"><input type="checkbox" name="is_department_deputy" value="1" {{ $u->is_department_deputy?'checked':'' }}> Helyettes</label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:oklch(0.708 0 0)"><input type="checkbox" name="is_supervisor" value="1" {{ $u->is_supervisor?'checked':'' }}> Szupervisor</label>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:oklch(0.708 0 0)"><input type="checkbox" name="is_admin" value="1" {{ $u->is_admin?'checked':'' }}> Admin</label>
                </div>

                <div style="display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary" style="font-size:12px;padding:6px 14px">Mentés</button>
                    <button type="button" class="btn btn-ghost" style="font-size:12px;padding:6px 14px" onclick="toggleUserEdit({{ $u->id }})">Bezárás</button>
                </div>
            </form>
            @if($u->id !== auth()->id())
            <div style="display:flex;gap:8px;margin-top:10px;padding-top:10px;border-top:1px solid #1e2d3d">
                <form method="POST" action="{{ route('admin.users.suspend', $u->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:4px 10px">{{ $u->is_suspended ? '✓ Aktiválás' : '⊘ Felfüggesztés' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" onsubmit="return confirm('Biztosan törlöd a fiókot?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 10px">Törlés</button>
                </form>
            </div>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
</div>
</div>
@endif

{{-- ════ RANKS ════ --}}
@if(auth()->user()->is_admin)
<div id="panel-ranks" class="admin-panel">
<div class="card" style="margin-bottom:16px">
    <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:16px">Új rang</div>
    <form method="POST" action="/admin/ranks" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;align-items:flex-end">
        @csrf
        <div><label class="form-label">Név</label><input type="text" name="name" class="form-input" required></div>
        <div><label class="form-label">Szín</label><input type="color" name="color" class="form-input" value="#8b949e" style="height:38px;padding:4px"></div>
        <div><label class="form-label">Szint</label><input type="number" name="level" class="form-input" value="0"></div>
        <div><label class="form-label">Discord szerepkör ID</label><input type="text" name="discord_role_id" class="form-input" placeholder="Opcionális"></div>
        <div style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="is_admin" value="1" id="newRankAdmin"><label for="newRankAdmin" style="color:#8b949e;font-size:13px">Admin rang</label></div>
        <button type="submit" class="btn btn-primary">Hozzáadás</button>
    </form>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>Név</th><th>Szín</th><th>Szint</th><th>Discord szerepkör</th><th>Admin</th><th></th></tr></thead>
        <tbody>
        @foreach($ranks as $rank)
        <tr>
            <form method="POST" action="/admin/ranks/{{ $rank->id }}" style="display:contents">
                @csrf @method('PUT')
                <td><input type="text" name="name" class="form-input" value="{{ $rank->name }}" style="font-size:13px;padding:4px 8px"></td>
                <td><input type="color" name="color" class="form-input" value="{{ $rank->color }}" style="height:32px;padding:2px;width:60px"></td>
                <td><input type="number" name="level" class="form-input" value="{{ $rank->level }}" style="font-size:13px;padding:4px 8px;width:70px"></td>
                <td><input type="text" name="discord_role_id" class="form-input" value="{{ $rank->discord_role_id }}" style="font-size:13px;padding:4px 8px"></td>
                <td><input type="checkbox" name="is_admin" value="1" {{ $rank->is_admin?'checked':'' }}></td>
                <td style="display:flex;gap:4px">
                    <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:4px 8px">Mentés</button>
            </form>
                    <form method="POST" action="/admin/ranks/{{ $rank->id }}" onsubmit="return confirm('Törlés?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 8px">Törlés</button>
                    </form>
                </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>
@endif

{{-- ════ DEPARTMENTS ════ --}}
<div id="panel-departments" class="admin-panel">
<div class="card">
    <div style="color:oklch(0.985 0 0);font-size:15px;font-weight:600;margin-bottom:6px">Alosztályok — Discord beállítások</div>
    <p style="color:oklch(0.708 0 0);font-size:13px;margin-bottom:16px">
        Minden alosztályhoz rendelj egy Discord szerepkört és csatornát. Ezek automatikusan öröklődnek az intranet csoport-üzenetváltásokra.
    </p>
    <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="border-bottom:1px solid oklch(1 0 0 / 10%)">
            <th style="padding:8px 12px;text-align:left;color:oklch(0.556 0 0);font-weight:500">Alosztály</th>
            <th style="padding:8px 12px;text-align:left;color:oklch(0.556 0 0);font-weight:500">Discord szerepkör</th>
            <th style="padding:8px 12px;text-align:left;color:oklch(0.556 0 0);font-weight:500">Discord csatorna ID</th>
            <th style="padding:8px 12px;text-align:right;color:oklch(0.556 0 0);font-weight:500"></th>
        </tr></thead>
        <tbody>
        @foreach($departments as $dept)
        <tr style="border-bottom:1px solid oklch(1 0 0 / 6%)">
            <form method="POST" action="{{ route('admin.departments.discord', $dept->id) }}" style="display:contents">
                @csrf @method('PUT')
                <td style="padding:10px 12px">
                    <div style="color:oklch(0.985 0 0);font-weight:500">{{ $dept->name }}</div>
                    <div style="color:oklch(0.556 0 0);font-size:11px">{{ $dept->members_count }} tag</div>
                </td>
                <td style="padding:10px 12px">
                    @if(count($discordRoles))
                    <select name="discord_role_id" class="form-select" style="font-size:12px;padding:4px 8px">
                        <option value="">— Nincs —</option>
                        @foreach($discordRoles as $role)
                        <option value="{{ $role['id'] }}" {{ $dept->discord_role_id === $role['id'] ? 'selected' : '' }}>
                            {{ $role['name'] }}
                        </option>
                        @endforeach
                    </select>
                    @else
                    <input type="text" name="discord_role_id" class="form-input" value="{{ $dept->discord_role_id }}" placeholder="Szerepkör ID" style="font-size:12px;padding:4px 8px">
                    @endif
                </td>
                <td style="padding:10px 12px">
                    <input type="text" name="discord_channel_id" class="form-input" value="{{ $dept->discord_channel_id }}" placeholder="Csatorna ID" style="font-size:12px;padding:4px 8px">
                </td>
                <td style="padding:10px 12px;text-align:right">
                    <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:4px 10px">Mentés</button>
                </td>
            </form>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

{{-- ════ ANNOUNCEMENTS ════ --}}
<div id="panel-announcements" class="admin-panel">
<div class="card" style="margin-bottom:16px">
    <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:16px">Új felhívás</div>
    <form method="POST" action="{{ route('admin.announcements.store') }}" id="annForm">
        @csrf
        <div style="margin-bottom:12px"><label class="form-label">Cím</label><input type="text" name="title" class="form-input" required></div>
        <div style="margin-bottom:12px">
            <label class="form-label">Tartalom (Markdown támogatott)</label>
            <textarea name="content" id="annContent" class="form-textarea" rows="8" required></textarea>
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
            <input type="checkbox" name="post_to_discord" value="1" id="postDiscord">
            <label for="postDiscord" style="color:#8b949e;font-size:13px">Küldés Discord-ra is</label>
        </div>
        <button type="submit" class="btn btn-primary">Közzététel</button>
    </form>
</div>
<div class="card">
    @foreach($announcements as $ann)
    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #1e2d3d">
        <div style="flex:1">
            <div style="color:#fff;font-size:14px;font-weight:500">{{ $ann->title }}</div>
            <div style="color:#4a5568;font-size:12px">{{ $ann->author->name }} · {{ $ann->created_at->diffForHumans() }}</div>
        </div>
        <form method="POST" action="{{ route('admin.announcements.destroy', $ann->id) }}" onsubmit="return confirm('Törlés?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 8px">Törlés</button>
        </form>
    </div>
    @endforeach
</div>
</div>

{{-- ════ CATEGORIES ════ --}}
@if(auth()->user()->is_admin)
<div id="panel-categories" class="admin-panel">
<div class="card" style="margin-bottom:16px">
    <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:16px">Új kategória</div>
    <form method="POST" action="{{ route('admin.categories.store') }}" style="display:grid;grid-template-columns:1fr 1fr auto auto auto;gap:8px;align-items:flex-end">
        @csrf
        <div><label class="form-label">Név</label><input type="text" name="name" class="form-input" required></div>
        <div><label class="form-label">Slug (azonosító)</label><input type="text" name="slug" class="form-input" placeholder="pl. PATROL" required></div>
        <div><label class="form-label">Szín</label><input type="color" name="color" class="form-input" value="#6b7280" style="height:38px;padding:4px;width:60px"></div>
        <div><label class="form-label">Sorrend</label><input type="number" name="sort_order" class="form-input" value="0" style="width:70px"></div>
        <button type="submit" class="btn btn-primary">Hozzáadás</button>
    </form>
</div>
<div class="card">
    <table class="table">
        <thead><tr><th>Név</th><th>Slug</th><th>Szín</th><th>Sorrend</th><th></th></tr></thead>
        <tbody>
        @foreach($categories as $cat)
        <tr>
            <form method="POST" action="{{ route('admin.categories.update', $cat->id) }}" style="display:contents">
                @csrf @method('PUT')
                <td><input type="text" name="name" class="form-input" value="{{ $cat->name }}" style="font-size:13px;padding:4px 8px"></td>
                <td><input type="text" name="slug" class="form-input" value="{{ $cat->slug }}" style="font-size:13px;padding:4px 8px"></td>
                <td><input type="color" name="color" class="form-input" value="{{ $cat->color }}" style="height:32px;padding:2px;width:60px"></td>
                <td><input type="number" name="sort_order" class="form-input" value="{{ $cat->sort_order }}" style="font-size:13px;padding:4px 8px;width:70px"></td>
                <td style="display:flex;gap:4px">
                    <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:4px 8px">Mentés</button>
            </form>
                    <form method="POST" action="{{ route('admin.categories.destroy', $cat->id) }}" onsubmit="return confirm('Törlés?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 8px">Törlés</button>
                    </form>
                </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>
@endif

{{-- ════ REPORTS ════ --}}
<div id="panel-reports" class="admin-panel">
@php
    $statusLabels = ['DRAFT'=>'Vázlat','SUBMITTED'=>'Beküldve','APPROVED'=>'Jóváhagyva','REJECTED'=>'Elutasítva'];
    $statusColors = ['DRAFT'=>'badge-gray','SUBMITTED'=>'badge-yellow','APPROVED'=>'badge-green','REJECTED'=>'badge-red'];
@endphp
<div class="card" style="margin-bottom:12px;padding:10px 16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <span style="color:#4a5568;font-size:13px">Szűrő:</span>
    @foreach([''=>'Összes','SUBMITTED'=>'Beküldve','APPROVED'=>'Jóváhagyva','REJECTED'=>'Elutasítva','DRAFT'=>'Vázlat'] as $s=>$l)
    <button type="button" class="btn btn-ghost filter-btn" data-status="{{ $s }}" style="font-size:11px;padding:4px 10px" onclick="filterReports('{{ $s }}', this)">{{ $l }}</button>
    @endforeach
</div>
<div class="card" style="padding:0;overflow:hidden">
<div style="overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:13px">
    <thead><tr style="border-bottom:1px solid #1e2d3d">
        <th style="padding:10px 16px;text-align:left;color:#4a5568;font-weight:500">Cím</th>
        <th style="padding:10px 16px;text-align:left;color:#4a5568;font-weight:500">Szerző</th>
        <th style="padding:10px 16px;text-align:left;color:#4a5568;font-weight:500">Állapot</th>
        <th style="padding:10px 16px;text-align:left;color:#4a5568;font-weight:500">Dátum</th>
        <th style="padding:10px 16px;text-align:right;color:#4a5568;font-weight:500"></th>
    </tr></thead>
    <tbody>
    @forelse($allReports as $r)
    <tr class="report-row" data-status="{{ $r->status }}" style="border-bottom:1px solid #111c2a;transition:background .12s"
        onmouseover="this.style.background='#0d1826'" onmouseout="this.style.background=''">
        <td style="padding:10px 16px;color:#fff;font-weight:500;max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $r->title }}</td>
        <td style="padding:10px 16px;color:#8b949e">{{ $r->author->in_game_name ?? $r->author->name ?? '—' }}</td>
        <td style="padding:10px 16px"><span class="status-badge {{ $statusColors[$r->status] ?? 'badge-gray' }}">{{ $statusLabels[$r->status] ?? $r->status }}</span></td>
        <td style="padding:10px 16px;color:#4a5568;font-size:12px">{{ $r->created_at->format('Y.m.d H:i') }}</td>
        <td style="padding:10px 16px;text-align:right"><a href="{{ route('jelentesek.show', $r->id) }}" class="btn btn-ghost" style="font-size:11px;padding:4px 10px">Megnyit →</a></td>
    </tr>
    @empty
    <tr><td colspan="5" style="padding:32px;text-align:center;color:#4a5568">Nincsenek jelentések.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
</div>
</div>

@if(auth()->user()->is_admin)

{{-- ════ SETTINGS ════ --}}
<div id="panel-settings" class="admin-panel">
<div class="card">
    <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:16px">Általános beállítások</div>
    <form method="POST" action="/admin/settings">
        @csrf @method('PATCH')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div><label class="form-label">Frakció neve</label><input type="text" name="name" class="form-input" value="{{ $factionSettings->name }}" required></div>
            <div><label class="form-label">Fejléc szöveg</label><input type="text" name="header_text" class="form-input" value="{{ $factionSettings->header_text }}"></div>
            <div>
                <label class="form-label">Logo URL</label>
                <input type="text" name="logo_url" class="form-input" value="{{ $factionSettings->logo_url }}">
                <div style="margin-top:6px"><label class="form-label">Feltöltés:</label><input type="file" accept="image/*" onchange="uploadFile(this, 'logo_url')"></div>
            </div>
            <div>
                <label class="form-label">Favicon URL</label>
                <input type="text" name="favicon_url" class="form-input" value="{{ $factionSettings->favicon_url }}">
                <div style="margin-top:6px"><label class="form-label">Feltöltés:</label><input type="file" accept="image/*" onchange="uploadFile(this, 'favicon_url')"></div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Mentés</button>
    </form>
</div>
</div>

{{-- ════ DISCORD ════ --}}
<div id="panel-discord" class="admin-panel">
@if($errors->has('discord'))
<div class="alert alert-red" style="margin-bottom:16px">{{ $errors->first('discord') }}</div>
@endif
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start">
<div class="card">
    <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #1e2d3d">
        <div style="color:#fff;font-size:14px;font-weight:600;margin-bottom:10px">Meglévő embed szerkesztése</div>
        <div style="display:flex;gap:8px;margin-bottom:8px">
            <input type="text" id="loadChannelId" class="form-input" placeholder="Csatorna ID" style="flex:1">
            <button type="button" class="btn btn-ghost" style="white-space:nowrap" onclick="loadMessages()">Betöltés</button>
        </div>
        <div id="messageList" style="display:none;max-height:200px;overflow-y:auto;background:#010409;border:1px solid #1e2d3d;border-radius:6px">
            <div style="color:#4a5568;font-size:12px;padding:8px 12px" id="messageListInner"></div>
        </div>
        <div id="editingBadge" style="display:none;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);color:#f59e0b;font-size:12px;border-radius:6px;padding:6px 12px;margin-top:8px">
            ✏️ Szerkesztési mód
            <button type="button" onclick="clearEdit()" style="margin-left:8px;background:none;border:none;color:#f59e0b;cursor:pointer;font-size:11px">× Új embed</button>
        </div>
    </div>
    <div style="color:#fff;font-size:14px;font-weight:600;margin-bottom:16px">Embed szerkesztő</div>
    <form method="POST" action="{{ route('admin.discord-embed') }}" id="embedForm">
        @csrf
        <input type="hidden" name="message_id" id="editMessageId">
        <div style="margin-bottom:14px">
            <label class="form-label">Csatorna</label>
            @if(count($discordChannels))
            <select class="form-select" style="margin-bottom:6px" onchange="document.getElementById('channelIdInput').value=this.value;updatePreview()">
                <option value="">— Válassz csatornát —</option>
                @foreach($discordChannels as $ch)<option value="{{ $ch['id'] }}">#{{ $ch['name'] }}</option>@endforeach
            </select>
            <div style="color:#4a5568;font-size:11px;margin-bottom:4px">Vagy adj meg kézzel:</div>
            @endif
            <input type="text" name="channel_id" id="channelIdInput" class="form-input" placeholder="Csatorna ID" required>
        </div>
        <div style="margin-bottom:14px;display:flex;align-items:flex-end;gap:10px">
            <div><label class="form-label">Szín</label><input type="color" name="color" id="embedColor" class="form-input" value="#34d399" style="height:38px;padding:4px;width:64px" oninput="updatePreview()"></div>
            <span style="color:#4a5568;font-size:12px;padding-bottom:10px">Bal oldali csík</span>
        </div>
        <div style="margin-bottom:14px"><label class="form-label">Szerző</label><input type="text" name="author_name" id="embedAuthor" class="form-input" maxlength="256" oninput="updatePreview()"></div>
        <div style="margin-bottom:14px"><label class="form-label">Cím</label><input type="text" name="title" id="embedTitle" class="form-input" maxlength="256" oninput="updatePreview()"></div>
        <div style="margin-bottom:14px"><label class="form-label">Leírás</label><textarea name="description" id="embedDesc" class="form-textarea" rows="5" maxlength="4096" oninput="updatePreview()"></textarea></div>
        <div style="margin-bottom:14px"><label class="form-label">Bélyegkép URL</label><input type="text" name="thumbnail_url" id="embedThumb" class="form-input" oninput="updatePreview()"></div>
        <div style="margin-bottom:14px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <span class="form-label" style="margin:0">Mezők</span>
                <button type="button" onclick="addField()" class="btn btn-ghost" style="font-size:11px;padding:3px 10px">+ Mező</button>
            </div>
            <div id="fieldsContainer"></div>
        </div>
        <div style="margin-bottom:14px"><label class="form-label">Nagy kép URL</label><input type="text" name="image_url" id="embedImage" class="form-input" oninput="updatePreview()"></div>
        <div style="margin-bottom:20px"><label class="form-label">Lábléc</label><input type="text" name="footer_text" id="embedFooter" class="form-input" maxlength="2048" oninput="updatePreview()"></div>
        <button type="submit" class="btn btn-primary">Küldés Discord-ra</button>
    </form>
</div>
<div style="position:sticky;top:20px">
    <div class="card">
        <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:12px">Előnézet</div>
        <div style="background:#313338;border-radius:8px;padding:16px;min-height:80px">
            <div id="prev-empty" style="color:#4a5568;font-size:13px;text-align:center;padding:20px 0">Töltsd ki a mezőket az előnézethez</div>
            <div id="embedPreview" style="display:none;background:#2b2d31;border-radius:4px;overflow:hidden;border-left:4px solid #34d399;padding:14px 16px">
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <div style="flex:1;min-width:0">
                        <div id="prev-author" style="color:#b5bac1;font-size:12px;font-weight:600;margin-bottom:6px;display:none"></div>
                        <div id="prev-title"  style="color:#fff;font-size:16px;font-weight:700;margin-bottom:6px;display:none"></div>
                        <div id="prev-desc"   style="color:#dbdee1;font-size:14px;line-height:1.55;margin-bottom:10px;white-space:pre-wrap;display:none"></div>
                        <div id="prev-fields" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px"></div>
                        <div id="prev-image"  style="margin-bottom:10px;display:none"><img id="prev-image-src" src="" style="max-width:100%;border-radius:4px"></div>
                        <div id="prev-footer" style="color:#b5bac1;font-size:12px;display:none"></div>
                    </div>
                    <div id="prev-thumb" style="display:none;flex-shrink:0"><img id="prev-thumb-src" src="" style="width:80px;height:80px;border-radius:4px;object-fit:cover"></div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

{{-- ════ SYNC ════ --}}
<div id="panel-sync" class="admin-panel">
<div class="card">
    <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:8px">Discord szinkronizáció</div>
    <p style="color:#8b949e;font-size:13px;margin-bottom:12px">Lekéri az összes szerver tagot Discord-ról és frissíti/létrehozza a fiókjukat.</p>
    <div class="alert alert-yellow" style="margin-bottom:16px">
        <strong>Előfeltétel:</strong> Minden ranghoz be kell állítani a Discord szerepkör ID-t a <strong>Rangok</strong> szekcióban.
    </div>
    <form method="POST" action="{{ route('admin.sync') }}">@csrf<button type="submit" class="btn btn-primary">Szinkronizálás indítása</button></form>
</div>
</div>

{{-- ════ MESSAGES ════ --}}
<div id="panel-messages" class="admin-panel">
<div class="card">
    <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:12px">Intranet üzenetek (legutóbbi 50)</div>
    @forelse($messages as $msg)
    <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid #1e2d3d">
        <div style="flex:1">
            <div style="color:#8b949e;font-size:12px;margin-bottom:4px">{{ $msg->author->name }} · {{ $msg->created_at->diffForHumans() }}</div>
            <div style="color:#fff;font-size:13px">{{ $msg->content }}</div>
        </div>
        <form method="POST" action="/admin/messages/{{ $msg->id }}" onsubmit="return confirm('Törlés?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 8px">Törlés</button>
        </form>
    </div>
    @empty
    <p style="color:#4a5568;font-size:14px">Nincsenek üzenetek.</p>
    @endforelse
</div>
</div>

@endif {{-- end admin-only panels --}}
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/easymde/2.18.0/easymde.min.js"></script>
<script>
// ── Panel switching ───────────────────────────────────────────────────────────
const _defaultPanel = '{{ auth()->user()->is_admin ? "users" : "reports" }}';
let _annMde = null;

function showPanel(name) {
    document.querySelectorAll('.admin-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.subnav-link').forEach(b => b.classList.remove('active'));

    const panel = document.getElementById('panel-' + name);
    if (panel) panel.classList.add('active');
    document.querySelectorAll('.subnav-link[onclick="showPanel(\'' + name + '\')"]').forEach(b => b.classList.add('active'));

    history.replaceState(null, '', '?tab=' + name);

    // Lazy-init EasyMDE for announcements when that panel first becomes visible
    if (name === 'announcements' && !_annMde) {
        const el = document.getElementById('annContent');
        if (el) {
            _annMde = new EasyMDE({
                element: el,
                spellChecker: false,
                status: false,
                placeholder: 'Tartalom (Markdown: **félkövér**, *dőlt*, # Cím, - Lista...)',
                toolbar: ['bold','italic','heading','|','quote','unordered-list','ordered-list','|','link','|','preview'],
            });
        }
    }
}

// Activate on page load from ?tab= param
const _initTab = new URLSearchParams(location.search).get('tab') || _defaultPanel;
showPanel(_initTab);

// ── Users ─────────────────────────────────────────────────────────────────────
function toggleNewUser() {
    const form    = document.getElementById('newUserForm');
    const chevron = document.getElementById('newUserChevron');
    const open    = form.style.display !== 'none';
    form.style.display      = open ? 'none' : '';
    chevron.style.transform = open ? '' : 'rotate(180deg)';
}

function toggleUserEdit(userId) {
    const row    = document.getElementById('edit_row_' + userId);
    const isOpen = row.style.display !== 'none';
    document.querySelectorAll('[id^="edit_row_"]').forEach(r => r.style.display = 'none');
    if (!isOpen) row.style.display = '';
}

function filterUsers() {
    const q = document.getElementById('userSearch').value.toLowerCase().trim();
    document.querySelectorAll('tr.user-row').forEach(row => {
        const matches = !q || (row.dataset.search || '').includes(q);
        row.style.display = matches ? '' : 'none';
        const next = row.nextElementSibling;
        if (next && next.id && next.id.startsWith('edit_row_') && !matches) next.style.display = 'none';
    });
}

function filterDeptRanks(userId) {
    const deptId  = document.getElementById('dept_' + userId).value;
    const rankSel = document.getElementById('deptrank_' + userId);
    rankSel.querySelectorAll('option').forEach(opt => {
        if (!opt.value) return;
        opt.style.display = opt.dataset.dept === deptId ? '' : 'none';
    });
    rankSel.value = '';
}
document.querySelectorAll('[id^="dept_"]').forEach(sel => filterDeptRanks(sel.id.replace('dept_', '')));

// ── Reports filter ────────────────────────────────────────────────────────────
function filterReports(status, btn) {
    document.querySelectorAll('tr.report-row').forEach(row => {
        row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
    });
    document.querySelectorAll('.filter-btn').forEach(b => b.style.fontWeight = '');
    if (btn) btn.style.fontWeight = '700';
}

// ── File upload ───────────────────────────────────────────────────────────────
function uploadFile(input, fieldName) {
    const fd = new FormData();
    fd.append('file', input.files[0]);
    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
    fetch('/upload', {method:'POST', body:fd}).then(r => r.json()).then(data => {
        input.closest('form').querySelector('[name="' + fieldName + '"]').value = data.url;
    });
}

// ── Discord embed editor ──────────────────────────────────────────────────────
let fieldIdx = 0;
function addField() {
    const i = fieldIdx++;
    const div = document.createElement('div');
    div.id = 'field_row_' + i;
    div.style.cssText = 'display:grid;grid-template-columns:1fr 1fr auto auto;gap:4px;margin-bottom:6px;align-items:center';
    div.innerHTML = `<input type="text" name="fields[${i}][name]" class="form-input" placeholder="Mező neve" style="font-size:12px" oninput="updatePreview()">
        <input type="text" name="fields[${i}][value]" class="form-input" placeholder="Mező értéke" style="font-size:12px" oninput="updatePreview()">
        <label style="display:flex;align-items:center;gap:4px;font-size:11px;color:#8b949e;white-space:nowrap;padding:0 4px"><input type="checkbox" name="fields[${i}][inline]" value="1" onchange="updatePreview()"> Egy sor</label>
        <button type="button" onclick="removeField(${i})" class="btn btn-danger" style="font-size:12px;padding:4px 8px;line-height:1">×</button>`;
    document.getElementById('fieldsContainer').appendChild(div);
    updatePreview();
}
function removeField(i) { const el = document.getElementById('field_row_'+i); if(el){el.remove();updatePreview();} }
function updatePreview() {
    const color  = document.getElementById('embedColor')?.value  || '#34d399';
    const author = document.getElementById('embedAuthor')?.value.trim() || '';
    const title  = document.getElementById('embedTitle')?.value.trim()  || '';
    const desc   = document.getElementById('embedDesc')?.value.trim()   || '';
    const thumb  = document.getElementById('embedThumb')?.value.trim()  || '';
    const image  = document.getElementById('embedImage')?.value.trim()  || '';
    const footer = document.getElementById('embedFooter')?.value.trim() || '';
    const hasContent = author||title||desc||thumb||image||footer||(document.getElementById('fieldsContainer')?.children.length>0);
    const previewEl = document.getElementById('embedPreview');
    const emptyEl   = document.getElementById('prev-empty');
    if (!previewEl) return;
    previewEl.style.display = hasContent ? '' : 'none';
    emptyEl.style.display   = hasContent ? 'none' : '';
    previewEl.style.borderLeftColor = color;
    const show = (id, text) => { const el=document.getElementById(id); if(!el)return; el.style.display=text?'':'none'; el.textContent=text; };
    show('prev-author',author); show('prev-title',title); show('prev-desc',desc); show('prev-footer',footer);
    const pThumb = document.getElementById('prev-thumb');
    if(pThumb){pThumb.style.display=thumb?'':'none';document.getElementById('prev-thumb-src').src=thumb;}
    const pImage = document.getElementById('prev-image');
    if(pImage){pImage.style.display=image?'':'none';document.getElementById('prev-image-src').src=image;}
    const pFields = document.getElementById('prev-fields');
    if(!pFields) return;
    pFields.innerHTML='';
    document.querySelectorAll('#fieldsContainer > div').forEach(row => {
        const inputs=row.querySelectorAll('input[type=text]');
        const fname=inputs[0]?.value.trim()||''; const fval=inputs[1]?.value.trim()||'';
        const finline=row.querySelector('input[type=checkbox]')?.checked;
        if(!fname&&!fval) return;
        const el=document.createElement('div');
        el.style.cssText='min-width:'+(finline?'45%':'100%')+';flex:'+(finline?'1':'0 0 100%');
        el.innerHTML='<div style="color:#b5bac1;font-size:12px;font-weight:700;margin-bottom:3px">'+fname+'</div><div style="color:#dbdee1;font-size:13px">'+fval+'</div>';
        pFields.appendChild(el);
    });
}
if (document.getElementById('embedPreview')) updatePreview();

async function loadMessages() {
    const channelId = document.getElementById('loadChannelId').value.trim();
    if (!channelId) return;
    const list=document.getElementById('messageList'); const inner=document.getElementById('messageListInner');
    list.style.display=''; inner.innerHTML='<span style="color:#8b949e;padding:8px 12px;display:block">Betöltés...</span>';
    try {
        const res = await fetch('{{ route('admin.discord-messages') }}?channel_id='+encodeURIComponent(channelId),{headers:{'Accept':'application/json'}});
        const data = await res.json();
        if(data.error){inner.innerHTML='<span style="color:#f87171;padding:8px 12px;display:block">'+data.error+'</span>';return;}
        if(!data.length){inner.innerHTML='<span style="color:#4a5568;padding:8px 12px;display:block">Nincs embed üzenet.</span>';return;}
        inner.innerHTML=data.map(m=>{
            const e=m.embed||{};const ts=m.timestamp?new Date(m.timestamp).toLocaleString('hu'):'';
            const label=(e.title||e.description||'(cím nélkül)').substring(0,60);
            const encoded=encodeURIComponent(JSON.stringify(m));
            return '<div onclick="loadEmbed(decodeURIComponent(\''+encoded+'\'),\''+channelId+'\')" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid #1e2d3d;font-size:13px;color:#fff" onmouseover="this.style.background=\'#1a2332\'" onmouseout="this.style.background=\'\'"><div style="font-weight:500">'+label+'</div><div style="color:#4a5568;font-size:11px">'+m.author+' · '+ts+'</div></div>';
        }).join('');
    } catch(e) { inner.innerHTML='<span style="color:#f87171;padding:8px 12px;display:block">Hálózati hiba.</span>'; }
}
function loadEmbed(msgJson, channelId) {
    const m=typeof msgJson==='string'?JSON.parse(msgJson):msgJson; const e=m.embed||{};
    document.getElementById('channelIdInput').value=channelId;
    document.getElementById('editMessageId').value=m.id;
    if(e.color!==undefined) document.getElementById('embedColor').value='#'+e.color.toString(16).padStart(6,'0');
    document.getElementById('embedAuthor').value=e.author?.name||'';
    document.getElementById('embedTitle').value=e.title||'';
    document.getElementById('embedDesc').value=e.description||'';
    document.getElementById('embedThumb').value=e.thumbnail?.url||'';
    document.getElementById('embedImage').value=e.image?.url||'';
    document.getElementById('embedFooter').value=e.footer?.text||'';
    document.getElementById('fieldsContainer').innerHTML=''; fieldIdx=0;
    (e.fields||[]).forEach(f=>{addField();const row=document.getElementById('field_row_'+(fieldIdx-1));if(row){row.querySelectorAll('input[type=text]')[0].value=f.name||'';row.querySelectorAll('input[type=text]')[1].value=f.value||'';if(f.inline)row.querySelector('input[type=checkbox]').checked=true;}});
    document.getElementById('editingBadge').style.display='';
    document.getElementById('messageList').style.display='none';
    updatePreview();
    document.getElementById('embedForm').scrollIntoView({behavior:'smooth',block:'start'});
}
function clearEdit() {
    document.getElementById('editMessageId').value='';
    document.getElementById('editingBadge').style.display='none';
    ['embedAuthor','embedTitle','embedDesc','embedThumb','embedImage','embedFooter'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});
    document.getElementById('embedColor').value='#34d399';
    document.getElementById('fieldsContainer').innerHTML=''; fieldIdx=0;
    updatePreview();
}
</script>
@endpush

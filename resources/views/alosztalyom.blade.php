@extends('layouts.app')
@section('title', 'Alosztályom')
@section('content')
<h1 style="color:#fff;font-size:24px;font-weight:700;margin-bottom:24px">Alosztályom</h1>

@if(!$department)
<div class="card"><p style="color:#4a5568;font-size:14px">Nem vagy besorolva egyetlen alosztályba sem.</p></div>
@else
@php $members = $department->members; @endphp

<div class="card" style="display:flex;align-items:center;gap:16px;margin-bottom:16px">
    @if($department->logo_url)
        <img src="{{ $department->logo_url }}" style="width:56px;height:56px;border-radius:50%;object-fit:cover">
    @else
        <div style="width:56px;height:56px;border-radius:50%;background:#1a2332;display:flex;align-items:center;justify-content:center;color:#34d399;font-size:20px;font-weight:700">{{ strtoupper(substr($department->short_name,0,1)) }}</div>
    @endif
    <div>
        <div style="color:#fff;font-size:18px;font-weight:700">{{ $department->name }}</div>
        <div style="display:flex;gap:8px;margin-top:4px">
            <span class="badge badge-gray">{{ $department->short_name }}</span>
            <span class="badge badge-blue">{{ $members->count() }} / {{ $department->max_members ?: '∞' }} fő</span>
            @if(auth()->user()->is_department_leader) <span class="badge badge-yellow">Vezető</span>
            @elseif(auth()->user()->is_department_deputy) <span class="badge badge-gray">Helyettes</span>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-green">{{ session('success') }}</div>
@endif

<div data-tabs>
    <div class="tabs">
        <button class="tab-btn active" data-tab="tagok">Tagok</button>
        <button class="tab-btn" data-tab="szabalyzat">Szabályzat</button>
        @if($canManage)
        <button class="tab-btn" data-tab="admin">Admin</button>
        @endif
    </div>

    {{-- TAGOK --}}
    <div class="tab-panel active" data-panel="tagok">
        <div class="card">
            <table class="table">
                <thead><tr><th>Név</th><th>Alosztályi rang</th><th>Frakció rang</th>@if($canManage)<th></th>@endif</tr></thead>
                <tbody>
                @foreach($members as $m)
                <tr>
                    <td style="color:#fff">
                        <div style="display:flex;align-items:center;gap:8px">
                            @if($m->avatar)<img src="{{ $m->avatar }}" style="width:28px;height:28px;border-radius:50%">
                            @else<div style="width:28px;height:28px;border-radius:50%;background:#1a2332;display:flex;align-items:center;justify-content:center;color:#34d399;font-size:11px;font-weight:700">{{ strtoupper(substr($m->name,0,1)) }}</div>
                            @endif
                            {{ $m->in_game_name ?? $m->name }}
                        </div>
                    </td>
                    <td>{{ $m->departmentRank?->name ?? '—' }}</td>
                    <td>
                        @if($m->rank)
                        <span class="badge" style="background:{{ $m->rank->color }}22;color:{{ $m->rank->color }}">{{ $m->rank->name }}</span>
                        @else —
                        @endif
                    </td>
                    @if($canManage)
                    <td>
                        <form method="POST" action="/alosztalyom/members/{{ $m->id }}" style="display:flex;gap:4px;align-items:center">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="dept_id" value="{{ $department->id }}">
                            <select name="department_rank_id" class="form-select" style="padding:4px 8px;font-size:12px;width:auto">
                                <option value="">— rang —</option>
                                @foreach($department->ranks as $dr)
                                <option value="{{ $dr->id }}" {{ $m->department_rank_id===$dr->id?'selected':'' }}>{{ $dr->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:4px 8px">Mentés</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- SZABÁLYZAT --}}
    <div class="tab-panel" data-panel="szabalyzat">
        @if($canManage)
        <div class="card" style="margin-bottom:16px">
            <form method="POST" action="/alosztalyom/rules">
                @csrf
                <input type="hidden" name="dept_id" value="{{ $department->id }}">
                <div style="margin-bottom:12px">
                    <label class="form-label">Szabályzat szövege</label>
                    <textarea name="rules" class="form-textarea" rows="12">{{ strip_tags($department->rules ?? '') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Mentés</button>
            </form>
        </div>
        @endif
        @if($department->rules)
        <div class="card">
            <div style="color:#8b949e;font-size:14px;line-height:1.7">{!! $department->rules !!}</div>
        </div>
        @else
        <div class="card"><p style="color:#4a5568;font-size:14px">Még nincs szabályzat.</p></div>
        @endif
    </div>

    {{-- ADMIN --}}
    @if($canManage)
    <div class="tab-panel" data-panel="admin">
        <div class="card" style="margin-bottom:16px">
            <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:16px">Alosztályi rangok</div>
            <table class="table" style="margin-bottom:16px">
                <thead><tr><th>Név</th><th>Szint</th><th></th></tr></thead>
                <tbody>
                @foreach($department->ranks as $dr)
                <tr>
                    <form method="POST" action="/alosztalyom/ranks/{{ $dr->id }}" style="display:contents">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="dept_id" value="{{ $department->id }}">
                        <td><input type="text" name="name" class="form-input" value="{{ $dr->name }}" style="padding:4px 8px;font-size:13px"></td>
                        <td><input type="number" name="level" class="form-input" value="{{ $dr->level }}" style="padding:4px 8px;font-size:13px;width:80px"></td>
                        <td style="display:flex;gap:4px">
                            <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:4px 8px">Mentés</button>
                    </form>
                            <form method="POST" action="/alosztalyom/ranks/{{ $dr->id }}" onsubmit="return confirm('Törlés?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="dept_id" value="{{ $department->id }}">
                                <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 8px">Törlés</button>
                            </form>
                        </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            <form method="POST" action="/alosztalyom/ranks" style="display:flex;gap:8px;align-items:flex-end">
                @csrf
                <input type="hidden" name="dept_id" value="{{ $department->id }}">
                <div><label class="form-label">Név</label><input type="text" name="name" class="form-input" required></div>
                <div><label class="form-label">Szint</label><input type="number" name="level" class="form-input" value="0" style="width:80px"></div>
                <button type="submit" class="btn btn-primary">Hozzáadás</button>
            </form>
        </div>
    </div>
    @endif
</div>
@endif
@endsection

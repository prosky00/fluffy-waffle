@extends('layouts.app')
@section('title', $announcement->title)
@section('content')
<a href="{{ route('dashboard') }}" class="btn btn-ghost" style="margin-bottom:16px;display:inline-flex">← Vissza</a>
<div class="card">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border)">
        @if($announcement->author->avatar)
            <img src="{{ $announcement->author->avatar }}" style="width:40px;height:40px;border-radius:50%">
        @else
            <div style="width:40px;height:40px;border-radius:50%;background:var(--surface-3);display:flex;align-items:center;justify-content:center;color:var(--accent);font-weight:700">{{ strtoupper(substr($announcement->author->name,0,1)) }}</div>
        @endif
        <div>
            <div style="color:var(--fg);font-size:18px;font-weight:700">{{ $announcement->title }}</div>
            <div style="color:var(--fg-subtle);font-size:12px">{{ $announcement->author->name }} · {{ $announcement->created_at->format('Y. m. d. H:i') }}</div>
        </div>
    </div>
    <div style="color:var(--fg-muted);font-size:14px;line-height:1.7">{!! $announcement->content !!}</div>
</div>
@endsection

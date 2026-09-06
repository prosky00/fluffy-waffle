@extends('layouts.app')
@section('title', $announcement->title)
@section('content')
<a href="{{ route('dashboard') }}" class="btn btn-ghost" style="margin-bottom:16px;display:inline-flex">← Vissza</a>
<div class="card">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #1e2d3d">
        @if($announcement->author->avatar)
            <img src="{{ $announcement->author->avatar }}" style="width:40px;height:40px;border-radius:50%">
        @else
            <div style="width:40px;height:40px;border-radius:50%;background:#1a2332;display:flex;align-items:center;justify-content:center;color:#34d399;font-weight:700">{{ strtoupper(substr($announcement->author->name,0,1)) }}</div>
        @endif
        <div>
            <div style="color:#fff;font-size:18px;font-weight:700">{{ $announcement->title }}</div>
            <div style="color:#4a5568;font-size:12px">{{ $announcement->author->name }} · {{ $announcement->created_at->format('Y. m. d. H:i') }}</div>
        </div>
    </div>
    <div style="color:#8b949e;font-size:14px;line-height:1.7">{!! $announcement->content !!}</div>
</div>
@endsection

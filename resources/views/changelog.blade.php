@extends('layouts.app')
@section('title', 'Változásnapló')
@section('content')
<h1 style="color:var(--fg);font-size:24px;font-weight:700;margin-bottom:24px">Változásnapló</h1>

@forelse($entries as $entry)
<div class="card" style="margin-bottom:16px">
    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:10px">
        <div style="color:var(--fg);font-size:16px;font-weight:700">{{ $entry->title }}</div>
        <div style="color:var(--fg-subtle);font-size:12px;white-space:nowrap">{{ $entry->created_at->format('Y. m. d.') }}</div>
    </div>
    <div class="md-content" style="color:var(--fg-muted);font-size:14px;line-height:1.65">{{ $entry->content }}</div>
</div>
@empty
<div class="card"><p style="color:var(--fg-subtle);font-size:14px">Még nincsenek bejegyzések.</p></div>
@endforelse
@endsection

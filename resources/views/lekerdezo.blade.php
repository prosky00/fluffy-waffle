@extends('layouts.app')
@section('title', 'Lekérdező')
@section('content')
<h1 style="color:#fff;font-size:24px;font-weight:700;margin-bottom:24px">Lekérdező</h1>

<div class="card" style="margin-bottom:16px">
    <form method="GET" action="{{ route('lekerdezo') }}" style="display:flex;gap:8px">
        <input type="text" name="q" class="form-input" placeholder="Keresés cím szerint..." value="{{ $query }}" style="flex:1">
        <button type="submit" class="btn btn-primary">Keresés</button>
    </form>
</div>

@if($query !== null)
    @if($reports->count())
    <div class="card">
        @foreach($reports as $report)
        @php
            $statusLabels = ['DRAFT'=>'Vázlat','SUBMITTED'=>'Beküldve','APPROVED'=>'Jóváhagyva','REJECTED'=>'Elutasítva'];
            $statusColors = ['DRAFT'=>'badge-gray','SUBMITTED'=>'badge-yellow','APPROVED'=>'badge-green','REJECTED'=>'badge-red'];
        @endphp
        <div style="padding:12px 0;border-bottom:1px solid #1e2d3d">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <a href="{{ route('jelentesek.show', $report->id) }}" style="color:#fff;font-size:14px;font-weight:500">{{ $report->title }}</a>
                <span class="badge {{ $statusColors[$report->status] }}">{{ $statusLabels[$report->status] }}</span>
            </div>
            <div style="color:#4a5568;font-size:12px">{{ $report->author->in_game_name ?? $report->author->name }} · {{ $report->created_at->format('Y. m. d.') }}</div>
        </div>
        @endforeach
        <div style="margin-top:16px">{{ $reports->links() }}</div>
    </div>
    @else
    <div class="card"><p style="color:#4a5568;font-size:14px">Nincs találat: „{{ $query }}"</p></div>
    @endif
@endif
@endsection

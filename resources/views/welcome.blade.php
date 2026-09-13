@extends('layouts.guest')

@section('content')
@if(session('success') || $errors->any())
<div class="page-content" style="padding-bottom:0">
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error" role="alert">{{ $errors->first() }}</div>@endif
</div>
@endif

@foreach($sections as $section)
    @php $d = $section->data; @endphp

    @if($section->type === 'banner')
        @php $variantClass = ['dark' => 'strip-dark', 'light' => 'strip-light', 'accent' => 'strip-accent'][$d['variant']] ?? 'strip-dark'; @endphp
        <div class="strip {{ $variantClass }}">
            @if(!empty($d['url']))
                <a href="{{ $d['url'] }}">{{ $d['text'] }}</a>
            @else
                {{ $d['text'] }}
            @endif
        </div>

    @elseif($section->type === 'hero')
        <div class="hero-band">
            @if(!empty($d['eyebrow']))<div class="eyebrow">{{ $d['eyebrow'] }}</div>@endif
            <div class="title">{{ $d['title'] }}</div>
        </div>
        @if(!empty($d['image_url']))
            <img src="{{ $d['image_url'] }}" alt="" class="hero-image">
        @endif

    @elseif($section->type === 'richtext')
        <div class="page-content" style="text-align:center;max-width:760px">
            @if(!empty($d['show_seal']) && $settings->logo_url)
                <img src="{{ $settings->logo_url }}" alt="" style="width:120px;height:120px;border-radius:50%;object-fit:cover;margin:0 auto 24px">
            @endif
            @if(!empty($d['heading']))<h2 style="font-size:20px;font-weight:900;margin-bottom:12px">{{ $d['heading'] }}</h2>@endif
            <p style="font-size:15px;line-height:1.7;color:var(--color-text-muted);white-space:pre-line">{{ $d['body'] }}</p>
        </div>

    @elseif($section->type === 'steps')
        <div class="page-content">
            <h2 style="text-align:center;font-size:20px;font-weight:900;margin-bottom:28px">{{ $d['heading'] }}</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-4)">
                @foreach($d['items'] as $i => $item)
                <div class="card">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--color-red);color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;margin-bottom:var(--space-3)">{{ $i + 1 }}</div>
                    <h3 style="font-size:15px;font-weight:700;margin-bottom:6px">{{ $item['title'] }}</h3>
                    <p style="font-size:13px;line-height:1.6;color:var(--color-text-muted)">{{ $item['body'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    @endif
@endforeach

@guest
<div class="page-content" style="text-align:center;padding-top:0">
    <a href="{{ route('register') }}" class="btn btn-primary" style="margin-right:8px">Fiók létrehozása</a>
    <a href="{{ route('login') }}" class="btn btn-ghost">Bejelentkezés</a>
</div>
@endguest
@endsection

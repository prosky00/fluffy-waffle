@if ($paginator->hasPages())
<div style="display:flex;align-items:center;gap:8px;margin-top:12px;flex-wrap:wrap">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span style="padding:6px 12px;border-radius:6px;border:1px solid #1e2d3d;color:#4a5568;font-size:13px">← Előző</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" style="padding:6px 12px;border-radius:6px;border:1px solid #1e2d3d;color:#8b949e;font-size:13px;text-decoration:none">← Előző</a>
    @endif

    {{-- Page numbers --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span style="color:#4a5568;font-size:13px">…</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span style="padding:6px 12px;border-radius:6px;background:#1a2332;color:#34d399;font-size:13px;border:1px solid #34d399">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" style="padding:6px 12px;border-radius:6px;border:1px solid #1e2d3d;color:#8b949e;font-size:13px;text-decoration:none">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" style="padding:6px 12px;border-radius:6px;border:1px solid #1e2d3d;color:#8b949e;font-size:13px;text-decoration:none">Következő →</a>
    @else
        <span style="padding:6px 12px;border-radius:6px;border:1px solid #1e2d3d;color:#4a5568;font-size:13px">Következő →</span>
    @endif

    <span style="color:#4a5568;font-size:12px;margin-left:8px">{{ $paginator->total() }} találat</span>
</div>
@endif

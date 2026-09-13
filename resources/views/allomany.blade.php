@extends('layouts.app')
@section('title', 'Állomány')
@section('content')
<h1 style="color:var(--fg);font-size:24px;font-weight:700;margin-bottom:24px">Állomány</h1>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:16px">
        <span style="color:var(--fg-subtle);font-size:13px;white-space:nowrap">{{ $members->count() }} aktív tag</span>
        <input type="text" id="allomanySearch" class="form-input" placeholder="Keresés karakter név, rendfokozat vagy alosztály szerint..." oninput="filterAllomany()" style="max-width:320px;margin-left:auto">
    </div>

    <div style="overflow-x:auto">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Karakter név</th>
                    <th>Rendfokozat</th>
                    <th>Alosztály</th>
                    <th>Csatlakozás dátuma</th>
                    <th style="text-align:center">Heti jelentések</th>
                    <th>Utolsó előléptetés</th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $i => $m)
                <tr class="allomany-row" data-search="{{ Str::lower($m['character_name'].' '.$m['rank'].' '.$m['departments']) }}">
                    <td>{{ $i + 1 }}</td>
                    <td style="color:var(--fg);font-weight:500">{{ $m['character_name'] }}</td>
                    <td>
                        @if($m['rank'] !== '—')
                            <span class="badge" style="background:{{ $m['rank_color'] }}22;color:{{ $m['rank_color'] }}">{{ $m['rank'] }}</span>
                        @else
                            <span style="color:var(--fg-subtle)">—</span>
                        @endif
                    </td>
                    <td style="color:var(--fg-subtle)">{{ $m['departments'] ?: '—' }}</td>
                    <td>{{ $m['joined_at']->format('Y. m. d.') }}</td>
                    <td style="text-align:center">
                        @if($m['weekly_reports'] > 0)
                            <span class="badge badge-green">{{ $m['weekly_reports'] }}</span>
                        @else
                            <span style="color:var(--fg-subtle)">0</span>
                        @endif
                    </td>
                    <td>
                        {{ $m['last_rankup'] ? $m['last_rankup']->format('Y. m. d.') : '—' }}
                    </td>
                </tr>
                @endforeach

                @if($members->isEmpty())
                <tr>
                    <td colspan="7" style="padding:32px;text-align:center;color:var(--fg-subtle)">Nincs aktív tag.</td>
                </tr>
                @endif
            </tbody>
        </table>
        <p id="allomanyNoResults" style="display:none;padding:32px;text-align:center;color:var(--fg-subtle)">Nincs találat.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function filterAllomany() {
    const q = document.getElementById('allomanySearch').value.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('tr.allomany-row').forEach(row => {
        const matches = !q || (row.dataset.search || '').includes(q);
        row.style.display = matches ? '' : 'none';
        if (matches) visible++;
    });
    document.getElementById('allomanyNoResults').style.display = visible === 0 ? '' : 'none';
}
</script>
@endpush

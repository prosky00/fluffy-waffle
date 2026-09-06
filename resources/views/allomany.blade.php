@extends('layouts.app')
@section('title', 'Állomány')
@section('content')
<h1 style="color:#fff;font-size:24px;font-weight:700;margin-bottom:24px">Állomány</h1>

<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid #1e2d3d;display:flex;align-items:center;justify-content:space-between">
        <span style="color:#4a5568;font-size:13px">{{ $members->count() }} aktív tag</span>
    </div>

    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="border-bottom:1px solid #1e2d3d">
                    <th style="padding:10px 20px;text-align:left;color:#4a5568;font-weight:500">#</th>
                    <th style="padding:10px 20px;text-align:left;color:#4a5568;font-weight:500">Karakter név</th>
                    <th style="padding:10px 20px;text-align:left;color:#4a5568;font-weight:500">Rendfokozat</th>
                    <th style="padding:10px 20px;text-align:left;color:#4a5568;font-weight:500">Csatlakozás dátuma</th>
                    <th style="padding:10px 20px;text-align:center;color:#4a5568;font-weight:500">Heti jelentések</th>
                    <th style="padding:10px 20px;text-align:left;color:#4a5568;font-weight:500">Utolsó előléptetés</th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $i => $m)
                <tr style="border-bottom:1px solid #111c2a;transition:background .12s" onmouseover="this.style.background='#0d1826'" onmouseout="this.style.background=''">
                    <td style="padding:12px 20px;color:#4a5568">{{ $i + 1 }}</td>
                    <td style="padding:12px 20px;color:#fff;font-weight:500">{{ $m['character_name'] }}</td>
                    <td style="padding:12px 20px">
                        @if($m['rank'] !== '—')
                            <span class="badge" style="background:{{ $m['rank_color'] }}22;color:{{ $m['rank_color'] }}">{{ $m['rank'] }}</span>
                        @else
                            <span style="color:#4a5568">—</span>
                        @endif
                    </td>
                    <td style="padding:12px 20px;color:#8b949e">{{ $m['joined_at']->format('Y. m. d.') }}</td>
                    <td style="padding:12px 20px;text-align:center">
                        @if($m['weekly_reports'] > 0)
                            <span class="badge badge-green">{{ $m['weekly_reports'] }}</span>
                        @else
                            <span style="color:#4a5568">0</span>
                        @endif
                    </td>
                    <td style="padding:12px 20px;color:#8b949e">
                        {{ $m['last_rankup'] ? $m['last_rankup']->format('Y. m. d.') : '—' }}
                    </td>
                </tr>
                @endforeach

                @if($members->isEmpty())
                <tr>
                    <td colspan="6" style="padding:32px;text-align:center;color:#4a5568">Nincs aktív tag.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Alosztály adatbázis')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <h1 style="color:#fff;font-size:24px;font-weight:700;flex:1">Alosztály adatbázis</h1>
    <button class="btn btn-primary" onclick="openModal('newDeptModal')">+ Új alosztály</button>
</div>

@if(session('success'))
<div class="alert alert-green">{{ session('success') }}</div>
@endif

<div class="card">
    <table class="table">
        <thead><tr><th>Név</th><th>Rövid</th><th>Tagok</th><th>Rangok</th><th></th></tr></thead>
        <tbody>
        @forelse($departments as $dept)
        <tr>
            <td style="color:#fff">{{ $dept->name }}</td>
            <td>{{ $dept->short_name }}</td>
            <td>{{ $dept->members_count }} / {{ $dept->max_members ?: '∞' }}</td>
            <td>{{ $dept->ranks->count() }}</td>
            <td style="display:flex;gap:4px">
                <button class="btn btn-ghost" style="font-size:11px;padding:4px 8px" onclick="openEdit({{ $dept->id }}, '{{ addslashes($dept->name) }}', '{{ addslashes($dept->short_name) }}', {{ $dept->max_members }})">Szerkesztés</button>
                <form method="POST" action="/alosztaly-adatbazis/{{ $dept->id }}" onsubmit="return confirm('Biztosan törlöd?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 8px">Törlés</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" style="color:#4a5568">Még nincs alosztály.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- New dept modal --}}
<div class="modal-backdrop" id="newDeptModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('newDeptModal')">&times;</button>
        <div class="modal-title">Új alosztály</div>
        <form method="POST" action="/alosztaly-adatbazis">
            @csrf
            <div style="margin-bottom:12px"><label class="form-label">Név</label><input type="text" name="name" class="form-input" required></div>
            <div style="margin-bottom:12px"><label class="form-label">Rövidítés</label><input type="text" name="short_name" class="form-input" required></div>
            <div style="margin-bottom:16px"><label class="form-label">Max. létszám (0 = nincs limit)</label><input type="number" name="max_members" class="form-input" value="0" min="0"></div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Létrehozás</button>
                <button type="button" onclick="closeModal('newDeptModal')" class="btn btn-ghost">Mégse</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit dept modal --}}
<div class="modal-backdrop" id="editDeptModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('editDeptModal')">&times;</button>
        <div class="modal-title">Alosztály szerkesztése</div>
        <form method="POST" id="editDeptForm">
            @csrf
            @method('PUT')
            <div style="margin-bottom:12px"><label class="form-label">Név</label><input type="text" name="name" id="editName" class="form-input" required></div>
            <div style="margin-bottom:12px"><label class="form-label">Rövidítés</label><input type="text" name="short_name" id="editShort" class="form-input" required></div>
            <div style="margin-bottom:16px"><label class="form-label">Max. létszám</label><input type="number" name="max_members" id="editMax" class="form-input" min="0"></div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Mentés</button>
                <button type="button" onclick="closeModal('editDeptModal')" class="btn btn-ghost">Mégse</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openModal(id)  { document.getElementById(id).classList.add('open') }
function closeModal(id) { document.getElementById(id).classList.remove('open') }
function openEdit(id, name, short, max) {
    document.getElementById('editDeptForm').action = `/alosztaly-adatbazis/${id}`;
    document.getElementById('editName').value  = name;
    document.getElementById('editShort').value = short;
    document.getElementById('editMax').value   = max;
    openModal('editDeptModal');
}
</script>
@endpush

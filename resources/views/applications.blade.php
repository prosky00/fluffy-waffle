@extends('layouts.guest')
@section('title', 'Jelentkezéseim — ' . $settings->name)

@section('content')
<div class="page-content" style="max-width:640px">
    <h1 style="font-size:22px;font-weight:900;margin-bottom:24px">Jelentkezéseim</h1>

    @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    @if(auth()->user()->is_member && (!$current || in_array($current->status, ['MEMBER'])))
    <div class="card" style="margin-bottom:16px">
        <p style="font-size:14px">Már teljes jogú tag vagy. <a href="{{ route('dashboard') }}" style="color:var(--color-red);font-weight:700">Ugrás a belső felületre &rarr;</a></p>
    </div>
    @endif

    @if($current && $current->status === 'PENDING')
        <div class="card">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
                <span style="font-weight:700;font-size:14px">Jelentkezésed elbírálás alatt</span>
                <span class="badge badge-pending">Elbírálás alatt</span>
            </div>
            @foreach($formFields as $field)
            @php $answer = $current->answers[$field->id] ?? null; @endphp
            <div class="field">
                <label>{{ $field->label }}</label>
                <p style="font-size:14px;color:var(--color-text-muted);white-space:pre-wrap">{{ is_bool($answer) ? ($answer ? 'Igen' : 'Nem') : ($answer ?: '—') }}</p>
            </div>
            @endforeach
        </div>

    @elseif($current && $current->status === 'NEEDS_CHANGES')
        <div class="card">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <span style="font-weight:700;font-size:14px">Módosítás szükséges</span>
            </div>
            @if($current->review_note)
            <p style="font-size:13px;color:var(--color-text-muted);margin-bottom:18px;line-height:1.6">{{ $current->review_note }}</p>
            @endif
            <form method="POST" action="{{ route('applications.store') }}" novalidate>
                @csrf
                @foreach($formFields as $field)
                @php
                    $answer     = $current->answers[$field->id] ?? null;
                    $editable   = in_array($field->id, $current->fields_needing_changes ?? []);
                @endphp
                <div class="field">
                    <label for="field_{{ $field->id }}">{{ $field->label }} @if($editable)<span style="color:var(--color-red)">*</span>@endif</label>
                    @if(!$editable)
                        <p style="font-size:14px;color:var(--color-text-muted);white-space:pre-wrap">{{ is_bool($answer) ? ($answer ? 'Igen' : 'Nem') : ($answer ?: '—') }}</p>
                    @elseif($field->type === 'textarea')
                        <textarea id="field_{{ $field->id }}" name="answers[{{ $field->id }}]" {{ $field->is_required ? 'required' : '' }}>{{ old("answers.{$field->id}", $answer) }}</textarea>
                    @elseif($field->type === 'select')
                        <select id="field_{{ $field->id }}" name="answers[{{ $field->id }}]" {{ $field->is_required ? 'required' : '' }}>
                            <option value="">— Válassz —</option>
                            @foreach($field->options ?? [] as $opt)
                            <option value="{{ $opt }}" {{ old("answers.{$field->id}", $answer) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif($field->type === 'checkbox')
                        <div style="display:flex;align-items:center;gap:6px">
                            <input type="checkbox" id="field_{{ $field->id }}" name="answers[{{ $field->id }}]" value="1" {{ old("answers.{$field->id}", $answer) ? 'checked' : '' }}>
                            <label for="field_{{ $field->id }}" style="font-weight:400">Igen</label>
                        </div>
                    @else
                        <input type="text" id="field_{{ $field->id }}" name="answers[{{ $field->id }}]" value="{{ old("answers.{$field->id}", $answer) }}" {{ $field->is_required ? 'required' : '' }}>
                    @endif
                </div>
                @endforeach
                <button type="submit" class="btn btn-primary">Módosítások elküldése</button>
            </form>
        </div>

    @elseif($current && $current->status === 'APPROVED')
        <div class="card" style="margin-bottom:16px">
            <span style="font-weight:700;font-size:14px">Jelentkezésed elfogadva!</span>
            <span class="badge badge-approved" style="margin-left:8px">Elfogadva</span>
            <p style="font-size:13px;color:var(--color-text-muted);margin-top:10px;line-height:1.6">Add meg, mikor érnél rá egy rövid interjúra. Legfeljebb 5 időpontot javasolhatsz.</p>
        </div>
        <div class="card">
            <form method="POST" action="{{ route('applications.slots') }}">
                @csrf
                <div id="slotsContainer">
                    @php $existingSlots = $current->proposed_slots ?: [['date' => '', 'start_time' => '', 'end_time' => '']]; @endphp
                    @foreach($existingSlots as $i => $slot)
                    <div class="slot-row" style="display:flex;gap:8px;margin-bottom:10px;align-items:center">
                        <input type="date" name="slots[{{ $i }}][date]" value="{{ $slot['date'] }}" required style="flex:1">
                        <input type="time" name="slots[{{ $i }}][start_time]" value="{{ $slot['start_time'] }}" required style="flex:1">
                        <span style="color:var(--color-text-muted)">–</span>
                        <input type="time" name="slots[{{ $i }}][end_time]" value="{{ $slot['end_time'] }}" required style="flex:1">
                        <button type="button" onclick="this.closest('.slot-row').remove()" class="btn btn-ghost" style="padding:6px 10px">&times;</button>
                    </div>
                    @endforeach
                </div>
                <button type="button" onclick="addSlotRow()" class="btn btn-ghost" style="margin-bottom:16px">+ Időpont hozzáadása</button>
                <button type="submit" class="btn btn-primary" style="width:100%">Elérhetőség mentése</button>
            </form>
        </div>

    @elseif($current && $current->status === 'SCHEDULED')
        <div class="card">
            <span style="font-weight:700;font-size:14px">Interjú időpont megerősítve</span>
            <p style="font-size:15px;margin-top:10px">{{ \Illuminate\Support\Carbon::parse($current->confirmed_date)->format('Y. m. d.') }}, {{ substr($current->confirmed_start_time,0,5) }}–{{ substr($current->confirmed_end_time,0,5) }}</p>
            <p style="font-size:13px;color:var(--color-text-muted);margin-top:10px">Az interjú után értesítést kapsz az eredményről.</p>
        </div>

    @elseif($current && $current->status === 'REJECTED')
        <div class="card" style="margin-bottom:16px">
            <span style="font-weight:700;font-size:14px">Jelentkezésed elutasítva</span>
            <span class="badge badge-rejected" style="margin-left:8px">Elutasítva</span>
        </div>
    @endif

    @if(!auth()->user()->is_member && (!$current || $current->status === 'REJECTED'))
    <div class="card">
        <h2 style="font-size:16px;font-weight:700;margin-bottom:6px">Csatlakozz hozzánk</h2>
        <p style="font-size:13px;color:var(--color-text-muted);margin-bottom:18px;line-height:1.6">
            Töltsd ki a jelentkezési űrlapot. Egy adminisztrátor elbírálja, és itt és Discordon is értesítést kapsz az eredményről.
        </p>
        <form method="POST" action="{{ route('applications.store') }}" novalidate>
            @csrf
            @foreach($formFields as $field)
            <div class="field">
                <label for="new_field_{{ $field->id }}">{{ $field->label }} @if($field->is_required)<span style="color:var(--color-red)">*</span>@endif</label>
                @if($field->type === 'textarea')
                    <textarea id="new_field_{{ $field->id }}" name="answers[{{ $field->id }}]" {{ $field->is_required ? 'required' : '' }}>{{ old("answers.{$field->id}") }}</textarea>
                @elseif($field->type === 'select')
                    <select id="new_field_{{ $field->id }}" name="answers[{{ $field->id }}]" {{ $field->is_required ? 'required' : '' }}>
                        <option value="">— Válassz —</option>
                        @foreach($field->options ?? [] as $opt)
                        <option value="{{ $opt }}" {{ old("answers.{$field->id}") === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                @elseif($field->type === 'checkbox')
                    <div style="display:flex;align-items:center;gap:6px">
                        <input type="checkbox" id="new_field_{{ $field->id }}" name="answers[{{ $field->id }}]" value="1" {{ old("answers.{$field->id}") ? 'checked' : '' }}>
                        <label for="new_field_{{ $field->id }}" style="font-weight:400">Igen</label>
                    </div>
                @else
                    <input type="text" id="new_field_{{ $field->id }}" name="answers[{{ $field->id }}]" value="{{ old("answers.{$field->id}") }}" {{ $field->is_required ? 'required' : '' }}>
                @endif
            </div>
            @endforeach
            <button type="submit" class="btn btn-primary">Jelentkezés elküldése</button>
        </form>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
let _slotIdx = {{ isset($existingSlots) ? count($existingSlots) : 1 }};
function addSlotRow() {
    const i = _slotIdx++;
    const container = document.getElementById('slotsContainer');
    const row = document.createElement('div');
    row.className = 'slot-row';
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:10px;align-items:center';
    row.innerHTML = `
        <input type="date" name="slots[${i}][date]" required style="flex:1">
        <input type="time" name="slots[${i}][start_time]" required style="flex:1">
        <span style="color:var(--color-text-muted)">–</span>
        <input type="time" name="slots[${i}][end_time]" required style="flex:1">
        <button type="button" onclick="this.closest('.slot-row').remove()" class="btn btn-ghost" style="padding:6px 10px">&times;</button>
    `;
    container.appendChild(row);
}
</script>
@endpush

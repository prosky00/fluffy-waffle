@extends('layouts.app')
@section('title', 'Beállítások')
@section('content')
<h1 style="color:#fff;font-size:24px;font-weight:700;margin-bottom:24px">Beállítások</h1>

@if(session('success'))
<div class="alert alert-green">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-red">{{ $errors->first() }}</div>
@endif

<div class="card" style="margin-bottom:16px">
    <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:14px">Általános</div>
    <form method="POST" action="/settings">
        @csrf
        <div style="margin-bottom:16px">
            <label class="form-label">Karakter név</label>
            <input type="text" name="in_game_name" class="form-input" value="{{ auth()->user()->in_game_name }}" placeholder="pl. John_Doe">
        </div>
        <button type="submit" class="btn btn-primary">Mentés</button>
    </form>
</div>

<div class="card">
    <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:14px">Jelszó megváltoztatása</div>
    <form method="POST" action="/settings/password">
        @csrf
        <div style="margin-bottom:12px">
            <label class="form-label">Jelenlegi jelszó</label>
            <input type="password" name="current_password" class="form-input" autocomplete="current-password">
        </div>
        <div style="margin-bottom:12px">
            <label class="form-label">Új jelszó</label>
            <input type="password" name="new_password" class="form-input" autocomplete="new-password">
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">Új jelszó megerősítése</label>
            <input type="password" name="new_password_confirmation" class="form-input" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Jelszó megváltoztatása</button>
    </form>
</div>
@endsection

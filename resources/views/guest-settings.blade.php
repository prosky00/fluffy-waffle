@extends('layouts.guest')
@section('title', 'Fiókbeállítások — ' . $settings->name)

@section('content')
<div class="page-content" style="max-width:480px">
    <h1 style="font-size:22px;font-weight:900;margin-bottom:24px">Fiókbeállítások</h1>

    @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="card" style="margin-bottom:16px">
        <h2 style="font-size:15px;font-weight:700;margin-bottom:14px">Karakter neve</h2>
        <form method="POST" action="{{ route('settings') }}">
            @csrf
            <div class="field">
                <label for="in_game_name">Karakter neve</label>
                <input type="text" id="in_game_name" name="in_game_name" value="{{ auth()->user()->in_game_name }}">
            </div>
            <button type="submit" class="btn btn-primary">Mentés</button>
        </form>
    </div>

    <div class="card">
        <h2 style="font-size:15px;font-weight:700;margin-bottom:14px">Jelszó megváltoztatása</h2>
        <form method="POST" action="{{ route('settings.password') }}">
            @csrf
            <div class="field">
                <label for="current_password">Jelenlegi jelszó</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                @error('current_password')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="new_password">Új jelszó</label>
                <input type="password" id="new_password" name="new_password" autocomplete="new-password">
            </div>
            <div class="field">
                <label for="new_password_confirmation">Új jelszó megerősítése</label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Jelszó megváltoztatása</button>
        </form>
    </div>
</div>
@endsection

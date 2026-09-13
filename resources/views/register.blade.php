@extends('layouts.guest')
@section('title', 'Regisztráció — ' . $settings->name)

@section('content')
<div class="page-content" style="max-width:480px">
    <div class="card">
        <h1 style="font-size:22px;font-weight:900;margin-bottom:8px">Fiók létrehozása</h1>
        <p style="font-size:14px;color:var(--color-text-muted);margin-bottom:24px;line-height:1.6">
            Ez csak egy fiókot hoz létre — a frakcióhoz a fiók létrehozása után, a "Csatlakozz hozzánk" oldalon tudsz jelentkezni.
        </p>

        @if($errors->any())
        <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" novalidate>
            @csrf
            <div class="field">
                <label for="in_game_name">Karakter neve</label>
                <input type="text" id="in_game_name" name="in_game_name" value="{{ old('in_game_name') }}"
                       aria-invalid="{{ $errors->has('in_game_name') ? 'true' : 'false' }}" required autofocus>
                @error('in_game_name')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="username">Felhasználónév</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}"
                       aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}" required>
                <span class="hint">Csak betű, szám, kötőjel és aláhúzás. Ezzel jelentkezel majd be.</span>
                @error('username')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="password">Jelszó</label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" required>
                <span class="hint">Legalább 6 karakter.</span>
                @error('password')<span class="error">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Fiók létrehozása</button>
        </form>
        <p style="text-align:center;font-size:13px;color:var(--color-text-muted);margin-top:18px">
            Már van fiókod? <a href="{{ route('login') }}" style="color:var(--color-red);font-weight:700">Jelentkezz be</a>
        </p>
    </div>
</div>
@endsection

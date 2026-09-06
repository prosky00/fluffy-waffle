<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés — {{ $settings->name }}</title>
    @if($settings->favicon_url)
        <link rel="icon" href="{{ $settings->favicon_url }}">
    @endif
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Inter, system-ui, sans-serif; background: #010409; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #0d1117; border: 1px solid #1e2d3d; border-radius: 12px; padding: 36px 32px; width: 100%; max-width: 380px; }
        .logo { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; margin: 0 auto 16px; display: block; }
        .logo-placeholder { width: 64px; height: 64px; border-radius: 50%; background: #059669; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px; font-weight: 700; margin: 0 auto 16px; }
        h1  { text-align: center; font-size: 22px; color: #fff; font-weight: 700; }
        .sub { text-align: center; color: #4a5568; font-size: 13px; margin-top: 4px; margin-bottom: 28px; }
        label { display: block; font-size: 12px; font-weight: 600; color: #8b949e; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
        input[type=text], input[type=password] {
            width: 100%; padding: 10px 14px; background: #010409; border: 1px solid #1e2d3d;
            border-radius: 8px; color: #fff; font-size: 14px; outline: none;
            transition: border-color .15s;
        }
        input[type=text]:focus, input[type=password]:focus { border-color: #34d399; }
        .field { margin-bottom: 16px; }
        .remember { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #8b949e; margin-bottom: 20px; }
        .remember input { width: auto; }
        .btn-submit {
            width: 100%; padding: 11px 16px; background: #34d399; color: #010409; border: none;
            border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s;
        }
        .btn-submit:hover { background: #2dd08a; }
        .error { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.3); color: #f87171; border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="card">
        @if($settings->logo_url)
            <img src="{{ $settings->logo_url }}" alt="Logo" class="logo">
        @else
            <div class="logo-placeholder">{{ strtoupper(substr($settings->name, 0, 1)) }}</div>
        @endif
        <h1>{{ $settings->name }}</h1>
        <p class="sub">Belső rendszer — csak frakció tagoknak</p>

        @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="/login">
            @csrf
            <div class="field">
                <label for="username">Felhasználónév</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" autofocus autocomplete="username">
            </div>
            <div class="field">
                <label for="password">Jelszó</label>
                <input type="password" id="password" name="password" autocomplete="current-password">
            </div>
            <label class="remember">
                <input type="checkbox" name="remember" value="1"> Emlékezz rám
            </label>
            <button type="submit" class="btn-submit">Bejelentkezés</button>
        </form>
    </div>
</body>
</html>

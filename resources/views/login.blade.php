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
        :root {
            --bg:            oklch(0.145 0 0);
            --surface:       oklch(0.205 0 0);
            --border:        oklch(1 0 0 / 10%);
            --primary:       oklch(0.553 0.195 38.402);
            --primary-hover: oklch(0.47 0.157 37.304);
            --primary-fg:    oklch(0.98 0.016 73.684);
            --accent:        oklch(0.705 0.213 47.604);
            --fg:            oklch(0.985 0 0);
            --fg-muted:      oklch(0.708 0 0);
            --fg-subtle:     oklch(0.556 0 0);
            --destructive:   oklch(0.704 0.191 22.216);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Inter, system-ui, sans-serif; background: var(--bg); color: var(--fg); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 36px 32px; width: 100%; max-width: 380px; }
        .logo { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; margin: 0 auto 16px; display: block; }
        .logo-placeholder { width: 64px; height: 64px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: var(--primary-fg); font-size: 24px; font-weight: 700; margin: 0 auto 16px; }
        h1  { text-align: center; font-size: 22px; color: var(--fg); font-weight: 700; }
        .sub { text-align: center; color: var(--fg-subtle); font-size: 13px; margin-top: 4px; margin-bottom: 28px; }
        label { display: block; font-size: 12px; font-weight: 600; color: var(--fg-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
        input[type=text], input[type=password] {
            width: 100%; padding: 10px 14px; background: var(--bg); border: 1px solid var(--border);
            border-radius: 8px; color: var(--fg); font-size: 14px; outline: none;
            transition: border-color .15s;
        }
        input[type=text]:focus, input[type=password]:focus { border-color: var(--accent); }
        .field { margin-bottom: 16px; }
        .remember { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--fg-muted); margin-bottom: 20px; }
        .remember input { width: auto; }
        .btn-submit {
            width: 100%; padding: 11px 16px; background: var(--primary); color: var(--primary-fg); border: none;
            border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s;
        }
        .btn-submit:hover { background: var(--primary-hover); }
        .error { background: oklch(0.704 0.191 22.216 / 10%); border: 1px solid oklch(0.704 0.191 22.216 / 30%); color: var(--destructive); border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px; }
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

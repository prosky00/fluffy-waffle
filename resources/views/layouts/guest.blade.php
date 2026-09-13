<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $settings->name)</title>
    @if($settings->favicon_url)
        <link rel="icon" href="{{ $settings->favicon_url }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --font-family-primary: 'Lato', sans-serif;
            --color-navy:        #16233d;
            --color-navy-hover:  #1f3155;
            --color-black:       #000000;
            --color-red:         #c8102e;
            --color-red-hover:   #a80d26;
            --color-charcoal:    #2b2b2b;
            --color-charcoal-2:  #3a3a3a;
            --color-page-bg:     #f4f4f5;
            --color-fg:          #ffffff;
            --color-fg-muted:    rgba(255, 255, 255, .68);
            --color-text:        #262626;
            --color-text-muted:  #6b6b6b;
            --color-border:      #e2e2e4;

            --space-1: 6px; --space-2: 9px; --space-3: 12px; --space-4: 14px; --space-5: 15px;
            --radius-xs: 6px;
            --shadow-1: rgba(50, 50, 50, .35) 0px 1px 2px 1px;
            --motion-instant: 150ms;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-family-primary); font-size: 16px; line-height: 24px;
            background: var(--color-page-bg); color: var(--color-text); min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }
        button { font-family: inherit; cursor: pointer; }
        img { max-width: 100%; display: block; }
        :focus-visible { outline: 3px solid var(--color-red); outline-offset: 2px; }

        /* Utility bar (logged-in only) */
        .utility-bar { background: var(--color-navy); color: var(--color-fg); font-size: 13px; }
        .utility-bar .row { max-width: 1200px; margin: 0 auto; padding: 8px var(--space-5); display: flex; align-items: center; gap: var(--space-4); }
        .utility-bar .welcome { font-weight: 700; }
        .utility-bar .welcome span { font-weight: 400; color: var(--color-fg-muted); }
        .utility-bar nav { margin-left: auto; display: flex; align-items: center; gap: var(--space-4); flex-wrap: wrap; }
        .utility-bar nav a, .utility-bar nav button {
            background: none; border: none; color: var(--color-fg); font-size: 13px; font-weight: 700;
            display: flex; align-items: center; gap: 6px; transition: color var(--motion-instant);
        }
        .utility-bar nav a:hover, .utility-bar nav button:hover { color: #ffb3ba; }
        .utility-badge {
            background: var(--color-red); color: #fff; border-radius: 9999px; font-size: 11px;
            font-weight: 700; padding: 1px 6px; line-height: 1.4;
        }

        /* Emergency / banner strips (rendered per page-section, see partials) */
        .strip { text-align: center; font-size: 13px; font-weight: 700; letter-spacing: .03em; padding: 10px var(--space-5); }
        .strip a { text-decoration: underline; }
        .strip-dark   { background: var(--color-black); color: #fff; }
        .strip-light  { background: #fff; color: var(--color-red); border-bottom: 1px solid var(--color-border); }
        .strip-accent { background: var(--color-red); color: #fff; }

        /* Hero */
        .hero-band { background: var(--color-red); color: #fff; text-align: center; padding: 28px var(--space-5) 32px; }
        .hero-band .eyebrow { font-size: 13px; font-weight: 700; letter-spacing: .12em; margin-bottom: 6px; }
        .hero-band .title { font-size: 34px; font-weight: 900; letter-spacing: .02em; }
        .hero-image { width: 100%; max-height: 420px; object-fit: cover; }

        /* Site nav */
        .site-nav { background: var(--color-charcoal); }
        .site-nav .row { max-width: 1200px; margin: 0 auto; padding: 0 var(--space-5); display: flex; align-items: center; gap: 28px; flex-wrap: wrap; }
        .site-nav a { color: #d8d8d8; font-size: 13px; font-weight: 700; letter-spacing: .03em; padding: 14px 0; transition: color var(--motion-instant); }
        .site-nav a:hover { color: #fff; }
        .site-nav .seal { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; margin: 0 4px; }

        /* Content */
        .page-content { max-width: 1200px; margin: 0 auto; padding: 40px var(--space-5) 64px; }

        .site-footer { border-top: 1px solid var(--color-border); padding: var(--space-5); text-align: center; }
        .site-footer p { font-size: 12px; color: var(--color-text-muted); }

        /* Shared form/button styles for guest pages */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: var(--space-2);
            font-size: 14px; font-weight: 700; padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-xs); border: 1px solid transparent; transition: background var(--motion-instant);
        }
        .btn-primary { background: var(--color-red); color: #fff; }
        .btn-primary:hover { background: var(--color-red-hover); }
        .btn-primary:disabled { opacity: .6; cursor: not-allowed; }
        .btn-ghost { background: transparent; color: var(--color-text); border-color: var(--color-border); }
        .btn-ghost:hover { background: #ececec; }

        .card { background: #fff; border: 1px solid var(--color-border); border-radius: var(--radius-xs); box-shadow: var(--shadow-1); padding: 28px; }

        .alert { border-radius: var(--radius-xs); padding: var(--space-3) var(--space-4); font-size: 14px; margin-bottom: var(--space-4); line-height: 1.5; }
        .alert-success { background: #e7f7ef; border: 1px solid #34d39980; color: #1f8a5c; }
        .alert-error   { background: #fdeceb; border: 1px solid #dc354580; color: #a8202f; }

        .field { margin-bottom: 18px; }
        .field label { display: block; font-size: 13px; font-weight: 700; margin-bottom: var(--space-1); }
        .field .hint { display: block; font-size: 12px; color: var(--color-text-muted); margin-top: var(--space-1); }
        .field .error { display: block; font-size: 12px; color: var(--color-red); font-weight: 700; margin-top: var(--space-1); }
        input[type=text], input[type=password], textarea {
            width: 100%; font-family: inherit; font-size: 14px; background: #fff; color: var(--color-text);
            border: 1px solid #c7c7c9; border-radius: var(--radius-xs); padding: var(--space-3);
            transition: border-color var(--motion-instant);
        }
        input:hover, textarea:hover { border-color: #a7a7aa; }
        input[aria-invalid="true"] { border-color: var(--color-red); }
        textarea { resize: vertical; min-height: 96px; }

        .badge { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: 9999px; font-size: 11px; font-weight: 700; }
        .badge-pending  { background: #fff3cd; color: #8a6d00; }
        .badge-approved { background: #e7f7ef; color: #1f8a5c; }
        .badge-rejected { background: #fdeceb; color: #a8202f; }

        @media (max-width: 720px) {
            .hero-band .title { font-size: 24px; }
            .utility-bar .row, .site-nav .row { flex-wrap: wrap; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @auth
    <div class="utility-bar">
        <div class="row">
            <div class="welcome">Üdvözlünk, {{ auth()->user()->in_game_name ?? auth()->user()->name }}</div>
            <nav>
                <a href="{{ route('applications.index') }}">Jelentkezéseim</a>
                <a href="{{ route('notifications.index') }}">
                    Értesítések
                    @php $unread = auth()->user()->userNotifications()->whereNull('read_at')->count(); @endphp
                    @if($unread > 0)<span class="utility-badge">{{ $unread }}</span>@endif
                </a>
                <a href="{{ route('settings') }}">Fiókbeállítások</a>
                <form method="POST" action="{{ route('logout') }}" style="display:contents">
                    @csrf
                    <button type="submit">Kijelentkezés</button>
                </form>
            </nav>
        </div>
    </div>
    @endauth

    <nav class="site-nav">
        <div class="row">
            @if($settings->logo_url)
                <a href="{{ route('welcome') }}"><img src="{{ $settings->logo_url }}" alt="{{ $settings->name }}" class="seal"></a>
            @endif
            @foreach($navLinks as $link)
                <a href="{{ $link->url }}" @if($link->is_external) target="_blank" rel="noopener" @endif>{{ $link->label }}</a>
            @endforeach
        </div>
    </nav>

    @yield('content')

    <footer class="site-footer">
        <p>&copy; {{ date('Y') }} {{ $settings->name }}</p>
    </footer>
    @stack('scripts')
</body>
</html>

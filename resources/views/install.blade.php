<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faction Dashboard — Telepítés</title>
    <style>
        :root {
            --bg:      oklch(0.145 0 0);
            --surface: oklch(0.205 0 0);
            --surface2: oklch(0.24 0 0);
            --border:  oklch(1 0 0 / 10%);
            --primary: oklch(0.553 0.195 38.402);
            --primary-hover: oklch(0.47 0.157 37.304);
            --accent:  oklch(0.705 0.213 47.604);
            --fg:      oklch(0.985 0 0);
            --fg-muted: oklch(0.708 0 0);
            --fg-subtle: oklch(0.556 0 0);
            --red:     oklch(0.704 0.191 22.216);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Inter, system-ui, sans-serif;
            background: var(--bg); color: var(--fg);
            min-height: 100vh; display: flex; align-items: flex-start;
            justify-content: center; padding: 40px 16px 80px;
        }
        .wrap { width: 100%; max-width: 660px; }
        .brand {
            text-align: center; margin-bottom: 32px;
        }
        .brand-icon {
            width: 64px; height: 64px; border-radius: 0;
            background: var(--primary); display: inline-flex;
            align-items: center; justify-content: center; margin-bottom: 12px;
        }
        .brand-icon svg { width: 32px; height: 32px; }
        .brand h1 { font-size: 22px; font-weight: 700; }
        .brand p  { color: var(--fg-subtle); font-size: 14px; margin-top: 4px; }

        .step {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 0; padding: 24px; margin-bottom: 16px;
        }
        .step-title {
            font-size: 13px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: var(--accent); margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }
        .step-num {
            width: 20px; height: 20px; border-radius: 50%;
            background: var(--accent); color: var(--bg);
            font-size: 11px; display: inline-flex;
            align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;
        }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .field { display: flex; flex-direction: column; gap: 5px; }
        .field label { font-size: 12px; color: var(--fg-muted); font-weight: 500; }
        .field small  { font-size: 11px; color: var(--fg-subtle); }
        .field input {
            background: var(--bg); border: 1px solid var(--border); border-radius: 0;
            color: var(--fg); padding: 8px 12px; font-size: 14px; font-family: inherit;
            width: 100%; transition: border-color .15s;
        }
        .field input:focus { outline: none; border-color: var(--accent); }
        .field input.error { border-color: var(--red); }
        .err-msg { font-size: 11px; color: var(--red); margin-top: 2px; }

        .hint-box {
            background: oklch(0.553 0.195 38.402 / 8%);
            border: 1px solid oklch(0.553 0.195 38.402 / 25%);
            border-radius: 0; padding: 12px 14px; margin-bottom: 16px;
            font-size: 12px; color: var(--fg-muted); line-height: 1.6;
        }
        .hint-box strong { color: var(--accent); }
        .hint-box a { color: var(--accent); }

        .btn-install {
            width: 100%; padding: 12px; background: var(--primary);
            color: oklch(0.98 0.016 73.684); border: none; border-radius: 0;
            font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s;
            margin-top: 8px;
        }
        .btn-install:hover { background: var(--primary-hover); }
        .btn-install:disabled { opacity: .5; cursor: not-allowed; }

        .alert-red {
            background: oklch(0.704 0.191 22.216 / 10%);
            border: 1px solid oklch(0.704 0.191 22.216 / 30%);
            color: oklch(0.704 0.191 22.216);
            border-radius: 0; padding: 12px 16px; margin-bottom: 16px; font-size: 14px;
        }
        .divider {
            border: none; border-top: 1px solid var(--border); margin: 4px 0 16px;
        }
        .progress {
            text-align: center; padding: 20px; color: var(--fg-subtle); font-size: 14px;
            display: none;
        }
    </style>
</head>
<body>
<div class="wrap">

    <div class="brand">
        <div class="brand-icon">
            <svg fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </div>
        <h1>Faction Dashboard telepítő</h1>
        <p>Töltsd ki az alábbi mezőket az első indítás előtt.</p>
    </div>

    @if($errors->any())
    <div class="alert-red">
        <strong>Hibák:</strong>
        <ul style="margin-top:6px;padding-left:16px">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="/install" id="installForm" onsubmit="startInstall()">
        @csrf

        {{-- STEP 1: App --}}
        <div class="step">
            <div class="step-title"><span class="step-num">1</span> Az alkalmazás</div>
            <div class="grid2">
                <div class="field" style="grid-column:span 2">
                    <label>Frakció neve *</label>
                    <input type="text" name="faction_name" value="{{ old('faction_name', 'Faction Dashboard') }}"
                           placeholder="pl. LSPD Belső rendszer" class="{{ $errors->has('faction_name') ? 'error' : '' }}" required>
                    @error('faction_name')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="field" style="grid-column:span 2">
                    <label>Az oldal URL-je *</label>
                    <input type="url" name="app_url" value="{{ old('app_url', 'http://localhost') }}"
                           placeholder="http://localhost" class="{{ $errors->has('app_url') ? 'error' : '' }}" required>
                    <small>Docker esetén maradhat http://localhost, éles szerveren add meg a domainedet (https://...)</small>
                    @error('app_url')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- STEP 2: Discord --}}
        <div class="step">
            <div class="step-title"><span class="step-num">2</span> Discord Bot</div>

            <div class="hint-box">
                <strong>Hol találod ezeket?</strong><br>
                Menj a <a href="https://discord.com/developers/applications" target="_blank">Discord fejlesztői oldalra</a> →
                hozz létre egy alkalmazást → <strong>OAuth2</strong> fülön találod a Client ID-t és Secret-et.
                A <strong>Bot</strong> fülön add hozzá a botot, majd másold a tokent.
                A Guild ID-hoz jobb klikk a szerveredeteden (Developer Mode bekapcsolva) → Server ID másolása.
            </div>

            <div class="grid2">
                <div class="field">
                    <label>Client ID *</label>
                    <input type="text" name="discord_client_id" value="{{ old('discord_client_id') }}"
                           placeholder="123456789012345678" class="{{ $errors->has('discord_client_id') ? 'error' : '' }}" required>
                    @error('discord_client_id')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Client Secret *</label>
                    <input type="text" name="discord_client_secret" value="{{ old('discord_client_secret') }}"
                           placeholder="AbCdEf..." class="{{ $errors->has('discord_client_secret') ? 'error' : '' }}" required>
                    @error('discord_client_secret')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="field" style="grid-column:span 2">
                    <label>Bot Token *</label>
                    <input type="text" name="discord_bot_token" value="{{ old('discord_bot_token') }}"
                           placeholder="ODA5NT..." class="{{ $errors->has('discord_bot_token') ? 'error' : '' }}" required>
                    <small>Soha ne add meg ezt másnak!</small>
                    @error('discord_bot_token')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="field" style="grid-column:span 2">
                    <label>Discord szerver (Guild) ID *</label>
                    <input type="text" name="discord_guild_id" value="{{ old('discord_guild_id') }}"
                           placeholder="123456789012345678" class="{{ $errors->has('discord_guild_id') ? 'error' : '' }}" required>
                    @error('discord_guild_id')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <hr class="divider" style="grid-column:span 2">

                <div class="field">
                    <label>Felhívások csatorna ID</label>
                    <input type="text" name="discord_announcement_channel" value="{{ old('discord_announcement_channel') }}"
                           placeholder="Opcionális">
                    <small>Ide kerülnek a közzétett felhívások</small>
                </div>
                <div class="field">
                    <label>Jelentések csatorna ID</label>
                    <input type="text" name="discord_reports_channel" value="{{ old('discord_reports_channel') }}"
                           placeholder="Opcionális">
                    <small>Ide kerülnek a beküldött jelentés értesítők</small>
                </div>
                <div class="field">
                    <label>Tag szerepkör ID</label>
                    <input type="text" name="discord_member_role" value="{{ old('discord_member_role') }}"
                           placeholder="Opcionális">
                    <small>A verifikált tagok Discord szerepköre</small>
                </div>
            </div>
        </div>

        {{-- STEP 3: Admin account --}}
        <div class="step">
            <div class="step-title"><span class="step-num">3</span> Admin fiók létrehozása</div>
            <div class="grid2">
                <div class="field">
                    <label>Teljes név *</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}"
                           placeholder="pl. Adminisztrátor" class="{{ $errors->has('admin_name') ? 'error' : '' }}" required>
                    @error('admin_name')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Felhasználónév *</label>
                    <input type="text" name="admin_username" value="{{ old('admin_username') }}"
                           placeholder="pl. admin" class="{{ $errors->has('admin_username') ? 'error' : '' }}" required>
                    <small>Csak betű, szám, - és _</small>
                    @error('admin_username')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Jelszó * (min. 8 karakter)</label>
                    <input type="password" name="admin_password"
                           class="{{ $errors->has('admin_password') ? 'error' : '' }}" required>
                    @error('admin_password')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Jelszó megerősítése *</label>
                    <input type="password" name="admin_password_confirmation" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-install" id="installBtn">
            Telepítés indítása →
        </button>
        <div class="progress" id="progressMsg">
            ⏳ Telepítés folyamatban… Ez eltarthat néhány másodpercig.
        </div>
    </form>
</div>

<script>
function startInstall() {
    const btn = document.getElementById('installBtn');
    const msg = document.getElementById('progressMsg');
    btn.disabled = true;
    btn.textContent = 'Telepítés…';
    msg.style.display = 'block';
}
</script>
</body>
</html>

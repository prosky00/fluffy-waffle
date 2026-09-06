<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class InstallController extends Controller
{
    public function show()
    {
        return view('install');
    }

    public function install(Request $request)
    {
        $data = $request->validate([
            'faction_name'        => 'required|string|max:100',
            'app_url'             => 'required|url',
            'discord_client_id'   => 'required|string',
            'discord_client_secret' => 'required|string',
            'discord_bot_token'   => 'required|string',
            'discord_guild_id'    => 'required|string',
            'discord_announcement_channel' => 'nullable|string',
            'discord_reports_channel'      => 'nullable|string',
            'discord_member_role'          => 'nullable|string',
            'admin_name'     => 'required|string|max:100',
            'admin_username' => 'required|string|max:50|regex:/^[a-zA-Z0-9_\-]+$/',
            'admin_password' => 'required|string|min:8|confirmed',
        ], [
            'faction_name.required'        => 'A frakció neve kötelező.',
            'app_url.required'             => 'Az alkalmazás URL-je kötelező.',
            'app_url.url'                  => 'Érvényes URL-t adj meg (pl. http://localhost).',
            'discord_client_id.required'   => 'A Discord Client ID kötelező.',
            'discord_client_secret.required' => 'A Discord Client Secret kötelező.',
            'discord_bot_token.required'   => 'A Discord Bot Token kötelező.',
            'discord_guild_id.required'    => 'A Discord szerver ID kötelező.',
            'admin_name.required'          => 'Az admin neve kötelező.',
            'admin_username.required'      => 'Az admin felhasználóneve kötelező.',
            'admin_username.regex'         => 'Csak betű, szám, kötőjel és alulvonás megengedett.',
            'admin_password.required'      => 'A jelszó kötelező.',
            'admin_password.min'           => 'A jelszónak legalább 8 karakter hosszúnak kell lennie.',
            'admin_password.confirmed'     => 'A két jelszó nem egyezik.',
        ]);

        // Write .env
        $this->writeEnv($data);

        // Reload config so the new env is picked up
        Artisan::call('config:clear');

        // Generate app key (writes back into .env)
        Artisan::call('key:generate', ['--force' => true]);

        // Run migrations
        Artisan::call('migrate', ['--force' => true]);

        // Create admin user
        User::create([
            'name'       => $data['admin_name'],
            'username'   => $data['admin_username'],
            'password'   => $data['admin_password'], // cast hashes automatically
            'is_admin'   => true,
        ]);

        // Also create faction settings row
        \App\Models\FactionSetting::firstOrCreate([], ['name' => $data['faction_name']]);

        // Mark as installed
        file_put_contents(storage_path('installed.lock'), now()->toIso8601String());

        return redirect('/login')->with('success', 'Telepítés sikeres! Jelentkezz be az admin fiókoddal.');
    }

    private function writeEnv(array $data): void
    {
        $envPath = base_path('.env');
        $env = <<<ENV
APP_NAME="{$data['faction_name']}"
APP_ENV=production
APP_KEY=base64:PLACEHOLDER
APP_DEBUG=false
APP_URL={$data['app_url']}

APP_LOCALE=hu
APP_FALLBACK_LOCALE=hu
APP_FAKER_LOCALE=hu_HU

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

VITE_APP_NAME="{$data['faction_name']}"

DISCORD_CLIENT_ID={$data['discord_client_id']}
DISCORD_CLIENT_SECRET={$data['discord_client_secret']}
DISCORD_BOT_TOKEN={$data['discord_bot_token']}
DISCORD_GUILD_ID={$data['discord_guild_id']}
DISCORD_ANNOUNCEMENT_CHANNEL_ID={$data['discord_announcement_channel']}
DISCORD_REPORTS_CHANNEL_ID={$data['discord_reports_channel']}
DISCORD_MEMBER_ROLE_ID={$data['discord_member_role']}
ENV;

        file_put_contents($envPath, $env);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\FactionSetting;
use App\Models\NavLink;
use Illuminate\Support\Facades\Http;

abstract class Controller
{
    protected function settings(): FactionSetting
    {
        return FactionSetting::singleton();
    }

    protected function view(string $view, array $data = [])
    {
        return view($view, array_merge([
            'settings' => $this->settings(),
            'navLinks' => NavLink::ordered()->get(),
        ], $data));
    }

    protected function sendDiscordDm(string $discordId, string $message): void
    {
        $token = config('services.discord.bot_token');
        if (!$token || !$discordId) return;

        try {
            $dm = Http::withHeaders(['Authorization' => "Bot {$token}"])
                ->post('https://discord.com/api/v10/users/@me/channels', ['recipient_id' => $discordId]);

            if ($dm->successful()) {
                Http::withHeaders(['Authorization' => "Bot {$token}"])
                    ->post("https://discord.com/api/v10/channels/{$dm->json('id')}/messages", [
                        'content' => $message,
                    ]);
            }
        } catch (\Exception $e) {
            // Discord DM is supplementary — never fail the main request
        }
    }
}

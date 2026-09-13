<?php

namespace App\Http\Controllers;

use App\Models\FactionSetting;
use App\Models\NavLink;
use App\Services\DiscordService;

abstract class Controller
{
    protected function settings(): FactionSetting
    {
        return FactionSetting::singleton();
    }

    /** Posts a "who changed what" entry to the admin audit channel, if configured. */
    protected function logAudit(string $title, array $fields, int $color = 0x5865F2): void
    {
        $channelId = $this->settings()->discord_audit_channel_id ?: config('services.discord.audit_channel_id');
        if ($channelId) {
            app(DiscordService::class)->logAudit($channelId, $title, $fields, $color);
        }
    }

    protected function actorName(): string
    {
        $u = auth()->user();
        return $u->in_game_name ?? $u->name;
    }

    protected function view(string $view, array $data = [])
    {
        return view($view, array_merge([
            'settings' => $this->settings(),
            'navLinks' => NavLink::ordered()->get(),
        ], $data));
    }
}

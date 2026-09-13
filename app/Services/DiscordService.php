<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DiscordService
{
    private string $token;
    private string $guildId;

    public function __construct()
    {
        $this->token   = config('services.discord.bot_token', '');
        $this->guildId = config('services.discord.guild_id', '');
    }

    public function sendDm(string $discordId, string $content): bool
    {
        if (!$this->token) return false;
        try {
            $dm = Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->post('https://discord.com/api/v10/users/@me/channels', ['recipient_id' => $discordId]);
            if (!$dm->successful()) return false;
            Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->post("https://discord.com/api/v10/channels/{$dm->json('id')}/messages", ['content' => $content]);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function sendChannelMessage(string $channelId, string $content = '', array $embed = []): bool
    {
        if (!$this->token || !$channelId) return false;
        try {
            $payload = [];
            if ($content) $payload['content'] = $content;
            if ($embed)   $payload['embeds']  = [$embed];
            Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->post("https://discord.com/api/v10/channels/{$channelId}/messages", $payload);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function sendAnnouncement(string $channelId, string $title, string $description, string $authorName = '', string $mention = ''): bool
    {
        $embed = [
            'title'       => $title,
            'description' => $description,
            'color'       => hexdec('34d399'),
            'timestamp'   => now()->toIso8601String(),
        ];
        if ($authorName) $embed['author'] = ['name' => $authorName];

        $content = '';
        if ($mention === '@everyone') {
            $content = '@everyone';
        } elseif ($mention) {
            $content = "<@&{$mention}>";
        }

        return $this->sendChannelMessage($channelId, $content, $embed);
    }

    public function editMessage(string $channelId, string $messageId, array $embed): bool
    {
        if (!$this->token) return false;
        try {
            Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->patch("https://discord.com/api/v10/channels/{$channelId}/messages/{$messageId}", ['embeds' => [$embed]]);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function getChannelMessages(string $channelId, int $limit = 25): array
    {
        if (!$this->token) return [];
        try {
            $r = Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->get("https://discord.com/api/v10/channels/{$channelId}/messages", compact('limit'));
            return $r->successful() ? $r->json() : [];
        } catch (\Exception) {
            return [];
        }
    }

    /** Text channels only, each annotated with its parent category's name (if any)
     *  so callers can label options as "Category - Channel". */
    public function getGuildChannels(): array
    {
        if (!$this->token || !$this->guildId) return [];
        try {
            $r = Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->get("https://discord.com/api/v10/guilds/{$this->guildId}/channels");
            if ($r->successful()) {
                $all        = collect($r->json());
                $categories = $all->where('type', 4)->keyBy('id');

                return $all->where('type', 0)
                    ->sortBy('position')
                    ->map(function ($ch) use ($categories) {
                        $ch['category_name'] = $categories->get($ch['parent_id'] ?? null)['name'] ?? null;
                        return $ch;
                    })
                    ->values()
                    ->toArray();
            }
        } catch (\Exception) {}
        return [];
    }

    public function getGuildRoles(): array
    {
        if (!$this->token || !$this->guildId) return [];
        try {
            $r = Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->get("https://discord.com/api/v10/guilds/{$this->guildId}/roles");
            if ($r->successful()) {
                return collect($r->json())
                    ->sortBy('position')
                    ->values()
                    ->toArray();
            }
        } catch (\Exception) {}
        return [];
    }
}

<?php

namespace App\Services;

use App\Models\Department;
use App\Models\FactionSetting;
use App\Models\User;
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

    public function sendDm(string $discordId, string $content = '', array $embed = []): bool
    {
        if (!$this->token) return false;
        try {
            $dm = Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->post('https://discord.com/api/v10/users/@me/channels', ['recipient_id' => $discordId]);
            if (!$dm->successful()) return false;

            $payload = [];
            if ($content) $payload['content'] = $content;
            if ($embed)   $payload['embeds']  = [$embed];

            Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->post("https://discord.com/api/v10/channels/{$dm->json('id')}/messages", $payload);
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

    /** Posts a "who changed what" entry to the admin audit channel. $fields is a
     *  flat ['label' => value] map, always led by the actor ("Végrehajtotta"). */
    public function logAudit(string $channelId, string $title, array $fields, int $color = 0x5865F2): bool
    {
        if (!$channelId) return false;
        return $this->sendChannelMessage($channelId, '', [
            'title'     => $title,
            'fields'    => array_map(fn($label, $value) => [
                'name'   => $label,
                'value'  => (string)($value ?: '—'),
                'inline' => false,
            ], array_keys($fields), array_values($fields)),
            'color'     => $color,
            'timestamp' => now()->toIso8601String(),
        ]);
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

    /** Grants a guild role to a member. Requires the bot's own role to sit above
     *  the target role in the server's role hierarchy, or Discord rejects it. */
    public function addMemberRole(string $discordId, ?string $roleId): bool
    {
        if (!$this->token || !$this->guildId || !$roleId || !$discordId) return false;
        try {
            $r = Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->put("https://discord.com/api/v10/guilds/{$this->guildId}/members/{$discordId}/roles/{$roleId}");
            return $r->successful();
        } catch (\Exception) {
            return false;
        }
    }

    public function removeMemberRole(string $discordId, ?string $roleId): bool
    {
        if (!$this->token || !$this->guildId || !$roleId || !$discordId) return false;
        try {
            $r = Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->delete("https://discord.com/api/v10/guilds/{$this->guildId}/members/{$discordId}/roles/{$roleId}");
            return $r->successful();
        } catch (\Exception) {
            return false;
        }
    }

    /** Sets a member's server nickname. Requires the bot to have Manage Nicknames
     *  and — like role changes — to sit above the target in the role hierarchy;
     *  Discord also refuses to nickname the guild owner regardless of permissions. */
    public function setNickname(string $discordId, string $nickname): bool
    {
        if (!$this->token || !$this->guildId || !$discordId) return false;
        try {
            $r = Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->patch("https://discord.com/api/v10/guilds/{$this->guildId}/members/{$discordId}", [
                    'nick' => mb_substr($nickname, 0, 32),
                ]);
            return $r->successful();
        } catch (\Exception) {
            return false;
        }
    }

    /** Grants a freshly-linked user everything their current site profile
     *  implies — nickname, rank role, department role(s), admin role — since
     *  they had no Discord roles to react to before now. Only adds; there's
     *  nothing to remove on a first link. */
    public function pushUserState(User $user): void
    {
        if (!$user->discord_id) return;

        if ($user->in_game_name) {
            $this->setNickname($user->discord_id, $user->in_game_name);
        }

        if ($user->rank_id && $roleId = $user->rank?->discord_role_id) {
            $this->addMemberRole($user->discord_id, $roleId);
        }

        $deptIds = $user->departments()->pluck('departments.id')->toArray();
        if ($user->department_id) $deptIds[] = $user->department_id;
        foreach (Department::whereIn('id', array_unique($deptIds))->get() as $dept) {
            $this->addMemberRole($user->discord_id, $dept->discord_role_id);
        }

        if ($user->is_admin && $adminRoleId = FactionSetting::singleton()->discord_admin_role_id) {
            $this->addMemberRole($user->discord_id, $adminRoleId);
        }
    }

    /** Current role IDs a guild member actually holds on Discord right now. */
    public function getMemberRoleIds(string $discordId): array
    {
        if (!$this->token || !$this->guildId || !$discordId) return [];
        try {
            $r = Http::withHeaders(['Authorization' => "Bot {$this->token}"])
                ->get("https://discord.com/api/v10/guilds/{$this->guildId}/members/{$discordId}");
            return $r->successful() ? ($r->json('roles') ?? []) : [];
        } catch (\Exception) {
            return [];
        }
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

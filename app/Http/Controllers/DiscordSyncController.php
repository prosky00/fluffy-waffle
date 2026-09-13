<?php

namespace App\Http\Controllers;

use App\Models\FactionSetting;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class DiscordSyncController extends Controller
{
    public function sync()
    {
        $guildId    = config('services.discord.guild_id');
        $token      = config('services.discord.bot_token');
        $memberRole = FactionSetting::singleton()->discord_member_role_id ?: config('services.discord.member_role_id');
        $headers    = ['Authorization' => "Bot {$token}"];

        // Test bot token
        $test = Http::withHeaders($headers)->get("https://discord.com/api/v10/guilds/{$guildId}");
        if (!$test->ok()) {
            return back()->withErrors(['sync' => "Discord API hiba: {$test->status()} — {$test->body()}"]);
        }

        $ranks = Rank::whereNotNull('discord_role_id')->get()->keyBy('discord_role_id');
        if ($ranks->isEmpty()) {
            return back()->withErrors(['sync' => 'Nincsenek rangok Discord szerepkör ID-vel beállítva. Menj az Admin → Rangok fülre és töltsd ki a "Discord szerepkör ID" mezőket.']);
        }

        // Fetch all guild members (paginate with after)
        $allMembers = [];
        $after = '0';
        do {
            $response = Http::withHeaders($headers)
                ->get("https://discord.com/api/v10/guilds/{$guildId}/members", ['limit' => 1000, 'after' => $after]);

            if (!$response->ok()) break;

            $batch = $response->json();
            if (empty($batch)) break;

            $allMembers = array_merge($allMembers, $batch);
            $after = end($batch)['user']['id'];
        } while (count($batch) === 1000);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($allMembers as $member) {
            $discordUser = $member['user'] ?? null;
            if (!$discordUser || ($discordUser['bot'] ?? false)) continue;

            // Only process members with the faction member role (if set)
            if ($memberRole && !in_array($memberRole, $member['roles'] ?? [])) {
                $skipped++;
                continue;
            }

            $roleIds = $member['roles'] ?? [];
            $matched = null;
            $bestLevel = -1;

            foreach ($roleIds as $roleId) {
                if (isset($ranks[$roleId]) && $ranks[$roleId]->level > $bestLevel) {
                    $matched   = $ranks[$roleId];
                    $bestLevel = $ranks[$roleId]->level;
                }
            }

            $avatarHash = $discordUser['avatar'] ?? null;
            $avatarUrl  = $avatarHash
                ? "https://cdn.discordapp.com/avatars/{$discordUser['id']}/{$avatarHash}.png"
                : null;

            $nickname = $member['nick'] ?? null;
            $username = $discordUser['username'] ?? 'Unknown';
            $displayName = $nickname ?? $discordUser['global_name'] ?? $username;

            $existing = User::where('discord_id', $discordUser['id'])->first();

            $updateData = [
                'name'   => $displayName,
                'avatar' => $avatarUrl,
            ];
            if ($matched) {
                $updateData['rank_id'] = $matched->id;
                if ($matched->is_admin) {
                    $updateData['is_admin'] = true;
                }
            }

            if ($existing) {
                $existing->update($updateData);
                $updated++;
            } else {
                User::create(array_merge($updateData, [
                    'discord_id' => $discordUser['id'],
                    'email'      => null,
                ]));
                $created++;
            }
        }

        return back()->with('success', "Szinkronizálva: {$updated} frissítve, {$created} új tag, {$skipped} kihagyva (nem tag).");
    }
}

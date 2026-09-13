<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\FactionSetting;
use App\Models\Rank;
use App\Models\User;
use App\Services\DiscordService;
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

        $this->logAudit('🔄 Discord szinkronizáció lefuttatva', [
            'Végrehajtotta' => $this->actorName(),
            'Eredmény'      => "{$updated} frissítve, {$created} új tag, {$skipped} kihagyva",
        ]);

        return back()->with('success', "Szinkronizálva: {$updated} frissítve, {$created} új tag, {$skipped} kihagyva (nem tag).");
    }

    /** The reverse direction of sync(): pushes each linked user's current rank,
     *  department(s), admin status and character name out to their real Discord
     *  roles/nickname. Unlike the per-edit auto-sync (which only reacts to a
     *  change), this recomputes from scratch for everyone — so it also fixes
     *  stale roles left over from remapping a rank's or department's
     *  discord_role_id after the fact, which the reactive sync can't catch. */
    public function pushAll()
    {
        $discord = app(DiscordService::class);

        // The full universe of roles this app manages — anything else on a
        // member (e.g. roles from other bots) is left untouched.
        $rankRoleIds = Rank::whereNotNull('discord_role_id')->pluck('discord_role_id')->all();
        $deptRoleIds = Department::whereNotNull('discord_role_id')->pluck('discord_role_id')->all();
        $settings    = FactionSetting::singleton();
        $adminRoleId = $settings->discord_admin_role_id;
        $memberRoleId = $settings->discord_member_role_id;
        $guestRoleId  = $settings->discord_guest_role_id;
        $managedRoleIds = array_unique(array_filter(array_merge(
            $rankRoleIds, $deptRoleIds, [$adminRoleId, $memberRoleId, $guestRoleId]
        )));

        $users = User::whereNotNull('discord_id')->with(['rank', 'departments'])->get();
        $updated = 0;

        foreach ($users as $user) {
            $desiredRoleIds = [];
            if ($roleId = $user->rank?->discord_role_id) $desiredRoleIds[] = $roleId;
            if ($user->is_admin && $adminRoleId) $desiredRoleIds[] = $adminRoleId;
            if ($user->is_member && $memberRoleId) $desiredRoleIds[] = $memberRoleId;
            if (!$user->is_member && $guestRoleId) $desiredRoleIds[] = $guestRoleId;

            $deptIds = $user->departments->pluck('id')->toArray();
            if ($user->department_id) $deptIds[] = $user->department_id;
            foreach (Department::whereIn('id', array_unique($deptIds))->whereNotNull('discord_role_id')->pluck('discord_role_id') as $roleId) {
                $desiredRoleIds[] = $roleId;
            }
            $desiredRoleIds = array_unique($desiredRoleIds);

            $currentRoleIds = $discord->getMemberRoleIds($user->discord_id);
            $changed = false;

            foreach (array_diff($desiredRoleIds, $currentRoleIds) as $roleId) {
                $discord->addMemberRole($user->discord_id, $roleId);
                $changed = true;
            }
            foreach (array_intersect($managedRoleIds, array_diff($currentRoleIds, $desiredRoleIds)) as $roleId) {
                $discord->removeMemberRole($user->discord_id, $roleId);
                $changed = true;
            }
            if ($user->in_game_name) {
                $discord->setNickname($user->discord_id, $user->in_game_name);
            }

            if ($changed) $updated++;
        }

        $this->logAudit('🔄 Discord szerepkörök visszaszinkronizálva', [
            'Végrehajtotta' => $this->actorName(),
            'Eredmény'      => "{$updated} tag szerepköre módosítva, " . $users->count() . " csatolt tag ellenőrizve",
        ]);

        return back()->with('success', "Kész: {$updated} tag Discord szerepköre frissítve ({$users->count()} csatolt tag ellenőrizve).");
    }
}

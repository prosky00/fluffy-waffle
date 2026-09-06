<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\FactionSetting;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Rank;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Services\DiscordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        $user            = auth()->user();
        $discord         = app(DiscordService::class);
        $users           = $user->is_admin ? User::with(['rank', 'department', 'departmentRank', 'departments'])->orderBy('name')->get() : collect();
        $ranks           = Rank::orderBy('level', 'desc')->get();
        $departments     = $user->is_admin ? Department::with('ranks')->withCount('members')->orderBy('name')->get() : collect();
        $announcements   = Announcement::with('author')->latest()->get();
        $messages        = $user->is_admin ? Message::with(['author', 'conversation'])->latest()->take(50)->get() : collect();
        $factionSettings = FactionSetting::singleton();
        $discordChannels = $user->is_admin ? $discord->getGuildChannels() : [];
        $discordRoles    = $user->is_admin ? $discord->getGuildRoles() : [];
        $categories      = ReportCategory::orderBy('sort_order')->get();
        $allReports      = Report::with('author')->latest()->get();

        return $this->view('admin', compact('users', 'ranks', 'departments', 'announcements', 'messages', 'factionSettings', 'discordChannels', 'discordRoles', 'categories', 'allReports'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'username'     => 'required|string|max:50|unique:users,username|alpha_dash',
            'password'     => 'required|string|min:6',
            'name'         => 'required|string|max:100',
            'in_game_name'  => 'nullable|string|max:100',
            'rank_id'       => 'nullable|exists:ranks,id',
            'is_supervisor' => 'nullable|boolean',
            'is_admin'      => 'nullable|boolean',
        ]);

        User::create([
            'username'      => $data['username'],
            'password'      => $data['password'],
            'name'          => $data['name'],
            'in_game_name'  => $data['in_game_name'] ?? null,
            'rank_id'       => $data['rank_id'] ?? null,
            'is_supervisor' => !empty($data['is_supervisor']),
            'is_admin'      => !empty($data['is_admin']),
        ]);

        return $this->adminTab('users', "Fiók létrehozva. Felhasználónév: {$data['username']}");
    }

    public function toggleSuspend($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return $this->adminTab('users', null, 'Nem függesztheted fel a saját fiókod.');
        }
        $user->update(['is_suspended' => !$user->is_suspended]);
        $status = $user->is_suspended ? 'felfüggesztve' : 'aktiválva';
        return $this->adminTab('users', "Felhasználó {$status}.");
    }

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return $this->adminTab('users', null, 'Nem törölheted a saját fiókod.');
        }
        $user->delete();
        return $this->adminTab('users', 'Felhasználó törölve.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'username'                => "nullable|string|max:50|alpha_dash|unique:users,username,{$id}",
            'new_password'            => 'nullable|string|min:6',
            'rank_id'                 => 'nullable|exists:ranks,id',
            'in_game_name'            => 'nullable|string|max:100',
            'is_admin'                => 'nullable|boolean',
            'is_supervisor'           => 'nullable|boolean',
            'department_id'           => 'nullable|exists:departments,id',
            'department_ids'          => 'nullable|array',
            'department_ids.*'        => 'exists:departments,id',
            'department_rank_id'      => 'nullable|exists:department_ranks,id',
            'is_department_leader'    => 'nullable|boolean',
            'is_department_deputy'    => 'nullable|boolean',
            'rank_up_date'            => 'nullable|date',
            'notification_preference' => 'nullable|in:ALL,MESSAGES_ONLY',
        ]);

        $oldRankId = $user->rank_id;
        $oldDeptId = $user->department_id;

        $newPassword   = $data['new_password'] ?? null;
        $newDeptIds    = $data['department_ids'] ?? null;
        unset($data['new_password'], $data['department_ids']);

        $user->update(array_filter($data, fn($v) => $v !== null));

        if ($newPassword) {
            $user->update(['password' => $newPassword]);
        }

        // Sync many-to-many department memberships and their conversations
        if ($newDeptIds !== null) {
            $oldDeptPivotIds = $user->departments()->pluck('departments.id')->toArray();
            $user->departments()->sync($newDeptIds);

            // Add user to newly joined dept conversations
            $added   = array_diff($newDeptIds, $oldDeptPivotIds);
            $removed = array_diff($oldDeptPivotIds, $newDeptIds);

            foreach (Department::whereIn('id', $added)->get() as $dept) {
                $conv = $dept->syncConversation();
                $conv->participants()->syncWithoutDetaching([$user->id]);
            }
            foreach (Department::whereIn('id', $removed)->get() as $dept) {
                if ($conv = $dept->officialConversation) {
                    $conv->participants()->detach($user->id);
                }
            }
        }

        // Only notify when the value actually changed (cast both sides — form sends strings, DB returns ints)
        if (!empty($data['rank_id']) && (int)$data['rank_id'] !== (int)$oldRankId) {
            $rankName = Rank::find($data['rank_id'])?->name ?? 'ismeretlen';
            $msg = "Rangod megváltozott: {$rankName}";
            if ($user->wantsNotification('general')) {
                Notification::create(['user_id' => $user->id, 'message' => $msg]);
            }
            if ($user->discord_id) {
                app(DiscordService::class)->sendDm($user->discord_id, "🎖️ Faction értesítő: {$msg}");
            }

            // Sync rank-based conversation membership
            if ($oldRankId) {
                $oldConv = Conversation::where('type', 'RANK')->where('rank_id', $oldRankId)->first();
                if ($oldConv) $oldConv->participants()->detach($user->id);
            }
            $newConv = Conversation::where('type', 'RANK')->where('rank_id', $data['rank_id'])->first();
            if ($newConv) $newConv->participants()->syncWithoutDetaching([$user->id]);
        }
        if (!empty($data['department_id']) && (int)$data['department_id'] !== (int)$oldDeptId) {
            $deptName = Department::find($data['department_id'])?->name ?? 'ismeretlen';
            $msg = "Elsődleges alosztályod megváltozott: {$deptName}";
            if ($user->wantsNotification('general')) {
                Notification::create(['user_id' => $user->id, 'message' => $msg]);
            }
            if ($user->discord_id) {
                app(DiscordService::class)->sendDm($user->discord_id, "🏢 Faction értesítő: {$msg}");
            }
        }

        return $this->adminTab('users', 'Felhasználó frissítve.');
    }

    public function updateDepartment(Request $request, $id)
    {
        $dept = Department::findOrFail($id);
        $data = $request->validate([
            'discord_role_id'    => 'nullable|string|max:50',
            'discord_channel_id' => 'nullable|string|max:50',
        ]);
        $dept->update($data);
        return $this->adminTab('departments', 'Alosztály frissítve.');
    }

    public function storeRank(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'color'           => 'required|string|max:20',
            'level'           => 'required|integer',
            'is_admin'        => 'nullable|boolean',
            'discord_role_id' => 'nullable|string',
        ]);
        Rank::create($data);
        return $this->adminTab('ranks', 'Rang létrehozva.');
    }

    public function updateRank(Request $request, $id)
    {
        $rank = Rank::findOrFail($id);
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'color'           => 'required|string|max:20',
            'level'           => 'required|integer',
            'is_admin'        => 'nullable|boolean',
            'discord_role_id' => 'nullable|string',
        ]);
        $rank->update($data);
        return $this->adminTab('ranks', 'Rang frissítve.');
    }

    public function deleteRank($id)
    {
        Rank::findOrFail($id)->delete();
        return $this->adminTab('ranks', 'Rang törölve.');
    }

    public function storeAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'content'         => 'required|string',
            'post_to_discord' => 'nullable|boolean',
            'mention_role_id' => 'nullable|string|max:50',
        ]);

        $announcement = Announcement::create([
            'user_id' => auth()->id(),
            'title'   => $data['title'],
            'content' => strip_tags($data['content'], '<p><b><i><u><h1><h2><h3><ul><ol><li><br><strong><em>'),
        ]);

        if (!empty($data['post_to_discord'])) {
            $channelId   = config('services.discord.announcement_channel_id');
            $factionName = FactionSetting::singleton()->name ?? 'Faction';
            $mention     = $data['mention_role_id'] ?? '';
            app(DiscordService::class)->sendAnnouncement($channelId, $data['title'], strip_tags($data['content']), $factionName, $mention);
        }

        return $this->adminTab('announcements', 'Felhívás közzétéve.');
    }

    public function deleteAnnouncement($id)
    {
        Announcement::findOrFail($id)->delete();
        return $this->adminTab('announcements', 'Felhívás törölve.');
    }

    public function deleteMessage($id)
    {
        Message::findOrFail($id)->delete();
        return $this->adminTab('messages', 'Üzenet törölve.');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'header_text' => 'nullable|string|max:100',
            'logo_url'    => 'nullable|string',
            'favicon_url' => 'nullable|string',
        ]);

        FactionSetting::singleton()->update($data);
        return $this->adminTab('settings', 'Beállítások mentve.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'slug'       => 'required|string|max:50|unique:report_categories,slug',
            'color'      => 'required|string|max:20',
            'sort_order' => 'nullable|integer',
        ]);
        ReportCategory::create($data);
        return $this->adminTab('categories', 'Kategória létrehozva.');
    }

    public function updateCategory(Request $request, $id)
    {
        $cat  = ReportCategory::findOrFail($id);
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'slug'       => "required|string|max:50|unique:report_categories,slug,{$id}",
            'color'      => 'required|string|max:20',
            'sort_order' => 'nullable|integer',
        ]);
        $cat->update($data);
        return $this->adminTab('categories', 'Kategória frissítve.');
    }

    public function destroyCategory($id)
    {
        ReportCategory::findOrFail($id)->delete();
        return $this->adminTab('categories', 'Kategória törölve.');
    }

    public function sendEmbed(Request $request)
    {
        $data = $request->validate([
            'channel_id'    => 'required|string',
            'message_id'    => 'nullable|string',
            'color'         => 'nullable|string|max:7',
            'title'         => 'nullable|string|max:256',
            'description'   => 'nullable|string|max:4096',
            'author_name'   => 'nullable|string|max:256',
            'footer_text'   => 'nullable|string|max:2048',
            'image_url'     => 'nullable|string|max:2048',
            'thumbnail_url' => 'nullable|string|max:2048',
            'fields'        => 'nullable|array|max:25',
            'fields.*.name'   => 'nullable|string|max:256',
            'fields.*.value'  => 'nullable|string|max:1024',
            'fields.*.inline' => 'nullable',
        ]);

        $embed = ['timestamp' => now()->toIso8601String()];

        if (!empty($data['color'])) {
            $embed['color'] = hexdec(ltrim($data['color'], '#'));
        }
        if (!empty($data['author_name'])) {
            $embed['author'] = ['name' => $data['author_name']];
        }
        if (!empty($data['title'])) {
            $embed['title'] = $data['title'];
        }
        if (!empty($data['description'])) {
            $embed['description'] = $data['description'];
        }
        if (!empty($data['thumbnail_url'])) {
            $embed['thumbnail'] = ['url' => $data['thumbnail_url']];
        }
        if (!empty($data['image_url'])) {
            $embed['image'] = ['url' => $data['image_url']];
        }
        if (!empty($data['footer_text'])) {
            $embed['footer'] = ['text' => $data['footer_text']];
        }
        if (!empty($data['fields'])) {
            $embed['fields'] = collect($data['fields'])
                ->filter(fn($f) => !empty($f['name']) || !empty($f['value']))
                ->map(fn($f) => [
                    'name'   => $f['name'] ?? '​',
                    'value'  => $f['value'] ?? '​',
                    'inline' => !empty($f['inline']),
                ])
                ->values()
                ->toArray();
        }

        $token   = config('services.discord.bot_token');
        $discord = app(DiscordService::class);

        if (!empty($data['message_id'])) {
            $ok         = $discord->editMessage($data['channel_id'], $data['message_id'], $embed);
            $successMsg = 'Embed sikeresen frissítve Discord-on.';
        } else {
            $ok         = $discord->sendChannelMessage($data['channel_id'], '', $embed);
            $successMsg = 'Embed sikeresen elküldve Discord-ra.';
        }

        if (!$ok) {
            return redirect(route('admin') . '?tab=discord')->withErrors(['discord' => 'Discord hiba: sikertelen küldés.']);
        }

        return $this->adminTab('discord', $successMsg);
    }

    public function getDiscordMessages(Request $request)
    {
        $channelId = $request->validate(['channel_id' => 'required|string'])['channel_id'];
        $raw       = app(DiscordService::class)->getChannelMessages($channelId);

        if (empty($raw) && !is_array($raw)) {
            return response()->json(['error' => 'Hiba a Discord API-tól'], 422);
        }

        $messages = collect($raw)
            ->filter(fn($m) => !empty($m['embeds']))
            ->map(fn($m) => [
                'id'        => $m['id'],
                'timestamp' => $m['timestamp'],
                'author'    => $m['author']['username'] ?? 'Bot',
                'embed'     => $m['embeds'][0],
            ])
            ->values();

        return response()->json($messages);
    }

    private function adminTab(string $tab, string $success = null, string $error = null)
    {
        $redirect = redirect(route('admin') . '?tab=' . $tab);
        if ($success) $redirect = $redirect->with('success', $success);
        if ($error)   $redirect = $redirect->withErrors(['error' => $error]);
        return $redirect;
    }

}

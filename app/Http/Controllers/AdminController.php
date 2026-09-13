<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ApplicationFormField;
use App\Models\ChangelogEntry;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\DepartmentRank;
use App\Models\FactionApplication;
use App\Models\FactionSetting;
use App\Models\Message;
use App\Models\NavLink;
use App\Models\Notification;
use App\Models\PageSection;
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
        $navLinks        = $user->is_admin ? NavLink::ordered()->get() : collect();
        $pageSections    = $user->is_admin ? PageSection::ordered()->get() : collect();
        $conversations   = $user->is_admin
            ? Conversation::with(['participants', 'rank', 'department', 'messages.author'])
                ->withCount('messages')
                ->whereHas('messages')
                ->get()
                ->sortByDesc(fn($c) => $c->messages->first()?->created_at)
                ->values()
            : collect();
        $factionSettings = FactionSetting::singleton();
        $discordChannels = $user->is_admin ? $discord->getGuildChannels() : [];
        $discordRoles    = $user->is_admin ? $discord->getGuildRoles() : [];
        $categories      = ReportCategory::orderBy('sort_order')->get();
        $allReports      = Report::with('author')->latest()->get();
        $changelogEntries = ChangelogEntry::with('author')->latest()->get();

        return $this->view('admin', compact('users', 'ranks', 'departments', 'announcements', 'conversations', 'navLinks', 'pageSections', 'factionSettings', 'discordChannels', 'discordRoles', 'categories', 'allReports', 'changelogEntries'));
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
            'is_hr'         => 'nullable|boolean',
        ]);

        User::create([
            'username'      => $data['username'],
            'password'      => $data['password'],
            'name'          => $data['name'],
            'in_game_name'  => $data['in_game_name'] ?? null,
            'rank_id'       => $data['rank_id'] ?? null,
            'is_supervisor' => !empty($data['is_supervisor']),
            'is_admin'      => !empty($data['is_admin']),
            'is_hr'         => !empty($data['is_hr']),
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
            'is_hr'                   => 'nullable|boolean',
            'department_id'           => 'nullable|exists:departments,id',
            'department_ids'          => 'nullable|array',
            'department_ids.*'        => 'exists:departments,id',
            'department_rank_id'      => 'nullable|exists:department_ranks,id',
            'is_department_leader'    => 'nullable|boolean',
            'is_department_deputy'    => 'nullable|boolean',
            'rank_up_date'            => 'nullable|date',
            'notification_preference' => 'nullable|in:ALL,MESSAGES_ONLY',
        ]);

        $oldRankId       = $user->rank_id;
        $oldDeptId       = $user->department_id;
        $oldDeptRankId   = $user->department_rank_id;
        $oldIsLeader     = $user->is_department_leader;
        $oldIsDeputy     = $user->is_department_deputy;
        $oldIsAdmin      = $user->is_admin;
        $oldIsSupervisor = $user->is_supervisor;
        $oldIsHr         = $user->is_hr;

        $newPassword = $data['new_password'] ?? null;
        $newDeptIds  = $data['department_ids'] ?? null;
        unset($data['new_password'], $data['department_ids']);

        // Checkboxes are always explicit (unchecked = absent from the request = false) —
        // unlike the other nullable fields below, where null means "leave unchanged".
        $booleanFields = ['is_admin', 'is_supervisor', 'is_hr', 'is_department_leader', 'is_department_deputy'];
        $updateData    = array_filter($data, fn($v) => $v !== null);
        foreach ($booleanFields as $field) {
            $updateData[$field] = !empty($data[$field]);
        }

        $user->update($updateData);

        if ($newPassword) {
            $user->update(['password' => $newPassword]);
        }

        // Collect exactly what changed so the notification only ever mentions the
        // delta, never a dump of the whole profile.
        $changes = [];

        if (!empty($data['rank_id']) && (int)$data['rank_id'] !== (int)$oldRankId) {
            $rankName  = Rank::find($data['rank_id'])?->name ?? 'ismeretlen';
            $changes[] = "Rangod: {$rankName}";

            // Sync rank-based conversation membership
            if ($oldRankId) {
                $oldConv = Conversation::where('type', 'RANK')->where('rank_id', $oldRankId)->first();
                if ($oldConv) $oldConv->participants()->detach($user->id);
            }
            $newConv = Conversation::where('type', 'RANK')->where('rank_id', $data['rank_id'])->first();
            if ($newConv) $newConv->participants()->syncWithoutDetaching([$user->id]);
        }
        if (!empty($data['department_id']) && (int)$data['department_id'] !== (int)$oldDeptId) {
            $deptName  = Department::find($data['department_id'])?->name ?? 'ismeretlen';
            $changes[] = "Elsődleges alosztályod: {$deptName}";
        }
        if (!empty($data['department_rank_id']) && (int)$data['department_rank_id'] !== (int)$oldDeptRankId) {
            $drName    = DepartmentRank::find($data['department_rank_id'])?->name ?? 'ismeretlen';
            $changes[] = "Alosztályon belüli rangod: {$drName}";
        }
        if ($updateData['is_department_leader'] !== (bool)$oldIsLeader) {
            $changes[] = $updateData['is_department_leader'] ? 'Alosztályvezető lettél' : 'Már nem vagy alosztályvezető';
        }
        if ($updateData['is_department_deputy'] !== (bool)$oldIsDeputy) {
            $changes[] = $updateData['is_department_deputy'] ? 'Helyettes vezető lettél' : 'Már nem vagy helyettes vezető';
        }
        if ($updateData['is_admin'] !== (bool)$oldIsAdmin) {
            $changes[] = $updateData['is_admin'] ? 'Admin jogot kaptál' : 'Admin jogod visszavonva';
        }
        if ($updateData['is_supervisor'] !== (bool)$oldIsSupervisor) {
            $changes[] = $updateData['is_supervisor'] ? 'Szupervízor jogot kaptál' : 'Szupervízor jogod visszavonva';
        }
        if ($updateData['is_hr'] !== (bool)$oldIsHr) {
            $changes[] = $updateData['is_hr'] ? 'HR jogot kaptál' : 'HR jogod visszavonva';
        }

        // Sync many-to-many department memberships and their conversations
        if ($newDeptIds !== null) {
            $oldDeptPivotIds = $user->departments()->pluck('departments.id')->toArray();
            $user->departments()->sync($newDeptIds);

            $added   = array_diff($newDeptIds, $oldDeptPivotIds);
            $removed = array_diff($oldDeptPivotIds, $newDeptIds);

            foreach (Department::whereIn('id', $added)->get() as $dept) {
                $conv = $dept->syncConversation();
                $conv->participants()->syncWithoutDetaching([$user->id]);
                $changes[] = "Csatlakoztál: {$dept->name}";
            }
            foreach (Department::whereIn('id', $removed)->get() as $dept) {
                if ($conv = $dept->officialConversation) {
                    $conv->participants()->detach($user->id);
                }
                $changes[] = "Kikerültél: {$dept->name}";
            }
        }

        if (!empty($changes)) {
            $msg = implode("\n", array_map(fn($c) => "• {$c}", $changes));
            if ($user->wantsNotification('general')) {
                Notification::create(['user_id' => $user->id, 'message' => $msg]);
            }
            if ($user->discord_id) {
                app(DiscordService::class)->sendDm($user->discord_id, '', [
                    'title'       => '🔔 Fiókod frissült',
                    'description' => $msg,
                    'color'       => hexdec('3B82F6'),
                    'timestamp'   => now()->toIso8601String(),
                ]);
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

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:departments',
            'short_name'  => 'required|string|max:20',
            'max_members' => 'required|integer|min:0',
        ]);
        $dept = Department::create($data);
        // Auto-create a default "Alosztályvezető" rank for every new department
        DepartmentRank::create(['department_id' => $dept->id, 'name' => 'Alosztályvezető', 'level' => 1]);
        return $this->adminTab('departments', 'Alosztály létrehozva.');
    }

    public function updateDepartmentInfo(Request $request, $id)
    {
        $dept = Department::findOrFail($id);
        $data = $request->validate([
            'name'        => "required|string|max:100|unique:departments,name,{$id}",
            'short_name'  => 'required|string|max:20',
            'max_members' => 'required|integer|min:0',
        ]);
        $dept->update($data);
        return $this->adminTab('departments', 'Alosztály frissítve.');
    }

    public function destroyDepartment($id)
    {
        Department::findOrFail($id)->delete();
        return $this->adminTab('departments', 'Alosztály törölve.');
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
            $channelId   = FactionSetting::singleton()->discord_announcement_channel_id ?: config('services.discord.announcement_channel_id');
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

    public function deleteConversation($id)
    {
        Conversation::findOrFail($id)->delete();
        return $this->adminTab('messages', 'Beszélgetés törölve.');
    }

    public function approveJoinRequest($id)
    {
        $application = FactionApplication::where('status', 'PENDING')->findOrFail($id);
        $application->update(['status' => 'APPROVED', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        $this->notifyApplicant($application, 'Jelentkezésed elfogadva! Add meg mikor érnél rá egy rövid interjúra a Jelentkezéseim oldalon.');

        return redirect()->route('hr.index', ['tab' => 'applications'])->with('success', 'Jelentkezés elfogadva.');
    }

    public function rejectJoinRequest($id)
    {
        $application = FactionApplication::whereIn('status', ['PENDING', 'NEEDS_CHANGES'])->findOrFail($id);
        $application->update(['status' => 'REJECTED', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        $this->notifyApplicant($application, 'Jelentkezésed elutasítva. Új jelentkezést nyújthatsz be a Jelentkezéseim oldalon.');

        return redirect()->route('hr.index', ['tab' => 'applications'])->with('success', 'Jelentkezés elutasítva.');
    }

    public function needsChangesJoinRequest(Request $request, $id)
    {
        $application = FactionApplication::where('status', 'PENDING')->findOrFail($id);
        $data = $request->validate([
            'fields_needing_changes'   => 'required|array|min:1',
            'fields_needing_changes.*' => 'integer|exists:application_form_fields,id',
            'review_note'              => 'nullable|string|max:2000',
        ]);

        $application->update([
            'status'                 => 'NEEDS_CHANGES',
            'fields_needing_changes' => $data['fields_needing_changes'],
            'review_note'            => $data['review_note'] ?? null,
            'reviewed_by'            => auth()->id(),
            'reviewed_at'            => now(),
        ]);

        $this->notifyApplicant($application, 'A jelentkezésed néhány részéhez módosítás szükséges. Nézd meg a Jelentkezéseim oldalon.');

        return redirect()->route('hr.index', ['tab' => 'applications'])->with('success', 'Módosítás kérve a jelentkezőtől.');
    }

    private function notifyApplicant(FactionApplication $application, string $message): void
    {
        $applicant = $application->user;
        Notification::create(['user_id' => $applicant->id, 'message' => $message]);

        if ($applicant->discord_id) {
            app(DiscordService::class)->sendDm($applicant->discord_id, '', [
                'title'       => '📋 Jelentkezés',
                'description' => $message,
                'color'       => hexdec('F97316'),
                'timestamp'   => now()->toIso8601String(),
            ]);
        }
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'header_text'       => 'nullable|string|max:100',
            'logo_url'          => 'nullable|string',
            'favicon_url'       => 'nullable|string',
            'hr_department_id'  => 'nullable|exists:departments,id',
        ]);

        FactionSetting::singleton()->update($data);
        return $this->adminTab('settings', 'Beállítások mentve.');
    }

    public function updateDiscordSettings(Request $request)
    {
        $data = $request->validate([
            'discord_announcement_channel_id' => 'nullable|string|max:50',
            'discord_reports_channel_id'      => 'nullable|string|max:50',
            'discord_applications_channel_id' => 'nullable|string|max:50',
            'discord_audit_channel_id'        => 'nullable|string|max:50',
            'discord_member_role_id'          => 'nullable|string|max:50',
        ]);

        FactionSetting::singleton()->update($data);
        return $this->adminTab('discord', 'Discord csatorna beállítások mentve.');
    }

    public function storeFormField(Request $request)
    {
        $data = $this->validateFormField($request);
        ApplicationFormField::create($data);
        return redirect()->route('hr.index', ['tab' => 'form'])->with('success', 'Mező létrehozva.');
    }

    public function updateFormField(Request $request, $id)
    {
        $field = ApplicationFormField::findOrFail($id);
        $field->update($this->validateFormField($request));
        return redirect()->route('hr.index', ['tab' => 'form'])->with('success', 'Mező frissítve.');
    }

    public function destroyFormField($id)
    {
        ApplicationFormField::findOrFail($id)->delete();
        return redirect()->route('hr.index', ['tab' => 'form'])->with('success', 'Mező törölve.');
    }

    private function validateFormField(Request $request): array
    {
        $data = $request->validate([
            'label'       => 'required|string|max:150',
            'type'        => 'required|in:text,textarea,select,checkbox',
            'options'     => 'nullable|string',
            'is_required' => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
        ]);

        $options = null;
        if ($data['type'] === 'select' && !empty($data['options'])) {
            $options = array_values(array_filter(array_map('trim', explode("\n", $data['options']))));
        }

        return [
            'label'       => $data['label'],
            'type'        => $data['type'],
            'options'     => $options,
            'is_required' => !empty($data['is_required']),
            'sort_order'  => $data['sort_order'] ?? 0,
        ];
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

    public function getAuditLog(Request $request)
    {
        $channelId = FactionSetting::singleton()->discord_audit_channel_id ?: config('services.discord.audit_channel_id');
        if (!$channelId) {
            return response()->json(['error' => 'Nincs beállítva napló csatorna. Állítsd be a Discord fülön.'], 422);
        }

        $raw = app(DiscordService::class)->getChannelMessages($channelId, 50);
        if (empty($raw) && !is_array($raw)) {
            return response()->json(['error' => 'Hiba a Discord API-tól'], 422);
        }

        $entries = collect($raw)
            ->map(fn($m) => [
                'id'        => $m['id'],
                'timestamp' => $m['timestamp'],
                'author'    => $m['author']['username'] ?? 'Bot',
                'content'   => $m['content'] ?? '',
            ])
            ->filter(fn($e) => $e['content'] !== '')
            ->values();

        return response()->json($entries);
    }

    public function storeChangelog(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        ChangelogEntry::create([
            'user_id' => auth()->id(),
            'title'   => $data['title'],
            'content' => $data['content'],
        ]);

        return $this->adminTab('changelog', 'Változásnapló bejegyzés hozzáadva.');
    }

    public function storeNavLink(Request $request)
    {
        $data = $request->validate([
            'label'       => 'required|string|max:50',
            'url'         => 'required|string|max:255',
            'is_external' => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
        ]);
        NavLink::create([
            'label'       => $data['label'],
            'url'         => $data['url'],
            'is_external' => !empty($data['is_external']),
            'sort_order'  => $data['sort_order'] ?? 0,
        ]);
        return $this->adminTab('page-builder', 'Navigációs link létrehozva.');
    }

    public function updateNavLink(Request $request, $id)
    {
        $link = NavLink::findOrFail($id);
        $data = $request->validate([
            'label'       => 'required|string|max:50',
            'url'         => 'required|string|max:255',
            'is_external' => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
        ]);
        $link->update([
            'label'       => $data['label'],
            'url'         => $data['url'],
            'is_external' => !empty($data['is_external']),
            'sort_order'  => $data['sort_order'] ?? 0,
        ]);
        return $this->adminTab('page-builder', 'Navigációs link frissítve.');
    }

    public function destroyNavLink($id)
    {
        NavLink::findOrFail($id)->delete();
        return $this->adminTab('page-builder', 'Navigációs link törölve.');
    }

    public function storePageSection(Request $request)
    {
        $type = $request->validate(['type' => 'required|in:banner,hero,richtext,steps'])['type'];
        $data = $this->validatePageSection($request, $type);
        $data['type'] = $type;
        PageSection::create($data);
        return $this->adminTab('page-builder', 'Szakasz létrehozva.');
    }

    public function updatePageSection(Request $request, $id)
    {
        $section = PageSection::findOrFail($id);
        $data    = $this->validatePageSection($request, $section->type);
        $section->update($data);
        return $this->adminTab('page-builder', 'Szakasz frissítve.');
    }

    public function destroyPageSection($id)
    {
        PageSection::findOrFail($id)->delete();
        return $this->adminTab('page-builder', 'Szakasz törölve.');
    }

    public function togglePageSection($id)
    {
        $section = PageSection::findOrFail($id);
        $section->update(['is_visible' => !$section->is_visible]);
        return $this->adminTab('page-builder', 'Szakasz láthatósága módosítva.');
    }

    private function validatePageSection(Request $request, string $type): array
    {
        $data = match ($type) {
            'banner' => $request->validate([
                'text'    => 'required|string|max:255',
                'url'     => 'nullable|string|max:255',
                'variant' => 'required|in:dark,light,accent',
            ]),
            'hero' => $request->validate([
                'eyebrow'   => 'nullable|string|max:100',
                'title'     => 'required|string|max:150',
                'image_url' => 'nullable|string|max:2048',
                'show_seal' => 'nullable|boolean',
            ]),
            'richtext' => array_merge(
                ['heading' => $request->validate(['richtext_heading' => 'nullable|string|max:150'])['richtext_heading'] ?? null],
                $request->validate(['body' => 'required|string|max:5000', 'show_seal' => 'nullable|boolean'])
            ),
            'steps' => array_merge(
                ['heading' => $request->validate(['steps_heading' => 'required|string|max:150'])['steps_heading']],
                $request->validate(['items' => 'required|array|max:6', 'items.*.title' => 'required|string|max:100', 'items.*.body' => 'required|string|max:500'])
            ),
            default => abort(422, 'Ismeretlen szakasz típus.'),
        };

        if (array_key_exists('show_seal', $data)) $data['show_seal'] = !empty($data['show_seal']);

        $sortOrder = $request->validate(['sort_order' => 'nullable|integer'])['sort_order'] ?? 0;

        return ['data' => $data, 'sort_order' => $sortOrder];
    }

    private function adminTab(string $tab, string $success = null, string $error = null)
    {
        $redirect = redirect(route('admin') . '?tab=' . $tab);
        if ($success) $redirect = $redirect->with('success', $success);
        if ($error)   $redirect = $redirect->withErrors(['error' => $error]);
        return $redirect;
    }

}

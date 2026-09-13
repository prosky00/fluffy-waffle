<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Models\Rank;
use App\Services\DiscordService;
use Illuminate\Http\Request;

class IntranetController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($request->query('dept')) {
            $dept = Department::findOrFail($request->query('dept'));
            if (!$user->is_admin && !$user->departments()->where('departments.id', $dept->id)->exists()) {
                abort(403);
            }
            $conversation = $dept->syncConversation();
            return redirect("/intranet/{$conversation->id}");
        }

        $folder = $request->query('folder', 'inbox');
        $all = $this->userConversations($user);
        $conversations = $this->filterByFolder($all, $user, $folder);
        $folderCounts = $this->folderCounts($all, $user);
        $users = User::orderBy('name')->get(['id', 'name', 'in_game_name']);
        $ranks = Rank::orderBy('level', 'desc')->get();
        $departments = $this->visibleDepartments($user);
        return $this->view('intranet', compact('conversations', 'users', 'ranks', 'departments', 'folder', 'folderCounts'));
    }

    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $conversation = Conversation::with(['participants', 'rank'])->findOrFail($id);

        $isMember = $conversation->participants->contains('id', $user->id);
        if (!$isMember && !$user->is_admin) {
            if (!$this->autoJoin($conversation, $user)) abort(403);
            $conversation->load('participants');
        }

        $messages = Message::with('author')
            ->where('conversation_id', $id)
            ->oldest()
            ->get();

        $folder = $request->query('folder', 'inbox');
        $all = $this->userConversations($user);
        $conversations = $this->filterByFolder($all, $user, $folder);
        $folderCounts = $this->folderCounts($all, $user);
        $users = User::orderBy('name')->get(['id', 'name', 'in_game_name']);
        $ranks = Rank::orderBy('level', 'desc')->get();
        $departments = $this->visibleDepartments($user);

        return $this->view('intranet', compact('conversations', 'conversation', 'messages', 'users', 'ranks', 'departments', 'folder', 'folderCounts'));
    }

    public function toggleStar($id)
    {
        $user = auth()->user();
        $conversation = Conversation::with('participants')->findOrFail($id);
        if (!$conversation->participants->contains('id', $user->id)) {
            if (!$this->autoJoin($conversation, $user)) abort(403);
        }
        $starred = (bool) ($conversation->participants->firstWhere('id', $user->id)?->pivot->starred ?? false);
        $conversation->participants()->updateExistingPivot($user->id, ['starred' => !$starred]);
        return back();
    }

    public function trashConversation($id)
    {
        $user = auth()->user();
        $conversation = Conversation::with('participants')->findOrFail($id);
        if (!$conversation->participants->contains('id', $user->id)) {
            if (!$this->autoJoin($conversation, $user)) abort(403);
        }
        $conversation->participants()->updateExistingPivot($user->id, ['trashed_at' => now()]);
        return redirect('/intranet');
    }

    public function restoreConversation($id)
    {
        $user = auth()->user();
        $conversation = Conversation::with('participants')->findOrFail($id);
        if ($conversation->participants->contains('id', $user->id)) {
            $conversation->participants()->updateExistingPivot($user->id, ['trashed_at' => null]);
        }
        return redirect('/intranet?folder=trash');
    }

    public function pollMessages($id)
    {
        $user = auth()->user();
        $conversation = Conversation::with('participants')->findOrFail($id);

        $isMember = $conversation->participants->contains('id', $user->id);
        if (!$isMember && !$user->is_admin) {
            if (!$this->autoJoin($conversation, $user)) abort(403);
        }

        $after = request('after');
        $query = Message::with('author')->where('conversation_id', $id)->oldest();
        if ($after) {
            $query->where('id', '>', $after);
        }

        return response()->json($query->get()->map(fn($m) => [
            'id'         => $m->id,
            'content'    => $m->content,
            'created_at' => $m->created_at->format('H:i'),
            'author'     => ['id' => $m->author->id, 'name' => $m->author->in_game_name ?? $m->author->name, 'avatar' => $m->author->avatar],
            'to'         => $conversation->displayName($m->author),
        ]));
    }

    public function createConversation(Request $request)
    {
        $data = $request->validate([
            'type'            => 'required|in:DIRECT,GROUP,RANK',
            'name'            => 'nullable|string|max:255',
            'department_id'   => 'nullable|exists:departments,id',
            'rank_id'         => 'nullable|exists:ranks,id',
            'participant_ids' => 'nullable|array',
        ]);

        // GROUP backed by a department → find or create the one official channel
        if ($data['type'] === 'GROUP' && !empty($data['department_id'])) {
            $dept         = Department::findOrFail($data['department_id']);
            $conversation = $dept->syncConversation(); // idempotent
            return redirect("/intranet/{$conversation->id}");
        }

        $conversation = Conversation::create([
            'type'    => $data['type'],
            'name'    => $data['name'] ?? null,
            'rank_id' => $data['rank_id'] ?? null,
        ]);

        $participants = collect($data['participant_ids'] ?? []);
        if ($data['type'] === 'RANK' && $data['rank_id']) {
            $participants = User::where('rank_id', $data['rank_id'])->pluck('id');
        }

        $participants->push(auth()->id())->unique()->each(function ($uid) use ($conversation) {
            $conversation->participants()->attach($uid);
        });

        return redirect("/intranet/{$conversation->id}");
    }

    public function sendMessage(Request $request, $id)
    {
        $data = $request->validate(['content' => 'required|string']);
        $user = auth()->user();

        $conversation = Conversation::with('participants')->findOrFail($id);
        $isMember = $conversation->participants->contains('id', $user->id);
        if (!$isMember) {
            if (!$this->autoJoin($conversation, $user)) abort(403);
        }

        $message = Message::create([
            'conversation_id' => $id,
            'user_id'         => $user->id,
            'content'         => strip_tags($data['content']),
        ]);

        // In-app notifications + Discord DMs for other participants
        $senderName   = $user->in_game_name ?? $user->name;
        $chatName     = $conversation->name ?: $senderName;
        $notifMsg     = "Új üzeneted érkezett ide: {$chatName}";
        $discordPreview = "{$senderName}: " . mb_substr(strip_tags($data['content']), 0, 80);
        $discord      = app(DiscordService::class);
        $conversation->participants
            ->where('id', '!=', $user->id)
            ->each(function (User $p) use ($notifMsg, $discordPreview, $discord) {
                Notification::create(['user_id' => $p->id, 'message' => $notifMsg]);
                if ($p->discord_id) {
                    $discord->sendDm($p->discord_id, "💬 **Intranet üzenet:** {$discordPreview}");
                }
            });

        // Post to Discord channel if GROUP/RANK conversation has one configured
        if ($conversation->discord_channel_id) {
            $mention = $conversation->discord_role_id ? "<@&{$conversation->discord_role_id}>" : '';
            $embed = [
                'author'      => ['name' => $senderName],
                'description' => mb_substr(strip_tags($data['content']), 0, 500),
                'color'       => hexdec('5865F2'),
                'timestamp'   => now()->toIso8601String(),
                'footer'      => ['text' => $conversation->name ?: 'Intranet — Csoportüzenet'],
            ];
            $discord->sendChannelMessage($conversation->discord_channel_id, $mention, $embed);
        }

        if (request()->expectsJson()) {
            return response()->json([
                'id'         => $message->id,
                'content'    => $message->content,
                'created_at' => $message->created_at->format('H:i'),
                'author'     => ['id' => $user->id, 'name' => $user->in_game_name ?? $user->name, 'avatar' => $user->avatar],
                'to'         => $conversation->displayName($user),
            ]);
        }

        return back();
    }

    public function deleteMessage($id)
    {
        $user    = auth()->user();
        $message = Message::findOrFail($id);

        if ($message->user_id !== $user->id && !$user->is_admin) {
            abort(403);
        }

        $message->delete();
        return response()->json(['ok' => true]);
    }

    /** Org folders: admins can browse/message every department; everyone else only
     *  sees the department(s) they actually belong to. */
    private function visibleDepartments(User $user)
    {
        $query = Department::orderBy('name');
        if (!$user->is_admin) {
            $query->whereHas('members', fn($q) => $q->where('users.id', $user->id));
        }
        return $query->get(['id', 'name', 'discord_role_id', 'discord_channel_id']);
    }

    private function userConversations(User $user)
    {
        $deptIds = $user->departments()->pluck('departments.id');

        return Conversation::with(['participants', 'department', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->where(function ($q) use ($user, $deptIds) {
                // explicit participant
                $q->whereHas('participants', fn($q2) => $q2->where('users.id', $user->id));
                // OR official dept channel the user belongs to (auto-joins on open)
                if ($deptIds->isNotEmpty()) {
                    $q->orWhereIn('department_id', $deptIds);
                }
                // OR rank channel matching the user's current rank (auto-joins on open)
                if ($user->rank_id) {
                    $q->orWhere(fn($q2) => $q2->where('type', 'RANK')->where('rank_id', $user->rank_id));
                }
            })
            ->latest()
            ->get();
    }

    /** Folder is per-user state (starred/trashed), read off the participant pivot. Auto-joined
     *  conversations (dept/rank channels the user hasn't opened yet) have no pivot row yet — treat
     *  those as plain inbox: not starred, not trashed. */
    private function filterByFolder($conversations, User $user, string $folder)
    {
        return $conversations->filter(function ($conv) use ($user, $folder) {
            $pivot   = $conv->participants->firstWhere('id', $user->id)?->pivot;
            $trashed = $pivot && $pivot->trashed_at !== null;
            $starred = $pivot && $pivot->starred;
            return match ($folder) {
                'trash'   => $trashed,
                'starred' => $starred && !$trashed,
                'sent'    => !$trashed && $conv->messages->first()?->user_id === $user->id,
                default   => !$trashed,
            };
        })->values();
    }

    private function folderCounts($conversations, User $user): array
    {
        return [
            'inbox'   => $this->filterByFolder($conversations, $user, 'inbox')->count(),
            'starred' => $this->filterByFolder($conversations, $user, 'starred')->count(),
            'sent'    => $this->filterByFolder($conversations, $user, 'sent')->count(),
            'trash'   => $this->filterByFolder($conversations, $user, 'trash')->count(),
        ];
    }

    private function autoJoin(Conversation $conversation, User $user): bool
    {
        // Dept-backed GROUP conversation
        if ($conversation->department_id && $user->departments()->where('departments.id', $conversation->department_id)->exists()) {
            $conversation->participants()->syncWithoutDetaching([$user->id]);
            return true;
        }
        // RANK conversation matching the user's current rank
        if ($conversation->type === 'RANK' && $conversation->rank_id && (int)$user->rank_id === (int)$conversation->rank_id) {
            $conversation->participants()->syncWithoutDetaching([$user->id]);
            return true;
        }
        return false;
    }
}

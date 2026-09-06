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
    public function index()
    {
        $user = auth()->user();
        $conversations = $this->userConversations($user);
        $users = User::orderBy('name')->get(['id', 'name', 'in_game_name']);
        $ranks = Rank::orderBy('level', 'desc')->get();
        $departments = Department::orderBy('name')->get(['id', 'name', 'discord_role_id', 'discord_channel_id']);
        return $this->view('intranet', compact('conversations', 'users', 'ranks', 'departments'));
    }

    public function show($id)
    {
        $user = auth()->user();
        $conversation = Conversation::with(['participants', 'rank'])->findOrFail($id);

        $isMember = $conversation->participants->contains('id', $user->id);
        if (!$isMember && !$user->is_admin) {
            if (!$this->autoJoin($conversation, $user)) abort(403);
        }

        $messages = Message::with('author')
            ->where('conversation_id', $id)
            ->oldest()
            ->get();

        $conversations = $this->userConversations($user);
        $users = User::orderBy('name')->get(['id', 'name', 'in_game_name']);
        $ranks = Rank::orderBy('level', 'desc')->get();
        $departments = Department::orderBy('name')->get(['id', 'name', 'discord_role_id', 'discord_channel_id']);

        return $this->view('intranet', compact('conversations', 'conversation', 'messages', 'users', 'ranks', 'departments'));
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
        ]));
    }

    public function createConversation(Request $request)
    {
        $data = $request->validate([
            'type'            => 'required|in:DIRECT,GROUP,RANK',
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

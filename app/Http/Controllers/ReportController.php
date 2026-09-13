<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\FactionSetting;
use App\Models\Notification;
use App\Models\User;
use App\Services\DiscordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ReportController extends Controller
{
    public function index()
    {
        $user       = auth()->user();
        $canManage  = $user->is_admin || $user->is_supervisor;
        $categories = ReportCategory::orderBy('sort_order')->get();

        $myReports = Report::with(['author', 'connectedUsers'])
            ->where(function ($q) use ($user) {
                $q->where('author_id', $user->id)
                  ->orWhereHas('connectedUsers', fn($q) => $q->where('users.id', $user->id));
            })
            ->latest()
            ->get();

        $allReports = $canManage
            ? Report::with(['author', 'connectedUsers'])->latest()->get()
            : collect();

        $users = User::orderBy('name')->get(['id', 'name', 'in_game_name']);
        return $this->view('jelentesek', compact('myReports', 'allReports', 'canManage', 'categories', 'users'));
    }

    public function store(Request $request)
    {
        $validSlugs = ReportCategory::pluck('slug')->implode(',');
        $data = $request->validate([
            'title'             => 'required|string|max:255',
            'content'           => 'required|string',
            'category'          => "required|exists:report_categories,slug",
            'status'            => 'required|in:DRAFT,SUBMITTED',
            'connected_user_ids'=> 'nullable|array',
        ]);

        $report = Report::create([
            'author_id' => auth()->id(),
            'title'     => $data['title'],
            'content'   => $data['content'],
            'category'  => $data['category'],
            'status'    => $data['status'],
        ]);

        if (!empty($data['connected_user_ids'])) {
            $ids = array_filter($data['connected_user_ids'], fn($id) => $id != auth()->id());
            $report->connectedUsers()->attach($ids);
        }

        if ($data['status'] === 'SUBMITTED') {
            $this->notifyAdmins($report);
        }

        return redirect()->route('jelentesek')->with('success', 'Jelentés mentve.');
    }

    public function show($id)
    {
        $report     = Report::with(['author', 'connectedUsers'])->findOrFail($id);
        $this->authorizeReport($report);
        $users      = User::orderBy('name')->get(['id', 'name', 'in_game_name']);
        $categories = ReportCategory::orderBy('sort_order')->get();
        return $this->view('jelentesek-show', compact('report', 'users', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $report = Report::with('connectedUsers')->findOrFail($id);
        $user   = auth()->user();

        $isAuthor    = $report->author_id === $user->id;
        $isConnected = $report->connectedUsers->contains('id', $user->id);

        $canManage = $user->is_admin || $user->is_supervisor;

        if (!$canManage && !$isAuthor && !$isConnected) {
            abort(403);
        }
        if (!$canManage && $report->status === 'APPROVED') {
            abort(403, 'Jóváhagyott jelentés nem szerkeszthető.');
        }

        $data = $request->validate([
            'title'              => 'sometimes|string|max:255',
            'content'            => 'sometimes|string',
            'category'           => 'sometimes|exists:report_categories,slug',
            'status'             => 'sometimes|in:DRAFT,SUBMITTED,APPROVED,REJECTED',
            'admin_comment'      => 'nullable|string',
            'connected_user_ids' => 'nullable|array',
        ]);

        $oldStatus = $report->status;

        if (isset($data['content'])) {
            $data['content'] = strip_tags($data['content'], '<p><b><i><u><h1><h2><h3><ul><ol><li><br><strong><em><a>');
        }

        if (isset($data['status']) && $data['status'] === 'SUBMITTED' && $oldStatus === 'REJECTED') {
            $data['admin_comment'] = null;
        }

        $report->update($data);

        if (isset($data['connected_user_ids'])) {
            $ids = array_filter($data['connected_user_ids'], fn($id) => $id != $user->id);
            $report->connectedUsers()->sync($ids);
        }

        $newStatus = $data['status'] ?? null;

        if ($newStatus === 'SUBMITTED' && $oldStatus !== 'SUBMITTED') {
            $this->notifyAdmins($report);
        }

        if (in_array($newStatus, ['APPROVED', 'REJECTED']) && $oldStatus !== $newStatus) {
            $label  = $newStatus === 'APPROVED' ? 'jóváhagyva' : 'elutasítva';
            $actor  = $user->in_game_name ?? $user->name;

            $this->logAudit($newStatus === 'APPROVED' ? '✅ Jelentés jóváhagyva' : '❌ Jelentés elutasítva', [
                'Végrehajtotta' => $actor,
                'Jelentés'      => $report->title,
                'Szerző'        => $report->author->in_game_name ?? $report->author->name,
            ]);
            $suffix = ($newStatus === 'REJECTED' && !empty($data['admin_comment']))
                ? " Ok: {$data['admin_comment']}" : '';
            $msg    = "\"{$report->title}\" jelentésedet {$actor} {$label}.{$suffix}";

            $report->load('connectedUsers');
            $notifyIds = collect([$report->author_id])
                ->merge($report->connectedUsers->pluck('id'))
                ->unique()
                ->filter(fn($uid) => $uid !== $user->id);

            $notifyUsers = User::whereIn('id', $notifyIds)->get(['id', 'discord_id', 'notification_preference']);
            foreach ($notifyUsers as $notifyUser) {
                if ($notifyUser->wantsNotification('general')) {
                    Notification::create(['user_id' => $notifyUser->id, 'message' => $msg]);
                }
                if ($notifyUser->discord_id) {
                    app(DiscordService::class)->sendDm($notifyUser->discord_id, '', [
                        'title'       => '📋 Jelentés',
                        'description' => $msg,
                        'color'       => hexdec('F97316'),
                        'timestamp'   => now()->toIso8601String(),
                    ]);
                }
            }
        }

        return redirect()->route('jelentesek')->with('success', 'Mentve.');
    }

    public function destroy($id)
    {
        $report = Report::with('author')->findOrFail($id);
        $user   = auth()->user();
        $isAuthor = $report->author_id === $user->id;

        $canManage = $user->is_admin || $user->is_supervisor;
        if (!$canManage && !($isAuthor && $report->status === 'DRAFT')) {
            abort(403);
        }

        $title  = $report->title;
        $author = $report->author->in_game_name ?? $report->author->name;
        $report->delete();

        if (!$isAuthor) {
            $this->logAudit('🗑️ Jelentés törölve (admin)', [
                'Végrehajtotta' => $this->actorName(),
                'Jelentés'      => $title,
                'Szerző'        => $author,
            ]);
        }

        return redirect()->route('jelentesek')->with('success', 'Jelentés törölve.');
    }

    private function authorizeReport(Report $report)
    {
        $user = auth()->user();
        $isAuthor    = $report->author_id === $user->id;
        $isConnected = $report->connectedUsers->contains('id', $user->id);
        $isPublic    = in_array($report->status, ['SUBMITTED', 'APPROVED']);

        $canManage = $user->is_admin || $user->is_supervisor;
        if (!$isAuthor && !$isConnected && !$canManage && !$isPublic) {
            abort(403);
        }
    }

    private function notifyAdmins(Report $report): void
    {
        $report->loadMissing('author');
        $characterName = $report->author->in_game_name ?? $report->author->name ?? 'Ismeretlen';
        $inAppMsg      = "Új beküldött jelentés: \"{$report->title}\" — {$characterName}";

        // In-app notifications for all admins and supervisors
        $managers = User::where(fn($q) => $q->where('is_admin', true)->orWhere('is_supervisor', true))->get(['id']);
        foreach ($managers as $manager) {
            Notification::create(['user_id' => $manager->id, 'message' => $inAppMsg]);
        }

        // Discord channel embed
        $channelId = FactionSetting::singleton()->discord_reports_channel_id ?: config('services.discord.reports_channel_id');
        $token     = config('services.discord.bot_token');
        if (!$channelId || !$token) return;

        $reportUrl  = rtrim(config('app.url'), '/') . '/jelentesek/' . $report->id;
        $categories = ['PATROL' => 'Járőr', 'INCIDENT' => 'Incidens', 'TRAINING' => 'Képzés', 'MEETING' => 'Gyűlés', 'OTHER' => 'Egyéb'];

        Http::withHeaders(['Authorization' => "Bot {$token}"])
            ->post("https://discord.com/api/v10/channels/{$channelId}/messages", [
                'embeds' => [[
                    'title'     => $report->title,
                    'url'       => $reportUrl,
                    'color'     => hexdec('f59e0b'), // amber
                    'fields'    => [
                        ['name' => 'Karakter neve', 'value' => $characterName,                                      'inline' => true],
                        ['name' => 'Kategória',     'value' => $categories[$report->category] ?? $report->category, 'inline' => true],
                        ['name' => 'Állapot',       'value' => 'Beküldve',                                          'inline' => true],
                    ],
                    'timestamp' => now()->toIso8601String(),
                    'footer'    => ['text' => 'Kattints a címre a jelentés megnyitásához'],
                ]],
            ]);
    }
}

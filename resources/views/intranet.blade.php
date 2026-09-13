@extends('layouts.app')
@section('title', 'Intranet')

@push('styles')
<style>
.mail-search { margin-bottom:16px; }
.mail-search input {
    width:100%; max-width:480px; margin:0 auto; display:block;
    background:var(--surface); border:1px solid var(--border); border-radius:8px;
    color:var(--fg); padding:9px 14px; font-size:13px;
}
.mail-search input:focus { outline:none; border-color:var(--accent); }
.mail-toolbar { display:flex; align-items:center; gap:8px; margin-bottom:16px; }
.mail-toolbar-title { color:var(--fg); font-size:14px; font-weight:600; }
.mail-toolbar-count { color:var(--fg-subtle); font-size:12px; margin-left:6px; }
.mail-toolbar-actions { margin-left:auto; display:flex; gap:8px; }

.mail-layout { display:flex; gap:0; height:calc(100vh - 180px); min-height:420px; }

.mail-folders { width:220px; flex-shrink:0; background:var(--bg); border:1px solid var(--border); border-radius:12px 0 0 12px; padding:14px 10px; overflow-y:auto; }
.mail-compose-btn { width:100%; margin-bottom:14px; justify-content:center; }
.mail-folder { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:6px; color:var(--fg-muted); font-size:13px; text-decoration:none; margin-bottom:2px; transition:background .1s; }
.mail-folder:hover { background:var(--surface); color:var(--fg); }
.mail-folder.active { background:var(--surface-2); color:var(--accent); font-weight:600; }
.mail-folder svg { flex-shrink:0; }
.mail-folder-count { margin-left:auto; color:var(--fg-subtle); font-size:11px; }
.mail-folder-category { color:var(--fg-subtle); font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; margin:16px 10px 6px; }

.mail-list { width:300px; flex-shrink:0; background:var(--bg); border-top:1px solid var(--border); border-bottom:1px solid var(--border); border-left:1px solid var(--border); overflow-y:auto; }
.mail-row { display:block; padding:12px 16px; border-bottom:1px solid var(--border); cursor:pointer; text-decoration:none; transition:background .1s; }
.mail-row:hover, .mail-row.active { background:var(--surface); }
.mail-row-top { display:flex; align-items:baseline; justify-content:space-between; gap:8px; margin-bottom:3px; }
.mail-row-name { color:var(--fg); font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mail-row-date { color:var(--fg-subtle); font-size:11px; white-space:nowrap; flex-shrink:0; }
.mail-row-subject { color:var(--fg-muted); font-size:12px; font-weight:500; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mail-row-preview { color:var(--fg-subtle); font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mail-empty { padding:24px 16px; color:var(--fg-subtle); font-size:13px; text-align:center; }

.mail-reading { flex:1; display:flex; flex-direction:column; background:var(--bg); border:1px solid var(--border); border-radius:0 12px 12px 0; overflow:hidden; }
.mail-reading-body { flex:1; overflow-y:auto; padding:24px; }
.mail-subject { color:var(--fg); font-size:20px; font-weight:700; margin-bottom:18px; }
.mail-thread-item { display:flex; gap:12px; padding-bottom:20px; margin-bottom:20px; border-bottom:1px solid var(--border); }
.mail-thread-item:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
.mail-avatar, .mail-avatar-ph { width:38px; height:38px; border-radius:50%; flex-shrink:0; object-fit:cover; }
.mail-avatar-ph { background:var(--surface-3); display:flex; align-items:center; justify-content:center; color:var(--accent); font-size:14px; font-weight:700; }
.mail-meta { color:var(--fg-subtle); font-size:12px; line-height:1.7; margin-bottom:8px; }
.mail-meta b { color:var(--fg-muted); font-weight:600; }
.mail-body-text { color:var(--fg); font-size:14px; line-height:1.6; white-space:pre-wrap; }
.mail-del { background:none; border:none; color:var(--fg-subtle); font-size:11px; cursor:pointer; padding:2px 4px; margin-top:6px; }
.mail-del:hover { color:var(--destructive); }
.mail-no-selection { flex:1; display:flex; align-items:center; justify-content:center; color:var(--fg-subtle); font-size:14px; }

.mail-reply { padding:16px 24px; border-top:1px solid var(--border); display:flex; gap:8px; }
.mail-reply textarea { flex:1; background:var(--surface); border:1px solid var(--border); border-radius:6px; color:var(--fg); padding:8px 12px; font-size:14px; resize:none; font-family:inherit; }
.mail-reply textarea:focus { outline:none; border-color:var(--accent); }
</style>
@endpush

@section('content')
@php
    $folder = $folder ?? 'inbox';
    $folderLabels = ['inbox' => 'Beérkezett', 'starred' => 'Csillagozott', 'sent' => 'Elküldött', 'trash' => 'Kuka'];
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
    <h1 style="color:var(--fg);font-size:24px;font-weight:700;flex:1">Intranet</h1>
</div>

<div class="mail-search">
    <input type="text" id="mailSearchInput" placeholder="Keresés leveleidben..." oninput="filterMailList()">
</div>

<div class="mail-toolbar">
    <span class="mail-toolbar-title">{{ $folderLabels[$folder] ?? 'Beérkezett' }}</span>
    <span class="mail-toolbar-count">{{ count($conversations) }} levél</span>
    @if(isset($conversation))
    <div class="mail-toolbar-actions">
        @php $isStarred = (bool) ($conversation->participants->firstWhere('id', auth()->id())?->pivot->starred ?? false); @endphp
        <form method="POST" action="/intranet/{{ $conversation->id }}/star" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-ghost" style="font-size:12px">{{ $isStarred ? '★' : '☆' }} Csillagozás</button>
        </form>
        <button type="button" class="btn btn-primary" style="font-size:12px" onclick="focusReply()">↩ Válasz</button>
        <button type="button" class="btn btn-ghost" style="font-size:12px" onclick="focusReply()">↪ Válasz mindenkinek</button>
        <form method="POST" action="/intranet/{{ $conversation->id }}/trash" onsubmit="return confirm('Áthelyezés a kukába?')" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-ghost" style="font-size:12px">Kukába</button>
        </form>
        @if($folder === 'trash')
        <form method="POST" action="/intranet/{{ $conversation->id }}/restore" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-ghost" style="font-size:12px">Visszaállítás</button>
        </form>
        @endif
    </div>
    @endif
</div>

<div class="mail-layout">
    {{-- Folders --}}
    <div class="mail-folders">
        <button class="btn btn-primary mail-compose-btn" onclick="openModal('newConvModal')">✎ Levélírás</button>

        <a href="/intranet?folder=inbox" class="mail-folder {{ $folder==='inbox' ? 'active' : '' }}">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
            Beérkezett <span class="mail-folder-count">{{ $folderCounts['inbox'] ?? 0 }}</span>
        </a>
        <a href="/intranet?folder=starred" class="mail-folder {{ $folder==='starred' ? 'active' : '' }}">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Csillagozott <span class="mail-folder-count">{{ $folderCounts['starred'] ?? 0 }}</span>
        </a>
        <a href="/intranet?folder=sent" class="mail-folder {{ $folder==='sent' ? 'active' : '' }}">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Elküldött <span class="mail-folder-count">{{ $folderCounts['sent'] ?? 0 }}</span>
        </a>
        <a href="/intranet?folder=trash" class="mail-folder {{ $folder==='trash' ? 'active' : '' }}">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Kuka <span class="mail-folder-count">{{ $folderCounts['trash'] ?? 0 }}</span>
        </a>

        <div class="mail-folder-category">Szervezeti mappák</div>
        @foreach($departments as $dept)
        <a href="/intranet?dept={{ $dept->id }}" class="mail-folder">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            {{ $dept->name }}
        </a>
        @endforeach
    </div>

    {{-- Message list --}}
    <div class="mail-list" id="mailList">
        @forelse($conversations as $conv)
        @php $lastMsg = $conv->messages->first(); @endphp
        <a href="/intranet/{{ $conv->id }}?folder={{ $folder }}" class="mail-row-link" data-search="{{ strtolower($conv->displayName(auth()->user()).' '.($conv->name ?? '').' '.($lastMsg->content ?? '')) }}">
            <div class="mail-row {{ isset($conversation) && $conversation->id===$conv->id ? 'active' : '' }}">
                <div class="mail-row-top">
                    <span class="mail-row-name">{{ $conv->displayName(auth()->user()) }}</span>
                    <span class="mail-row-date">{{ $lastMsg?->created_at->format('m. d. H:i') }}</span>
                </div>
                @if($conv->name)
                <div class="mail-row-subject">{{ $conv->name }}</div>
                @endif
                <div class="mail-row-preview">{{ $lastMsg ? Str::limit(strip_tags($lastMsg->content), 60) : 'Nincs üzenet' }}</div>
            </div>
        </a>
        @empty
        <div class="mail-empty">Nincs levél ebben a mappában.</div>
        @endforelse
    </div>

    {{-- Reading pane --}}
    <div class="mail-reading">
        @if(isset($conversation))
        <div class="mail-reading-body" id="msgContainer">
            <div class="mail-subject">{{ $conversation->name ?: $conversation->displayName(auth()->user()) }}</div>
            @foreach($messages as $msg)
            @php $authorDisplayName = $msg->author->in_game_name ?? $msg->author->name; @endphp
            <div class="mail-thread-item" id="msg-{{ $msg->id }}">
                @if($msg->author->avatar)
                    <img src="{{ $msg->author->avatar }}" class="mail-avatar">
                @else
                    <div class="mail-avatar-ph">{{ strtoupper(substr($authorDisplayName, 0, 1)) }}</div>
                @endif
                <div style="flex:1;min-width:0">
                    <div class="mail-meta">
                        <b>From:</b> {{ $authorDisplayName }}<br>
                        <b>To:</b> {{ $conversation->displayName($msg->author) }}<br>
                        <b>Date:</b> {{ $msg->created_at->format('Y. m. d. H:i') }}
                    </div>
                    <div class="mail-body-text">{{ $msg->content }}</div>
                    @if($msg->user_id === auth()->id() || auth()->user()->is_admin)
                    <button class="mail-del" onclick="deleteMsg({{ $msg->id }})">Törlés</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="mail-reply">
            <textarea id="msgInput" rows="2" placeholder="Válasz írása..." onkeydown="handleKey(event)"></textarea>
            <button class="btn btn-primary" onclick="sendMsg()">Küldés</button>
        </div>
        @else
        <div class="mail-no-selection">Válassz egy levelet a listából</div>
        @endif
    </div>
</div>

{{-- New conversation modal --}}
<div class="modal-backdrop" id="newConvModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('newConvModal')">&times;</button>
        <div class="modal-title">Levélírás</div>
        <form method="POST" action="/intranet/conversations">
            @csrf
            <div style="margin-bottom:12px">
                <label class="form-label">Típus</label>
                <select name="type" class="form-select" id="convType" onchange="toggleConvFields()">
                    <option value="DIRECT">Közvetlen (1:1)</option>
                    <option value="GROUP">Csoport</option>
                    <option value="RANK">Rang alapú</option>
                </select>
            </div>
            <div style="margin-bottom:12px">
                <label class="form-label">Tárgy (opcionális)</label>
                <input type="text" name="name" class="form-input" placeholder="Tárgy">
            </div>
            <div id="convGroupField" style="display:none;margin-bottom:12px">
                <label class="form-label">Alosztály / Csoport</label>
                <select name="department_id" class="form-select" id="convDeptSelect" onchange="onDeptChange()">
                    <option value="">— Válassz alosztályt —</option>
                    @foreach($allDepartments as $dept)
                    <option value="{{ $dept->id }}"
                            data-role="{{ $dept->discord_role_id }}"
                            data-channel="{{ $dept->discord_channel_id }}">
                        {{ $dept->name }}
                    </option>
                    @endforeach
                </select>
                <div id="deptDiscordHint" style="display:none;margin-top:6px;font-size:11px;color:var(--fg-muted)">
                    Discord: <span id="deptRoleHint"></span>
                </div>
            </div>
            <input type="hidden" name="discord_role_id" id="convDiscordRole">
            <input type="hidden" name="discord_channel_id" id="convDiscordChannel">
            <div id="convParticipants" style="margin-bottom:12px">
                <label class="form-label">Résztvevők</label>
                <select name="participant_ids[]" class="form-select" multiple style="height:120px">
                    @foreach($users as $u)
                        @if($u->id !== auth()->id())
                        <option value="{{ $u->id }}">{{ $u->in_game_name ?? $u->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div id="convRankField" style="display:none;margin-bottom:12px">
                <label class="form-label">Rendfokozat</label>
                <select name="rank_id" class="form-select">
                    @foreach($ranks as $r)
                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Létrehozás</button>
                <button type="button" onclick="closeModal('newConvModal')" class="btn btn-ghost">Mégse</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleConvFields() {
    const type = document.getElementById('convType').value;
    document.getElementById('convGroupField').style.display    = type === 'GROUP' ? 'block' : 'none';
    document.getElementById('convRankField').style.display     = type === 'RANK'  ? 'block' : 'none';
    document.getElementById('convParticipants').style.display  = type === 'RANK'  ? 'none'  : 'block';
    if (type !== 'GROUP') {
        document.getElementById('convDiscordRole').value = '';
        document.getElementById('convDiscordChannel').value = '';
    }
}

function onDeptChange() {
    const sel = document.getElementById('convDeptSelect');
    const opt = sel.options[sel.selectedIndex];
    const role    = opt.dataset.role    || '';
    const channel = opt.dataset.channel || '';
    document.getElementById('convDiscordRole').value    = role;
    document.getElementById('convDiscordChannel').value = channel;
    const hint = document.getElementById('deptDiscordHint');
    if (role || channel) {
        document.getElementById('deptRoleHint').textContent = [role && 'Szerepkör: '+role, channel && 'Csatorna: '+channel].filter(Boolean).join(' · ');
        hint.style.display = '';
    } else {
        hint.style.display = 'none';
    }
}

function filterMailList() {
    const q = document.getElementById('mailSearchInput').value.trim().toLowerCase();
    document.querySelectorAll('#mailList .mail-row-link').forEach(a => {
        a.style.display = !q || a.dataset.search.includes(q) ? '' : 'none';
    });
}

function focusReply() {
    const input = document.getElementById('msgInput');
    if (input) { input.scrollIntoView({behavior:'smooth', block:'center'}); input.focus(); }
}

@if(isset($conversation))
const convId  = {{ $conversation->id }};
const authId  = {{ auth()->id() }};
const isAdmin = {{ auth()->user()->is_admin ? 'true' : 'false' }};
let lastId    = {{ $messages->last()?->id ?? 0 }};

const container = document.getElementById('msgContainer');
if (container) container.scrollTop = container.scrollHeight;

function sendMsg() {
    const input = document.getElementById('msgInput');
    const text  = input.value.trim();
    if (!text) return;
    input.value = '';
    fetch(`/intranet/${convId}/messages`, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
        body: JSON.stringify({content: text})
    }).then(r => r.json()).then(appendMsg);
}

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
}

function appendMsg(msg) {
    const isOwn = msg.author.id === authId;
    const div = document.createElement('div');
    div.id    = `msg-${msg.id}`;
    div.className = 'mail-thread-item';
    const avatarHtml = msg.author.avatar
        ? `<img src="${msg.author.avatar}" class="mail-avatar">`
        : `<div class="mail-avatar-ph">${msg.author.name[0].toUpperCase()}</div>`;
    const delBtn = (isOwn || isAdmin)
        ? `<button class="mail-del" onclick="deleteMsg(${msg.id})">Törlés</button>` : '';
    div.innerHTML = `${avatarHtml}<div style="flex:1;min-width:0"><div class="mail-meta"><b>From:</b> ${msg.author.name}<br><b>To:</b> ${msg.to}<br><b>Date:</b> ${msg.created_at}</div><div class="mail-body-text"></div>${delBtn}</div>`;
    div.querySelector('.mail-body-text').textContent = msg.content;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    if (msg.id > lastId) lastId = msg.id;
}

function deleteMsg(id) {
    if (!confirm('Biztosan törlöd?')) return;
    fetch(`/intranet/messages/${id}`, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content}
    }).then(() => { const el = document.getElementById(`msg-${id}`); if (el) el.remove(); });
}

// Poll every 3 seconds
setInterval(() => {
    fetch(`/intranet/${convId}/messages?after=${lastId}`)
        .then(r => r.json())
        .then(msgs => msgs.forEach(m => { appendMsg(m); }));
}, 3000);
@endif
</script>
@endpush

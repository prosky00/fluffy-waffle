@extends('layouts.app')
@section('title', 'Intranet')

@push('styles')
<style>
.chat-layout { display:flex; gap:0; height:calc(100vh - 96px); }
.conv-list { width:280px; flex-shrink:0; background:var(--bg); border:1px solid var(--border); border-radius:12px 0 0 12px; overflow-y:auto; }
.conv-item { padding:12px 16px; border-bottom:1px solid var(--border); cursor:pointer; transition:background .15s; }
.conv-item:hover, .conv-item.active { background:var(--surface); }
.conv-name { color:var(--fg); font-size:13px; font-weight:500; }
.conv-preview { color:var(--fg-subtle); font-size:12px; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chat-area { flex:1; display:flex; flex-direction:column; background:var(--bg); border:1px solid var(--border); border-left:none; border-radius:0 12px 12px 0; }
.chat-header { padding:16px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px; }
.chat-messages { flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:12px; }
.msg-row { display:flex; gap:10px; align-items:flex-start; }
.msg-row.own { flex-direction:row-reverse; }
.msg-avatar { width:32px; height:32px; border-radius:50%; object-fit:cover; flex-shrink:0; }
.msg-avatar-ph { width:32px; height:32px; border-radius:50%; background:var(--surface-3); display:flex; align-items:center; justify-content:center; color:var(--accent); font-size:12px; font-weight:700; flex-shrink:0; }
.msg-bubble { background:var(--surface); border-radius:8px; padding:8px 12px; max-width:70%; position:relative; }
.msg-row.own .msg-bubble { background:var(--surface-2); }
.msg-author { color:var(--fg-muted); font-size:11px; margin-bottom:4px; }
.msg-content { color:var(--fg); font-size:13px; line-height:1.5; }
.msg-time { color:var(--fg-subtle); font-size:10px; margin-top:4px; }
.msg-del { background:none; border:none; color:var(--fg-subtle); font-size:12px; cursor:pointer; padding:2px 4px; }
.msg-del:hover { color:var(--destructive); }
.chat-input { padding:16px; border-top:1px solid var(--border); display:flex; gap:8px; }
.chat-input textarea { flex:1; background:var(--bg); border:1px solid var(--border); border-radius:6px; color:var(--fg); padding:8px 12px; font-size:14px; resize:none; font-family:inherit; }
.chat-input textarea:focus { outline:none; border-color:var(--accent); }
.no-chat { flex:1; display:flex; align-items:center; justify-content:center; color:var(--fg-subtle); font-size:14px; }
</style>
@endpush

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
    <h1 style="color:var(--fg);font-size:24px;font-weight:700;flex:1">Intranet</h1>
    <button class="btn btn-primary" onclick="openModal('newConvModal')">+ Új üzenet</button>
</div>

<div class="chat-layout">
    {{-- Conversation list --}}
    <div class="conv-list">
        @forelse($conversations as $conv)
        @php $lastMsg = $conv->messages->first(); @endphp
        <a href="/intranet/{{ $conv->id }}" style="text-decoration:none">
            <div class="conv-item {{ isset($conversation) && $conversation->id===$conv->id ? 'active' : '' }}">
                <div class="conv-name">{{ $conv->displayName(auth()->user()) }}</div>
                <div class="conv-preview">{{ $lastMsg ? Str::limit(strip_tags($lastMsg->content), 40) : 'Nincs üzenet' }}</div>
            </div>
        </a>
        @empty
        <div style="padding:20px;color:var(--fg-subtle);font-size:13px">Nincs üzenetváltás.</div>
        @endforelse
    </div>

    {{-- Chat area --}}
    <div class="chat-area">
        @if(isset($conversation))
        <div class="chat-header">
            <div style="color:var(--fg);font-size:15px;font-weight:600">{{ $conversation->displayName(auth()->user()) }}</div>
            <span class="badge badge-gray" style="margin-left:auto">{{ $conversation->typeLabel() }}</span>
        </div>

        <div class="chat-messages" id="msgContainer">
            @foreach($messages as $msg)
            <div class="msg-row {{ $msg->user_id === auth()->id() ? 'own' : '' }}" id="msg-{{ $msg->id }}">
                @php $authorDisplayName = $msg->author->in_game_name ?? $msg->author->name; @endphp
                @if($msg->author->avatar)
                    <img src="{{ $msg->author->avatar }}" class="msg-avatar">
                @else
                    <div class="msg-avatar-ph">{{ strtoupper(substr($authorDisplayName, 0, 1)) }}</div>
                @endif
                <div class="msg-bubble">
                    <div class="msg-author">{{ $authorDisplayName }}</div>
                    <div class="msg-content">{{ $msg->content }}</div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="msg-time">{{ $msg->created_at->format('H:i') }}</div>
                        @if($msg->user_id === auth()->id() || auth()->user()->is_admin)
                        <button class="msg-del" onclick="deleteMsg({{ $msg->id }})">Törlés</button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="chat-input">
            <textarea id="msgInput" rows="2" placeholder="Írj üzenetet..." onkeydown="handleKey(event)"></textarea>
            <button class="btn btn-primary" onclick="sendMsg()">Küldés</button>
        </div>
        @else
        <div class="no-chat">Válassz egy üzenetváltást a listából</div>
        @endif
    </div>
</div>

{{-- New conversation modal --}}
<div class="modal-backdrop" id="newConvModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('newConvModal')">&times;</button>
        <div class="modal-title">Új üzenet</div>
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
            <div id="convGroupField" style="display:none;margin-bottom:12px">
                <label class="form-label">Alosztály / Csoport</label>
                <select name="department_id" class="form-select" id="convDeptSelect" onchange="onDeptChange()">
                    <option value="">— Válassz alosztályt —</option>
                    @foreach($departments as $dept)
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
function openModal(id)  { document.getElementById(id).classList.add('open') }
function closeModal(id) { document.getElementById(id).classList.remove('open') }

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

@if(isset($conversation))
const convId  = {{ $conversation->id }};
const authId  = {{ auth()->id() }};
const isAdmin = {{ auth()->user()->is_admin ? 'true' : 'false' }};
let lastId    = {{ $messages->last()?->id ?? 0 }};

// Scroll to bottom
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
    div.className = `msg-row ${isOwn ? 'own' : ''}`;
    const avatarHtml = msg.author.avatar
        ? `<img src="${msg.author.avatar}" class="msg-avatar">`
        : `<div class="msg-avatar-ph">${msg.author.name[0].toUpperCase()}</div>`;
    const delBtn = (isOwn || isAdmin)
        ? `<button class="msg-del" onclick="deleteMsg(${msg.id})">Törlés</button>` : '';
    div.innerHTML = `${avatarHtml}<div class="msg-bubble"><div class="msg-author">${msg.author.name}</div><div class="msg-content">${msg.content}</div><div style="display:flex;align-items:center;gap:8px"><div class="msg-time">${msg.created_at}</div>${delBtn}</div></div>`;
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

<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';
Session::start();
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$db = Database::getInstance();
$userId = Session::getUserId();
$groups = $db->query('SELECT * FROM chat_groups ORDER BY name')->fetchAll();
$userGroups = $db->query('SELECT group_id FROM chat_group_members WHERE user_id = ?', [$userId])->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Super Chat - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    body { background: #f4f6fa; }
    .superchat-container { display: flex; height: 90vh; max-width: 1200px; margin: 2rem auto; box-shadow: 0 4px 32px rgba(67,176,71,0.08); border-radius: 1.5rem; overflow: hidden; background: #fff; }
    .superchat-sidebar { width: 300px; background: #222; color: #fff; display: flex; flex-direction: column; }
    .superchat-sidebar h3 { padding: 2rem 1.5rem 1rem 1.5rem; margin: 0; font-size: 1.2rem; letter-spacing: 1px; }
    .superchat-group-list { flex: 1; overflow-y: auto; }
    .superchat-group-item { padding: 1rem 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #333; transition: background 0.2s; }
    .superchat-group-item.active, .superchat-group-item:hover { background: #43b047; color: #fff; }
    .superchat-group-item .group-name { font-weight: 500; }
    .superchat-group-item button { background: #fff; color: #43b047; border: none; border-radius: 1rem; padding: 0.2rem 0.8rem; font-size: 0.95em; cursor: pointer; transition: background 0.2s, color 0.2s; }
    .superchat-group-item.active button, .superchat-group-item:hover button { background: #eafbe7; color: #222; }
    .superchat-main { flex: 1; display: flex; flex-direction: column; }
    .superchat-header { padding: 1.5rem 2rem 1rem 2rem; border-bottom: 1px solid #eee; font-size: 1.2rem; font-weight: 600; color: #222; }
    .superchat-messages { flex: 1; overflow-y: auto; padding: 2rem; display: flex; flex-direction: column; gap: 1.2rem; background: #f8f8f8; }
    .superchat-message { display: flex; align-items: flex-end; gap: 1rem; }
    .superchat-message.me { flex-direction: row-reverse; }
    .superchat-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #43b047; background: #fff; }
    .superchat-bubble { background: #fff; border-radius: 1.2rem; padding: 0.8rem 1.2rem; box-shadow: 0 2px 8px rgba(67,176,71,0.08); font-size: 1.05em; max-width: 60vw; word-break: break-word; }
    .superchat-message.me .superchat-bubble { background: #43b047; color: #fff; }
    .superchat-meta { font-size: 0.85em; color: #888; margin-top: 0.2rem; }
    .superchat-typing { font-style: italic; color: #43b047; margin: 0.5rem 2rem; }
    .superchat-input-row { display: flex; gap: 1rem; padding: 1.5rem 2rem; border-top: 1px solid #eee; background: #fff; }
    .superchat-input { flex: 1; padding: 1rem; border-radius: 1rem; border: 1px solid #ccc; font-size: 1.05em; }
    .superchat-send-btn { background: #43b047; color: #fff; border: none; border-radius: 1rem; padding: 0.7rem 2rem; font-size: 1.1em; cursor: pointer; transition: background 0.2s; }
    .superchat-send-btn:hover { background: #36913a; }
    @media (max-width: 900px) { .superchat-container { flex-direction: column; height: auto; } .superchat-sidebar { width: 100%; flex-direction: row; height: auto; } .superchat-main { margin-left: 0; } }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="superchat-container">
    <div class="superchat-sidebar">
        <h3>Groupes
            <button id="create-group-btn" style="float:right;background:#43b047;color:#fff;border:none;border-radius:1rem;padding:0.3rem 1rem;font-size:1em;cursor:pointer;">+</button>
        </h3>
        <div class="superchat-group-list">
        <?php foreach ($groups as $g): ?>
            <div class="superchat-group-item" data-group-id="<?= $g['id'] ?>">
                <span class="group-name"><?= htmlspecialchars($g['name']) ?></span>
                <?php if (in_array($g['id'], $userGroups)): ?>
                    <button class="leave-group-btn" data-group-id="<?= $g['id'] ?>">Quitter</button>
                <?php else: ?>
                    <button class="join-group-btn" data-group-id="<?= $g['id'] ?>">Rejoindre</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <div class="superchat-main">
        <div class="superchat-header" id="chat-group-title"><em>Sélectionnez un groupe pour discuter</em></div>
        <div class="superchat-messages" id="chat-messages"></div>
        <div class="superchat-typing" id="typing-indicator"></div>
        <form id="chat-form" style="display:none;">
            <div class="superchat-input-row">
                <input type="text" id="chat-input" class="superchat-input" placeholder="Votre message..." autocomplete="off">
                <button type="submit" class="superchat-send-btn">Envoyer</button>
            </div>
        </form>
    </div>
</div>
<div id="create-group-modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);z-index:999;align-items:center;justify-content:center;">
    <form id="create-group-form" style="background:#fff;padding:2rem 2.5rem;border-radius:1rem;box-shadow:0 2px 16px rgba(0,0,0,0.12);display:flex;flex-direction:column;gap:1rem;min-width:300px;">
        <h2 style="margin:0 0 1rem 0;">Créer un groupe</h2>
        <input type="text" id="new-group-name" placeholder="Nom du groupe" required style="padding:0.7rem;border-radius:0.5rem;border:1px solid #ccc;font-size:1.1em;">
        <div id="create-group-error" style="color:#c00;font-size:0.95em;"></div>
        <div style="display:flex;gap:1rem;justify-content:flex-end;">
            <button type="button" id="cancel-create-group" style="background:#eee;color:#222;border:none;border-radius:0.5rem;padding:0.5rem 1.2rem;">Annuler</button>
            <button type="submit" style="background:#43b047;color:#fff;border:none;border-radius:0.5rem;padding:0.5rem 1.2rem;">Créer</button>
        </div>
    </form>
</div>
<script>
let currentGroup = null;
const userId = <?= json_encode($userId) ?>;
let socket = null;
let typingTimeout = null;
let joinedGroup = null;
let reconnectTimeout = null;

function getAvatar(username) {
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(username)}&background=43b047&color=fff&rounded=true`;
}

function connectWebSocket() {
    if (socket && (socket.readyState === 1 || socket.readyState === 0)) return; // Already connected or connecting
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const host = window.location.hostname;
    const port = '8080';
    socket = new WebSocket(`${protocol}//${host}:${port}`);
    socket.onopen = () => {
        socket.send(JSON.stringify({ type: 'auth', userId: userId }));
        if (currentGroup) {
            socket.send(JSON.stringify({ type: 'join_group', groupId: currentGroup }));
            joinedGroup = currentGroup;
        }
    };
    socket.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (data.type === 'message' && data.groupId == currentGroup) {
            appendMessage({
                user_id: data.userId,
                username: data.userId == userId ? 'Vous' : 'Utilisateur ' + data.userId,
                message: data.message,
                created_at: data.timestamp
            });
        } else if (data.type === 'typing' && data.groupId == currentGroup) {
            showTyping(data.userId, data.isTyping);
        }
    };
    socket.onclose = () => {
        if (reconnectTimeout) clearTimeout(reconnectTimeout);
        reconnectTimeout = setTimeout(connectWebSocket, 2000);
    };
    socket.onerror = (e) => {
        console.error('WebSocket error:', e);
        socket.close();
    };
}
connectWebSocket();

function joinGroupWS(groupId) {
    if (socket && socket.readyState === 1) {
        if (joinedGroup && joinedGroup !== groupId) {
            socket.send(JSON.stringify({ type: 'leave_group' }));
        }
        socket.send(JSON.stringify({ type: 'join_group', groupId: groupId }));
        joinedGroup = groupId;
    }
}
function leaveGroupWS() {
    if (socket && socket.readyState === 1 && joinedGroup) {
        socket.send(JSON.stringify({ type: 'leave_group' }));
        joinedGroup = null;
    }
}
window.addEventListener('beforeunload', () => {
    leaveGroupWS();
    if (socket) socket.close();
});

function appendMessage(msg) {
    const box = document.getElementById('chat-messages');
    const div = document.createElement('div');
    div.className = 'superchat-message' + (msg.user_id == userId ? ' me' : '');
    div.innerHTML = `
        <img src="${getAvatar(msg.username)}" class="superchat-avatar" alt="avatar">
        <div>
            <div class="superchat-bubble">${msg.message}</div>
            <div class="superchat-meta">${msg.username} • ${msg.created_at}</div>
        </div>
    `;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}
function showTyping(uid, isTyping) {
    const el = document.getElementById('typing-indicator');
    el.textContent = isTyping ? 'Quelqu\'un est en train d\'écrire...' : '';
    if (!isTyping) setTimeout(() => { el.textContent = ''; }, 1000);
}
function loadMessages(groupId) {
    fetch('api/group-messages.php?group_id=' + groupId)
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById('chat-messages');
            box.innerHTML = '';
            data.messages.forEach(msg => appendMessage(msg));
        });
}
document.querySelectorAll('.superchat-group-item').forEach(item => {
    item.onclick = function() {
        const groupId = this.dataset.groupId;
        if (currentGroup && currentGroup !== groupId) leaveGroupWS();
        currentGroup = groupId;
        document.getElementById('chat-group-title').textContent = this.querySelector('.group-name').textContent;
        document.getElementById('chat-form').style.display = 'block';
        loadMessages(groupId);
        joinGroupWS(groupId);
    };
});
document.querySelectorAll('.join-group-btn').forEach(btn => {
    btn.onclick = function(e) {
        e.stopPropagation();
        fetch('api/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'join', group_id: this.dataset.groupId })
        }).then(r => r.json()).then(data => { if(data.success) location.reload(); });
    };
});
document.querySelectorAll('.leave-group-btn').forEach(btn => {
    btn.onclick = function(e) {
        e.stopPropagation();
        fetch('api/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'leave', group_id: this.dataset.groupId })
        }).then(r => r.json()).then(data => { if(data.success) location.reload(); });
    };
});
document.getElementById('chat-form').onsubmit = function(e) {
    e.preventDefault();
    const input = document.getElementById('chat-input');
    const msg = input.value.trim();
    if (!msg || !currentGroup) return;
    if (socket && socket.readyState === 1) {
        socket.send(JSON.stringify({ type: 'message', groupId: currentGroup, message: msg }));
        input.value = '';
        sendTyping(false);
    }
};
document.getElementById('chat-input').addEventListener('input', function() {
    sendTyping(true);
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => sendTyping(false), 2000);
});
function sendTyping(isTyping) {
    if (socket && socket.readyState === 1 && currentGroup) {
        socket.send(JSON.stringify({ type: 'typing', groupId: currentGroup, isTyping: isTyping }));
    }
}
document.getElementById('create-group-btn').onclick = function() {
    document.getElementById('create-group-modal').style.display = 'flex';
};
document.getElementById('cancel-create-group').onclick = function() {
    document.getElementById('create-group-modal').style.display = 'none';
};
document.getElementById('create-group-form').onsubmit = function(e) {
    e.preventDefault();
    const name = document.getElementById('new-group-name').value.trim();
    if (!name) {
        document.getElementById('create-group-error').textContent = 'Le nom du groupe est requis.';
        return;
    }
    fetch('api/create-group.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            document.getElementById('create-group-error').textContent = data.error || 'Erreur lors de la création.';
        }
    });
};
</script>
</body>
</html> 
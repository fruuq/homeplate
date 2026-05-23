<?php
$pageTitle = 'Messages';
include __DIR__ . '/includes/header.php';
if (!$currentUser) { header('Location: /login.php?next=/messages.php'); exit; }
$withId = (int)($_GET['with'] ?? 0);
?>

<div class="container py-4">
  <h2 class="fw-bold mb-4"><i class="fas fa-comment-dots me-2 text-orange"></i>Messages</h2>

  <div class="row g-3" style="height:calc(100vh - 240px);min-height:500px">
    <!-- Conversations list -->
    <div class="col-md-4">
      <div class="card-hp h-100 p-0 overflow-hidden">
        <div class="p-3 border-bottom" style="background:var(--hp-light)">
          <input type="text" class="form-control" placeholder="Search conversations…" id="convo-search">
        </div>
        <div id="convo-list" style="overflow-y:auto;height:calc(100% - 60px)">
          <div class="text-center py-5"><div class="spinner-border spinner-border-sm text-orange"></div></div>
        </div>
      </div>
    </div>

    <!-- Chat thread -->
    <div class="col-md-8">
      <div class="card-hp h-100 p-0 overflow-hidden d-flex flex-column">
        <div id="chat-header" class="p-3 border-bottom" style="background:var(--hp-light)">
          <span class="text-muted">Select a conversation</span>
        </div>
        <div id="chat-box" class="chat-box flex-grow-1">
          <div class="text-center text-muted py-4">Select a conversation to start chatting</div>
        </div>
        <div class="p-3 border-top">
          <form id="send-form" onsubmit="sendMessage(event)" class="d-flex gap-2">
            <input type="text" class="form-control" id="msg-input" placeholder="Type a message…" disabled>
            <button type="submit" class="btn-hp btn px-3" id="send-btn" disabled>
              <i class="fas fa-paper-plane"></i>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
const ME = <?= json_encode($currentUser['id']) ?>;
let activePartnerId = <?= $withId ?: 'null' ?>;
let pollTimer = null;

async function loadConversations() {
  const list = document.getElementById('convo-list');
  try {
    const data = await API.get('/api/messages/conversations');
    if (!data.data.length) {
      list.innerHTML = '<div class="text-center py-5 text-muted px-3"><i class="fas fa-comment-slash fa-2x mb-2 d-block" style="opacity:.2"></i>No conversations yet</div>';
      return;
    }
    list.innerHTML = data.data.map(c => `
      <div class="convo-item d-flex align-items-center gap-3 p-3 border-bottom pointer ${activePartnerId===c.partner_id?'bg-light':''}"
           onclick="openChat(${c.partner_id},'${c.partner_name}')" style="cursor:pointer;transition:background .15s"
           onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='${activePartnerId===c.partner_id?'#f9f9f9':'#fff'}'">
        <div style="width:42px;height:42px;background:var(--hp-orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0">
          ${c.partner_name.charAt(0).toUpperCase()}
        </div>
        <div class="flex-grow-1 overflow-hidden">
          <div class="d-flex justify-content-between">
            <strong style="font-size:.9rem">${c.partner_name}</strong>
            ${c.unread_count > 0 ? `<span class="badge bg-orange rounded-pill" style="font-size:.7rem">${c.unread_count}</span>` : ''}
          </div>
          <div class="text-muted text-truncate" style="font-size:.8rem">${c.last_message || ''}</div>
        </div>
      </div>
    `).join('');
  } catch {}
}

async function openChat(partnerId, partnerName) {
  activePartnerId = partnerId;
  document.getElementById('chat-header').innerHTML = `
    <div class="d-flex align-items-center gap-2">
      <div style="width:36px;height:36px;background:var(--hp-orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">
        ${partnerName.charAt(0).toUpperCase()}
      </div>
      <strong>${partnerName}</strong>
    </div>`;

  document.getElementById('msg-input').disabled  = false;
  document.getElementById('send-btn').disabled   = false;
  document.getElementById('msg-input').focus();

  await loadThread();
  clearInterval(pollTimer);
  pollTimer = setInterval(loadThread, 5000); // Poll every 5s
  await loadConversations(); // Refresh unread counts
}

async function loadThread() {
  if (!activePartnerId) return;
  try {
    const data = await API.get('/api/messages?with=' + activePartnerId);
    const box  = document.getElementById('chat-box');
    const wasAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;

    box.innerHTML = data.data.map(m => `
      <div class="bubble ${m.sender_id == ME ? 'me' : 'them'}">
        ${m.body.replace(/</g,'&lt;')}
        <time>${new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</time>
      </div>
    `).join('') || '<div class="text-center text-muted py-4">Say hi! 👋</div>';

    if (wasAtBottom || true) box.scrollTop = box.scrollHeight;
  } catch {}
}

async function sendMessage(e) {
  e.preventDefault();
  const inp  = document.getElementById('msg-input');
  const body = inp.value.trim();
  if (!body || !activePartnerId) return;
  inp.value = '';
  try {
    await API.post('/api/messages', { receiver_id: activePartnerId, body });
    await loadThread();
  } catch (ex) { Toast.error(ex.message); inp.value = body; }
}

// Init
loadConversations();
if (activePartnerId) {
  // Open the chat passed via ?with= param
  fetch('/api/profile?id=' + activePartnerId)
    .then(r => r.json())
    .then(u => openChat(activePartnerId, u.name))
    .catch(() => openChat(activePartnerId, 'User'));
}
JS;
include __DIR__ . '/includes/footer.php';
?>

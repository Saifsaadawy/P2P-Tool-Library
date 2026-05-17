<?php
require_once '../config.php';
require_once '../includes/auth_check.php';
requireLogin();

$pdo           = $GLOBALS['pdo'];
$user          = currentUser();
$reservationId = (int)($_GET['reservation_id'] ?? 0);

if (!$reservationId) {
    header('Location: ' . BASE_PATH . '/pages/index.php');
    exit;
}

// Verify access
$stmt = $pdo->prepare("
    SELECT r.ReservationID, r.Status, r.StartDate, r.EndDate,
           t.Name AS tool_name,
           r.MemberID AS borrower_id, t.MemberID AS lender_id,
           CONCAT(borrower.Fname,' ',borrower.Lname) AS borrower_name,
           CONCAT(lender.Fname,' ',lender.Lname)     AS lender_name
    FROM Reservation r
    JOIN Tool   t        ON t.ToolID      = r.ToolID
    JOIN Member borrower ON borrower.MemberID = r.MemberID
    JOIN Member lender   ON lender.MemberID   = t.MemberID
    WHERE r.ReservationID = ?
");
$stmt->execute([$reservationId]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header('Location: ' . BASE_PATH . '/pages/index.php');
    exit;
}

// Check permission
$isBorrower  = $user['role'] === 'member' && $user['id'] == $reservation['borrower_id'];
$isLender    = $user['role'] === 'member' && $user['id'] == $reservation['lender_id'];
$isLibrarian = $user['role'] === 'librarian';
$canChat     = $isBorrower || $isLender; // librarian = read only

if (!$canChat && !$isLibrarian) {
    header('Location: ' . BASE_PATH . '/pages/index.php');
    exit;
}

require_once '../includes/header.php';
?>

<style>
.chat-wrapper {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem 3rem;
}

.chat-header {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    padding: 1.2rem 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.chat-header h2 { font-size: 1.05rem; font-weight: 600; color: #333; margin: 0; }
.chat-header p  { font-size: 0.85rem; color: #888; margin: 0.2rem 0 0; }

.chat-participants {
    display: flex;
    gap: 1rem;
    font-size: 0.82rem;
    color: #555;
}
.participant { display: flex; align-items: center; gap: 0.4rem; }

.chat-box {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    display: flex;
    flex-direction: column;
    height: 500px;
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    scroll-behavior: smooth;
}

/* Message bubbles */
.msg {
    display: flex;
    flex-direction: column;
    max-width: 72%;
}
.msg.mine   { align-self: flex-end; align-items: flex-end; }
.msg.theirs { align-self: flex-start; align-items: flex-start; }

.msg-sender {
    font-size: 0.75rem;
    color: #aaa;
    margin-bottom: 0.2rem;
    padding: 0 0.5rem;
}

.msg-bubble {
    padding: 0.65rem 1rem;
    border-radius: 16px;
    font-size: 0.9rem;
    line-height: 1.5;
    word-break: break-word;
}
.msg.mine   .msg-bubble { background: #3b5bdb; color: #fff; border-bottom-right-radius: 4px; }
.msg.theirs .msg-bubble { background: #f1f3f5; color: #333; border-bottom-left-radius: 4px; }

.msg-time {
    font-size: 0.72rem;
    color: #bbb;
    margin-top: 0.2rem;
    padding: 0 0.5rem;
}

/* Librarian view-only notice */
.readonly-notice {
    background: #fff3cd;
    border-top: 1px solid #ffd43b;
    padding: 0.8rem 1.5rem;
    font-size: 0.85rem;
    color: #856404;
    border-radius: 0 0 12px 12px;
    text-align: center;
}

/* Input area */
.chat-input-area {
    border-top: 1px solid #e0e0e0;
    padding: 1rem 1.2rem;
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
    border-radius: 0 0 12px 12px;
    background: #fafafa;
}

#msg-input {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 0.6rem 1rem;
    font-size: 0.9rem;
    font-family: inherit;
    resize: none;
    max-height: 120px;
    outline: none;
    transition: border-color 0.15s;
    background: #fff;
}
#msg-input:focus { border-color: #3b5bdb; }

.btn-send {
    background: #3b5bdb;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
    cursor: pointer;
    font-family: inherit;
    font-weight: 600;
    transition: opacity 0.15s;
    white-space: nowrap;
}
.btn-send:hover   { opacity: 0.88; }
.btn-send:disabled { opacity: 0.5; cursor: not-allowed; }

.empty-chat {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #bbb;
    font-size: 0.9rem;
    flex-direction: column;
    gap: 0.5rem;
}
</style>

<div class="chat-wrapper">

    <!-- Header -->
    <div class="chat-header">
        <div>
            <h2>💬 Chat — <?= htmlspecialchars($reservation['tool_name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p>Reservation #<?= $reservationId ?> &nbsp;·&nbsp;
               <?= $reservation['StartDate'] ?> → <?= $reservation['EndDate'] ?> &nbsp;·&nbsp;
               <span class="badge badge-<?= $reservation['Status'] ?>"><?= ucfirst($reservation['Status']) ?></span>
            </p>
        </div>
        <div class="chat-participants">
            <div class="participant">🛒 <strong><?= htmlspecialchars($reservation['borrower_name'], ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div style="color:#ccc">↔</div>
            <div class="participant">🔧 <strong><?= htmlspecialchars($reservation['lender_name'], ENT_QUOTES, 'UTF-8') ?></strong></div>
        </div>
    </div>

    <?php if ($isLibrarian): ?>
    <div class="alert alert-info" style="margin-bottom:1rem; font-size:0.88rem">
        👁️ You are viewing this conversation as a librarian. You cannot send messages.
    </div>
    <?php endif; ?>

    <!-- Chat Box -->
    <div class="chat-box">
        <div class="messages-area" id="messages-area">
            <div class="empty-chat" id="empty-msg">
                <span style="font-size:2rem">💬</span>
                <span>No messages yet. Start the conversation!</span>
            </div>
        </div>

        <?php if ($canChat): ?>
        <div class="chat-input-area">
            <textarea id="msg-input" rows="1" placeholder="Type a message…"></textarea>
            <button class="btn-send" id="send-btn" onclick="sendMessage()">Send ➤</button>
        </div>
        <?php else: ?>
        <div class="readonly-notice">
            👁️ Read-only view — Librarian cannot send messages
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="<?= BASE_PATH ?>/assets/js/api.js"></script>
<script>
const RESERVATION_ID = <?= $reservationId ?>;
const MY_ID          = <?= (int)$user['id'] ?>;
const CAN_CHAT       = <?= $canChat ? 'true' : 'false' ?>;
let   lastCount      = 0;

function formatTime(ts) {
    const d = new Date(ts.replace(' ', 'T'));
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) +
           ' · ' + d.toLocaleDateString([], { month: 'short', day: 'numeric' });
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}

async function loadMessages(scroll = false) {
    try {
        const data = await apiFetch(`/api/messages/get.php?reservation_id=${RESERVATION_ID}`);
        const msgs = data.data.messages;

        if (msgs.length === lastCount && !scroll) return; // nothing new
        lastCount = msgs.length;

        const area  = document.getElementById('messages-area');
        const empty = document.getElementById('empty-msg');

        if (!msgs.length) {
            if (empty) empty.style.display = 'flex';
            return;
        }
        if (empty) empty.style.display = 'none';

        area.innerHTML = msgs.map(m => {
            const mine = m.SenderID == MY_ID;
            return `
                <div class="msg ${mine ? 'mine' : 'theirs'}">
                    <div class="msg-sender">${escapeHtml(m.sender_name)}</div>
                    <div class="msg-bubble">${escapeHtml(m.Body)}</div>
                    <div class="msg-time">${formatTime(m.CreatedAt)}</div>
                </div>`;
        }).join('');

        // Scroll to bottom
        area.scrollTop = area.scrollHeight;

    } catch (e) {
        console.error('Failed to load messages:', e);
    }
}

async function sendMessage() {
    if (!CAN_CHAT) return;
    const input = document.getElementById('msg-input');
    const btn   = document.getElementById('send-btn');
    const body  = input.value.trim();
    if (!body) return;

    btn.disabled  = true;
    input.disabled = true;

    try {
        await apiFetch('/api/messages/send.php', 'POST', {
            reservation_id: RESERVATION_ID,
            body
        });
        input.value = '';
        input.style.height = 'auto';
        await loadMessages(true);
    } catch (e) {
        alert(e.message || 'Failed to send message.');
    } finally {
        btn.disabled   = false;
        input.disabled = false;
        input.focus();
    }
}

// Auto-resize textarea
document.getElementById('msg-input')?.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Send on Enter (Shift+Enter = new line)
document.getElementById('msg-input')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Load immediately then poll every 4 seconds
loadMessages(true);
setInterval(loadMessages, 4000);
</script>

<?php require_once '../includes/footer.php'; ?>
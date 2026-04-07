<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'buyer';

// ── Fetch all conversations this user is part of ──────────────
// Each conversation = one order. We show the other party + last message.
if ($role === 'seller') {
    // Seller: conversations are orders on their products
    $conv_stmt = $conn->prepare("
        SELECT
            o.id            AS order_id,
            o.customer_id   AS other_id,
            u.username      AS other_name,
            p.name          AS product_name,
            o.status        AS order_status,
            (
                SELECT m2.message FROM messages m2
                WHERE m2.order_id = o.id
                ORDER BY m2.created_at DESC LIMIT 1
            ) AS last_message,
            (
                SELECT m3.created_at FROM messages m3
                WHERE m3.order_id = o.id
                ORDER BY m3.created_at DESC LIMIT 1
            ) AS last_at,
            (
                SELECT COUNT(*) FROM messages m4
                WHERE m4.order_id = o.id
                  AND m4.receiver_id = ? AND m4.is_read = 0
            ) AS unread
        FROM orders o
        JOIN products p  ON o.product_id  = p.id
        JOIN users u     ON o.customer_id = u.id
        WHERE p.user_id = ?
        ORDER BY last_at DESC
    ");
    $conv_stmt->bind_param("ii", $user_id, $user_id);
} else {
    // Buyer: conversations are orders they placed
    $conv_stmt = $conn->prepare("
        SELECT
            o.id            AS order_id,
            p.user_id       AS other_id,
            u.username      AS other_name,
            p.name          AS product_name,
            o.status        AS order_status,
            (
                SELECT m2.message FROM messages m2
                WHERE m2.order_id = o.id
                ORDER BY m2.created_at DESC LIMIT 1
            ) AS last_message,
            (
                SELECT m3.created_at FROM messages m3
                WHERE m3.order_id = o.id
                ORDER BY m3.created_at DESC LIMIT 1
            ) AS last_at,
            (
                SELECT COUNT(*) FROM messages m4
                WHERE m4.order_id = o.id
                  AND m4.receiver_id = ? AND m4.is_read = 0
            ) AS unread
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN users u    ON p.user_id    = u.id
        WHERE o.customer_id = ?
        ORDER BY last_at DESC
    ");
    $conv_stmt->bind_param("ii", $user_id, $user_id);
}

$conv_stmt->execute();
$conversations = $conv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Active conversation from URL
$active_order_id  = (int)($_GET['order_id'] ?? ($conversations[0]['order_id'] ?? 0));
$active_conv      = null;
$active_messages  = [];

if ($active_order_id) {
    foreach ($conversations as $c) {
        if ((int)$c['order_id'] === $active_order_id) {
            $active_conv = $c;
            break;
        }
    }

    if ($active_conv) {
        // Mark as read on page load
        $mark = $conn->prepare("UPDATE messages SET is_read=1 WHERE order_id=? AND receiver_id=? AND is_read=0");
        $mark->bind_param("ii", $active_order_id, $user_id);
        $mark->execute();

        // Load messages
        $msg_stmt = $conn->prepare("
            SELECT m.id, m.sender_id, m.message, m.created_at, u.username AS sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.order_id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $msg_stmt->bind_param("iii", $active_order_id, $user_id, $user_id);
        $msg_stmt->execute();
        $active_messages = $msg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$username = htmlspecialchars($_SESSION['username']);
$initials = strtoupper(substr($username, 0, 1));
$last_msg_id = !empty($active_messages) ? end($active_messages)['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | NearBuy</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { box-sizing: border-box; }

        /* ── Layout ─────────────────────────────────────── */
        .messages-shell {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 0;
            height: calc(100vh - 60px);  /* 60px = top-navbar height */
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 18px;
            background: #111;
        }

        /* ── Conversation list (left panel) ─────────────── */
        .conv-panel {
            border-right: 1px solid rgba(255,255,255,0.07);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .conv-header {
            padding: 20px 20px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }
        .conv-header h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 20px;
            color: #fff;
            margin-bottom: 2px;
        }
        .conv-header p { font-size: 12px; color: rgba(255,255,255,0.3); }

        .conv-list { overflow-y: auto; flex: 1; padding: 8px 0; }
        .conv-list::-webkit-scrollbar { width: 4px; }
        .conv-list::-webkit-scrollbar-track { background: transparent; }
        .conv-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 4px; }

        .conv-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            cursor: pointer;
            border-radius: 0;
            transition: background 0.15s;
            text-decoration: none;
            border-left: 2px solid transparent;
        }
        .conv-item:hover { background: rgba(255,255,255,0.04); }
        .conv-item.active {
            background: rgba(255,184,30,0.06);
            border-left-color: #ffb81e;
        }
        .conv-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: rgba(255,184,30,0.1);
            border: 1px solid rgba(255,184,30,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 500; color: #ffb81e;
            flex-shrink: 0;
            position: relative;
        }
        .conv-avatar .unread-dot {
            position: absolute;
            top: -2px; right: -2px;
            width: 10px; height: 10px;
            background: #ffb81e;
            border-radius: 50%;
            border: 2px solid #111;
        }
        .conv-body { flex: 1; min-width: 0; }
        .conv-name {
            font-size: 14px; font-weight: 500; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            margin-bottom: 2px;
        }
        .conv-preview {
            font-size: 12px; color: rgba(255,255,255,0.3);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .conv-preview.unread { color: rgba(255,184,30,0.7); font-weight: 500; }
        .conv-time { font-size: 11px; color: rgba(255,255,255,0.2); flex-shrink: 0; }

        .empty-convs {
            padding: 40px 20px;
            text-align: center;
            font-size: 13px;
            color: rgba(255,255,255,0.2);
            line-height: 1.6;
        }

        /* ── Chat pane (right panel) ─────────────────────── */
        .chat-pane {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-topbar {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 22px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }
        .chat-topbar-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: rgba(255,184,30,0.1);
            border: 1px solid rgba(255,184,30,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 500; color: #ffb81e;
        }
        .chat-topbar-info { flex: 1; }
        .chat-topbar-name { font-size: 15px; font-weight: 500; color: #fff; }
        .chat-topbar-sub  { font-size: 12px; color: rgba(255,255,255,0.3); margin-top: 1px; }
        .order-status-badge {
            font-size: 10px; font-weight: 500;
            padding: 3px 10px; border-radius: 999px;
        }
        .status-pending   { background: rgba(255,184,30,0.1);  color: #ffb81e; border: 1px solid rgba(255,184,30,0.25); }
        .status-completed { background: rgba(52,211,153,0.1);  color: #34d399; border: 1px solid rgba(52,211,153,0.25); }
        .status-cancelled { background: rgba(248,113,113,0.1); color: #f87171; border: 1px solid rgba(248,113,113,0.25); }

        /* Messages scroll area */
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px 22px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .messages-area::-webkit-scrollbar { width: 4px; }
        .messages-area::-webkit-scrollbar-track { background: transparent; }
        .messages-area::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 4px; }

        /* Bubble */
        .msg-row { display: flex; align-items: flex-end; gap: 8px; }
        .msg-row.mine { flex-direction: row-reverse; }

        .msg-bubble {
            max-width: 68%;
            padding: 10px 14px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.5;
            word-break: break-word;
        }
        .msg-row.theirs .msg-bubble {
            background: #1a1a1a;
            border: 1px solid rgba(255,255,255,0.07);
            color: rgba(255,255,255,0.85);
            border-bottom-left-radius: 4px;
        }
        .msg-row.mine .msg-bubble {
            background: rgba(255,184,30,0.12);
            border: 1px solid rgba(255,184,30,0.2);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .msg-meta {
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            margin-top: 4px;
            text-align: right;
        }
        .msg-row.theirs .msg-meta { text-align: left; }
        .msg-sender-mini {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 500; color: rgba(255,255,255,0.4);
            flex-shrink: 0;
            margin-bottom: 20px; /* aligns with meta text height */
        }

        /* Empty state */
        .chat-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.2);
            font-size: 14px;
            gap: 10px;
            text-align: center;
            padding: 40px;
        }
        .chat-empty svg { width: 48px; height: 48px; fill: rgba(255,255,255,0.07); }

        /* Message input */
        .chat-input-bar {
            padding: 14px 18px;
            border-top: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        #msg-input {
            flex: 1;
            background: #1a1a1a;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 999px;
            padding: 10px 18px;
            color: #fff;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            transition: border-color 0.15s;
            resize: none;
            height: 42px;
            line-height: 1.5;
        }
        #msg-input:focus { border-color: rgba(255,184,30,0.35); }
        #send-btn {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffb81e, #ff6b35);
            border: none;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: opacity 0.15s, transform 0.1s;
        }
        #send-btn:hover { opacity: 0.88; }
        #send-btn:active { transform: scale(0.93); }
        #send-btn svg { width: 18px; height: 18px; fill: #0d0d0d; }
        #send-btn:disabled { opacity: 0.35; cursor: not-allowed; }

        .date-divider {
            text-align: center;
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            margin: 4px 0;
            position: relative;
        }
        .date-divider::before, .date-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: rgba(255,255,255,0.05);
        }
        .date-divider::before { left: 0; }
        .date-divider::after  { right: 0; }

        /* No conversation selected */
        .no-conv {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.2);
            font-size: 14px;
            gap: 12px;
            text-align: center;
            padding: 40px;
        }
        .no-conv svg { width: 56px; height: 56px; fill: rgba(255,255,255,0.06); }
        .no-conv p { line-height: 1.6; }

        @media (max-width: 720px) {
            .messages-shell { grid-template-columns: 1fr; }
            .conv-panel { display: <?php echo $active_conv ? 'none' : 'flex'; ?>; }
        }
    </style>
</head>
<body>

<nav class="top-navbar">
    <a class="nav-brand" href="dashboard.php">
        <div class="brand-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        </div>
        <span class="brand-name">NearBuy</span>
    </a>
    <div class="nav-right">
        <div class="user-pill">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <span class="username-display"><?php echo $username; ?></span>
        </div>
    </div>
</nav>

<div class="page-body">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 16px; display:flex; flex-direction:column; overflow:hidden;">
        <div class="messages-shell">

            <!-- ── Left: conversation list ── -->
            <div class="conv-panel">
                <div class="conv-header">
                    <h2>Messages</h2>
                    <p><?php echo count($conversations); ?> conversation<?php echo count($conversations) !== 1 ? 's' : ''; ?></p>
                </div>
                <div class="conv-list">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-convs">
                            <svg viewBox="0 0 24 24" style="width:36px;height:36px;fill:rgba(255,255,255,0.1);margin-bottom:10px;display:block;margin-left:auto;margin-right:auto;"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                            No conversations yet.<br>
                            <?php if ($role === 'buyer'): ?>
                                Place an order to start chatting with a seller.
                            <?php else: ?>
                                Buyers will message you once they place orders.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $c):
                            $isActive  = (int)$c['order_id'] === $active_order_id;
                            $otherInit = strtoupper(substr($c['other_name'], 0, 1));
                            $hasUnread = (int)$c['unread'] > 0 && !$isActive;
                            $preview   = $c['last_message'] ?? 'No messages yet — say hello!';
                            $timeStr   = '';
                            if ($c['last_at']) {
                                $ts = strtotime($c['last_at']);
                                $timeStr = date('n/j', $ts) === date('n/j') ? date('g:i A', $ts) : date('M j', $ts);
                            }
                        ?>
                        <a href="?order_id=<?php echo $c['order_id']; ?>"
                           class="conv-item <?php echo $isActive ? 'active' : ''; ?>">
                            <div class="conv-avatar">
                                <?php echo $otherInit; ?>
                                <?php if ($hasUnread): ?><span class="unread-dot"></span><?php endif; ?>
                            </div>
                            <div class="conv-body">
                                <div class="conv-name"><?php echo htmlspecialchars($c['other_name']); ?></div>
                                <div class="conv-preview <?php echo $hasUnread ? 'unread' : ''; ?>">
                                    <?php echo htmlspecialchars(mb_strimwidth($preview, 0, 45, '…')); ?>
                                </div>
                            </div>
                            <?php if ($timeStr): ?>
                                <span class="conv-time"><?php echo $timeStr; ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Right: chat pane ── -->
            <div class="chat-pane">
                <?php if ($active_conv): ?>

                    <!-- Top bar -->
                    <div class="chat-topbar">
                        <div class="chat-topbar-avatar">
                            <?php echo strtoupper(substr($active_conv['other_name'], 0, 1)); ?>
                        </div>
                        <div class="chat-topbar-info">
                            <div class="chat-topbar-name"><?php echo htmlspecialchars($active_conv['other_name']); ?></div>
                            <div class="chat-topbar-sub">
                                Re: <?php echo htmlspecialchars($active_conv['product_name']); ?>
                            </div>
                        </div>
                        <?php $st = strtolower($active_conv['order_status']); ?>
                        <span class="order-status-badge status-<?php echo $st; ?>">
                            Order <?php echo ucfirst($st); ?>
                        </span>
                    </div>

                    <!-- Messages -->
                    <div class="messages-area" id="messages-area">
                        <?php if (empty($active_messages)): ?>
                            <div class="chat-empty">
                                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                                <span>No messages yet.<br>Start the conversation!</span>
                            </div>
                        <?php else: ?>
                            <?php
                            $lastDate = '';
                            foreach ($active_messages as $msg):
                                $isMine  = (int)$msg['sender_id'] === $user_id;
                                $rowCls  = $isMine ? 'mine' : 'theirs';
                                $msgDate = date('M j, Y', strtotime($msg['created_at']));
                                $msgTime = date('g:i A', strtotime($msg['created_at']));
                                $sInit   = strtoupper(substr($msg['sender_name'], 0, 1));
                            ?>
                                <?php if ($msgDate !== $lastDate): ?>
                                    <div class="date-divider"><?php echo $msgDate; ?></div>
                                    <?php $lastDate = $msgDate; ?>
                                <?php endif; ?>
                                <div class="msg-row <?php echo $rowCls; ?>" data-id="<?php echo $msg['id']; ?>">
                                    <?php if (!$isMine): ?>
                                        <div class="msg-sender-mini"><?php echo $sInit; ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="msg-bubble"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                        <div class="msg-meta"><?php echo $msgTime; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Input bar -->
                    <div class="chat-input-bar">
                        <input type="text"
                               id="msg-input"
                               placeholder="Type a message…"
                               autocomplete="off"
                               maxlength="2000">
                        <button id="send-btn" title="Send">
                            <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                        </button>
                    </div>

                    <script>
                    const ORDER_ID     = <?php echo $active_order_id; ?>;
                    const RECEIVER_ID  = <?php echo (int)$active_conv['other_id']; ?>;
                    const CURRENT_USER = <?php echo $user_id; ?>;
                    let   lastMsgId    = <?php echo $last_msg_id; ?>;

                    const area     = document.getElementById('messages-area');
                    const input    = document.getElementById('msg-input');
                    const sendBtn  = document.getElementById('send-btn');

                    // ── Scroll to bottom ──
                    function scrollBottom() {
                        area.scrollTop = area.scrollHeight;
                    }
                    scrollBottom();

                    // ── Append bubble helper ──
                    function appendBubble(msg) {
                        const isMine = parseInt(msg.sender_id) === CURRENT_USER;
                        const row    = document.createElement('div');
                        row.className = 'msg-row ' + (isMine ? 'mine' : 'theirs');
                        row.dataset.id = msg.id;

                        const sInit = (msg.sender_name || '?').charAt(0).toUpperCase();
                        const text  = msg.message.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
                        const d     = new Date(msg.created_at.replace(' ', 'T'));
                        const time  = d.toLocaleTimeString([], {hour:'numeric', minute:'2-digit'});

                        row.innerHTML = (!isMine
                            ? `<div class="msg-sender-mini">${sInit}</div>` : '')
                            + `<div>
                                <div class="msg-bubble">${text}</div>
                                <div class="msg-meta">${time}</div>
                              </div>`;

                        // Remove empty state if present
                        const empty = area.querySelector('.chat-empty');
                        if (empty) empty.remove();

                        area.appendChild(row);
                        lastMsgId = Math.max(lastMsgId, parseInt(msg.id));
                    }

                    // ── Send ──
                    async function sendMessage() {
                        const text = input.value.trim();
                        if (!text) return;

                        sendBtn.disabled = true;
                        input.value = '';

                        const body = new URLSearchParams({
                            action:      'send',
                            order_id:    ORDER_ID,
                            receiver_id: RECEIVER_ID,
                            message:     text
                        });

                        try {
                            const res  = await fetch('chat_handler.php', { method:'POST', body });
                            const data = await res.json();
                            if (data.success) {
                                // Optimistic: immediately show my message
                                appendBubble({
                                    id:          data.message_id,
                                    sender_id:   CURRENT_USER,
                                    sender_name: '<?php echo addslashes($username); ?>',
                                    message:     text,
                                    created_at:  new Date().toISOString().slice(0,19).replace('T',' ')
                                });
                                scrollBottom();
                            }
                        } catch(e) { console.error(e); }
                        sendBtn.disabled = false;
                        input.focus();
                    }

                    // ── Poll for new messages every 4 s ──
                    async function pollMessages() {
                        try {
                            const res  = await fetch(`chat_handler.php?action=fetch&order_id=${ORDER_ID}&since_id=${lastMsgId}`);
                            const data = await res.json();
                            if (data.success && data.messages.length) {
                                const wasAtBottom = area.scrollHeight - area.clientHeight - area.scrollTop < 60;
                                data.messages.forEach(m => {
                                    if (parseInt(m.sender_id) !== CURRENT_USER) appendBubble(m);
                                });
                                if (wasAtBottom) scrollBottom();
                            }
                        } catch(e) {}
                    }
                    setInterval(pollMessages, 4000);

                    // ── Event listeners ──
                    sendBtn.addEventListener('click', sendMessage);
                    input.addEventListener('keydown', e => {
                        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
                    });
                    </script>

                <?php else: ?>
                    <!-- No conversation selected -->
                    <div class="no-conv">
                        <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                        <p>Select a conversation<br>from the list to start chatting.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

</body>
</html>
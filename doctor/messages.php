<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = (int)($_POST['recipient_id'] ?? 0);
    $child_id = isset($_POST['child_id']) ? (int)$_POST['child_id'] : null;
    $message = trim($_POST['message'] ?? '');

    //The default type if nothing is selected
    $message_type = in_array($_POST['message_type'] ?? 'general', ['general', 'consultation', 'urgent']) ? $_POST['message_type'] : 'general';

    if ($recipient_id > 0 && $message !== '') {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, child_id, message, message_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $_SESSION['user_id'], $recipient_id, $child_id, $message, $message_type);
        $stmt->execute();

        $notification_title = "رسالة جديدة من " . $_SESSION['full_name'];
        $notification_message = "لديك رسالة جديدة من الطبيب";
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')");
        $stmt_notify->bind_param("iss", $recipient_id, $notification_title, $notification_message);
        $stmt_notify->execute();

        $success_message = "تم إرسال الرسالة بنجاح";

        // علامة الرسائل السابقة كمقروءةMark previous messages as read
        $stmt_seen = $conn->prepare("UPDATE messages SET status = 'responded' WHERE sender_id = ? AND recipient_id = ? AND status = 'pending'");
        $stmt_seen->bind_param("ii", $recipient_id, $_SESSION['user_id']);
        $stmt_seen->execute();
    }
}

$query_conversations = "SELECT DISTINCT
    u.id, u.full_name,
    (SELECT message FROM messages
     WHERE (sender_id = ? AND recipient_id = u.id) OR (sender_id = u.id AND recipient_id = ?)
     ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT message_type FROM messages
     WHERE (sender_id = ? AND recipient_id = u.id) OR (sender_id = u.id AND recipient_id = ?)
     ORDER BY created_at DESC LIMIT 1) as last_message_type,
    (SELECT child_id FROM messages
     WHERE (sender_id = ? AND recipient_id = u.id) OR (sender_id = u.id AND recipient_id = ?)
     ORDER BY created_at DESC LIMIT 1) as last_child_id,
    (SELECT created_at FROM messages
     WHERE (sender_id = ? AND recipient_id = u.id) OR (sender_id = u.id AND recipient_id = ?)
     ORDER BY created_at DESC LIMIT 1) as last_message_time,
    (SELECT COUNT(*) FROM messages
     WHERE sender_id = u.id AND recipient_id = ? AND status = 'pending') as unread_count
FROM users u
JOIN messages m ON (m.sender_id = u.id OR m.recipient_id = u.id)
WHERE u.user_type = 'parent' AND (m.sender_id = ? OR m.recipient_id = ?)
GROUP BY u.id, u.full_name
ORDER BY last_message_time DESC";

$stmt_conversations = $conn->prepare($query_conversations);
$stmt_conversations->bind_param("iiiiiiiiiii", 
    $_SESSION['user_id'], $_SESSION['user_id'], // last_message
    $_SESSION['user_id'], $_SESSION['user_id'], // last_message_type
    $_SESSION['user_id'], $_SESSION['user_id'], // last_child_id
    $_SESSION['user_id'], $_SESSION['user_id'], // last_message_time
    $_SESSION['user_id'], // unread count
    $_SESSION['user_id'], $_SESSION['user_id']  // where condition
);
$stmt_conversations->execute();
$conversations_result = $stmt_conversations->get_result();

$selected_parent = null;
$messages_result = null;
$children_for_parent = [];
$selected_child_name = null;
$selected_message_type = null;
if (isset($_GET['parent_id'])) {
    $parent_id = (int)$_GET['parent_id'];

    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ? AND user_type = 'parent'");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $selected_parent = $stmt->get_result()->fetch_assoc();

    if ($selected_parent) {
        // جلب الأطفال التابعة لولي الأمر لعرض الطفل المختار في الدردشة
        $stmt_children = $conn->prepare("SELECT id, name FROM children WHERE user_id = ? ORDER BY name");
        $stmt_children->bind_param("i", $parent_id);
        $stmt_children->execute();
        $children_for_parent = $stmt_children->get_result()->fetch_all(MYSQLI_ASSOC);

        $query_messages = "SELECT m.*, u.full_name as sender_name, c.name as child_name,
                          CASE WHEN m.sender_id = ? THEN 1 ELSE 0 END as is_mine
                          FROM messages m
                          LEFT JOIN users u ON m.sender_id = u.id
                          LEFT JOIN children c ON m.child_id = c.id
                          WHERE (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?)
                          ORDER BY m.created_at ASC";
        $stmt_messages = $conn->prepare($query_messages);
        $stmt_messages->bind_param("iiiii", $_SESSION['user_id'], $_SESSION['user_id'], $parent_id, $parent_id, $_SESSION['user_id']);
        $stmt_messages->execute();
        $messages_result = $stmt_messages->get_result();

        // جلب آخر رسالة لتعيين نوع المحادثة
        $stmt_last = $conn->prepare("SELECT child_id, message_type FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY created_at DESC LIMIT 1");
        $stmt_last->bind_param("iiii", $_SESSION['user_id'], $parent_id, $parent_id, $_SESSION['user_id']);
        $stmt_last->execute();
        $last_info = $stmt_last->get_result()->fetch_assoc();

        $selected_child_name = null;
        $selected_message_type = null;
        if ($last_info) {
            if ($last_info['child_id']) {
                $stmt_child_name = $conn->prepare("SELECT name FROM children WHERE id = ?");
                $stmt_child_name->bind_param("i", $last_info['child_id']);
                $stmt_child_name->execute();
                $child_info = $stmt_child_name->get_result()->fetch_assoc();
                $selected_child_name = $child_info['name'] ?? null;
            }
            $selected_message_type = $last_info['message_type'];
        }

        $stmt_update = $conn->prepare("UPDATE messages SET status = 'responded' WHERE sender_id = ? AND recipient_id = ? AND status = 'pending'");
        $stmt_update->bind_param("ii", $parent_id, $_SESSION['user_id']);
        $stmt_update->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرسائل - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <style>
        .chat-container { height: calc(100vh - 200px); display: flex; }
        .conversations-list { width: 350px; border-left: 1px solid #dee2e6; background: #f8f9fa; overflow-y: auto; }
        .conversation-item { padding: 15px; border-bottom: 1px solid #e9ecef; cursor: pointer; transition: background-color 0.2s; }
        .conversation-item:hover { background: #e9ecef; }
        .conversation-item.active { background: #0d6efd; color: white; }
        .conversation-item .unread-badge { background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; }
        .chat-messages { flex: 1; display: flex; flex-direction: column; background: #ffffff; }
        .messages-list { flex: 1; overflow-y: auto; padding: 20px; }
        .message { margin-bottom: 15px; max-width: 70%; }
        .message.sent { margin-left: auto; text-align: right; }
        .message.received { margin-right: auto; }
        .message-bubble { padding: 10px 15px; border-radius: 18px; word-wrap: break-word; }
        .message.sent .message-bubble { background: #0d6efd; color: white; }
        .message.received .message-bubble { background: #f1f3f4; color: #333; }
        .message-time { font-size: 12px; color: #666; margin-top: 5px; }
        .chat-input { border-top: 1px solid #dee2e6; padding: 15px; background: #f8f9fa; }
        .online-indicator { width: 10px; height: 10px; border-radius: 50%; background: #28a745; display: inline-block; margin-left: 5px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand"><h1>👨‍⚕️ لوحة الطبيب</h1><span class="badge">v1.0</span></div>
            <div class="navbar-user"><div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'D', 0, 1)); ?></div><div><small style="color: #7a6880;">مرحباً د.</small><div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'طبيب'); ?></div></div></div>
        </div>
    </nav>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>الرسائل والمحادثات</h1>
            <span id="doctorNotificationBadge" class="badge bg-danger" style="font-size: 0.95rem; display: none;"></span>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="chat-container">
            <div class="conversations-list">
                <div class="p-3 border-bottom"><h6>قائمة الآباء</h6></div>
                <?php if ($conversations_result->num_rows > 0): ?>
                    <?php while ($conversation = $conversations_result->fetch_assoc()): ?>
                        <div class="conversation-item <?php echo (isset($_GET['parent_id']) && $_GET['parent_id'] == $conversation['id']) ? 'active' : ''; ?>" onclick="openConversation(<?php echo $conversation['id']; ?>)">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center"><strong><?php echo htmlspecialchars($conversation['full_name']); ?></strong><span class="online-indicator" title="متصل"></span></div>
                                    <p class="mb-1 small text-muted"><?php echo htmlspecialchars(substr($conversation['last_message'] ?? 'لا توجد رسائل', 0, 50)); ?></p>
                                    <?php if (!empty($conversation['last_child_id'])): ?>
                                        <?php
                                        $childName = '';
                                        $stmt_child = $conn->prepare("SELECT name FROM children WHERE id = ?");
                                        $stmt_child->bind_param("i", $conversation['last_child_id']);
                                        $stmt_child->execute();
                                        $childRow = $stmt_child->get_result()->fetch_assoc();
                                        $childName = $childRow['name'] ?? '';
                                        ?>
                                        <small class="text-muted">الطفل: <?php echo htmlspecialchars($childName); ?></small><br>
                                    <?php endif; ?>
                                    <?php if (!empty($conversation['last_message_type'])): ?>
                                        <small class="badge bg-secondary text-white"><?php echo ($conversation['last_message_type'] === 'consultation' ? 'استشارة' : ($conversation['last_message_type'] === 'urgent' ? 'عاجل' : 'عام')); ?></small>
                                    <?php endif; ?>
                                    <small class="text-muted"><?php echo $conversation['last_message_time'] ? date('d/m H:i', strtotime($conversation['last_message_time'])) : ''; ?></small>
                                </div>
                                <?php if ($conversation['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center p-4 text-muted"><i class="fas fa-comments fa-2x mb-2"></i><p>لا توجد محادثات</p><p>سيظهر هنا الآباء الذين أرسلوا رسائل</p></div>
                <?php endif; ?>
            </div>

            <div class="chat-messages">
                <?php if ($selected_parent): ?>
                    <div class="border-bottom p-3">
                        <h6><?php echo htmlspecialchars($selected_parent['full_name']); ?></h6>
                        <?php if ($selected_child_name): ?>
                            <small class="text-muted">الطفل: <?php echo htmlspecialchars($selected_child_name); ?></small><br>
                        <?php endif; ?>
                        <?php if ($selected_message_type): ?>
                            <small class="badge bg-info text-dark">
                                <?php echo ($selected_message_type === 'consultation' ? 'استشارة' : ($selected_message_type === 'urgent' ? 'عاجل' : 'عام')); ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="messages-list" id="messagesList">
                        <?php if ($messages_result && $messages_result->num_rows > 0): ?>
                            <?php while ($message = $messages_result->fetch_assoc()): ?>
                                <div class="message <?php echo $message['is_mine'] ? 'sent' : 'received'; ?>">
                                    <div class="message-bubble"><?php echo htmlspecialchars($message['message']); ?></div>
                                    <small class="text-muted">
                                        <?php if ($message['child_name']): ?>
                                            طفل: <?php echo htmlspecialchars($message['child_name']); ?>
                                        <?php endif; ?>
                                        <?php if ($message['message_type']): ?>
                                            | نوع: <?php echo ($message['message_type'] === 'consultation' ? 'استشارة' : ($message['message_type'] === 'urgent' ? 'عاجل' : 'عام')); ?>
                                        <?php endif; ?>
                                    </small>
                                    <div class="message-time"><?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted mt-5"><i class="fas fa-comments fa-3x mb-3"></i><p>لا توجد رسائل في هذه المحادثة</p><p>ابدأ بالرد على الرسالة</p></div>
                        <?php endif; ?>
                    </div>

                    <div class="chat-input">
                        <form method="POST" id="sendMessageForm">
                            <input type="hidden" name="recipient_id" value="<?php echo $parent_id; ?>">
                            <div class="row g-2">
                                <div class="col-4">
                                    <select class="form-select" name="message_type" required>
                                        <option value="general" <?php echo ($selected_message_type === 'general' ? 'selected' : ''); ?>>عام</option>
                                        <option value="consultation" <?php echo ($selected_message_type === 'consultation' ? 'selected' : ''); ?>>استشارة</option>
                                        <option value="urgent" <?php echo ($selected_message_type === 'urgent' ? 'selected' : ''); ?>>عاجل</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <select class="form-select" name="child_id">
                                        <option value="">الطفل (اختياري)</option>
                                        <?php foreach ($children_for_parent as $child): ?>
                                            <option value="<?php echo $child['id']; ?>" <?php echo (isset($selected_child_name) && $selected_child_name === $child['name'] ? 'selected' : ''); ?>><?php echo htmlspecialchars($child['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <div class="input-group">
                                        <input type="text" name="message" class="form-control" placeholder="اكتب ردك هنا..." required>
                                        <button type="submit" name="send_message" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted"><div class="text-center"><i class="fas fa-comments fa-4x mb-3"></i><h5>اختر ولي أمر لعرض المحادثة</h5><p>انقر فوق أحد الآباء من القائمة الجانبية</p></div></div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openConversation(parentId) {
            window.location.href = 'messages.php?parent_id=' + parentId;
        }

        <?php if ($messages_result): ?>
            document.getElementById('messagesList').scrollTop = document.getElementById('messagesList').scrollHeight;
        <?php endif; ?>

        function refreshNotificationBadge() {
            fetch('check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('#doctorMessageCount');
                    if (!badge) return;
                    const total = data.unread_messages || 0;
                    badge.textContent = total > 0 ? total : '';
                    badge.style.display = total > 0 ? 'inline-flex' : 'none';
                });
        }

        refreshNotificationBadge();
        setInterval(refreshNotificationBadge, 15000);

        <?php if (isset($parent_id)): ?>
            setInterval(function() {
                fetch('check_new_messages.php?parent_id=<?php echo $parent_id; ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.new_messages && data.new_messages.length > 0) {
                            const messagesList = document.getElementById('messagesList');
                            data.new_messages.forEach(message => {
                                const messageDiv = document.createElement('div');
                                messageDiv.className = 'message received';
                                messageDiv.innerHTML = `
                                    <div class="message-bubble">${message.message}</div>
                                    <div class="message-time">${message.time}</div>
                                `;
                                messagesList.appendChild(messageDiv);
                            });
                            messagesList.scrollTop = messagesList.scrollHeight;
                            fetch('mark_messages_read.php?parent_id=<?php echo $parent_id; ?>');
                        }
                    })
                    .catch(error => console.error('Error checking doctor messages:', error));
            }, 10000);
        <?php endif; ?>
    </script>
</body>
</html>

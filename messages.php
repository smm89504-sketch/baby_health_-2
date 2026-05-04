<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    header('Location: login.php');
    exit();
}

require_once './includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Processing the sending of a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = $_POST['recipient_id'];
    $child_id = $_POST['child_id'] ?? null;
    $message = trim($_POST['message']);
    $message_type = $_POST['message_type'] ?? 'general';

    if (!empty($message) && !empty($recipient_id)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, child_id, message, message_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $_SESSION['user_id'], $recipient_id, $child_id, $message, $message_type);
        $stmt->execute();

        //Create a notification for the recipient للمستلم
        $notification_title = "رسالة جديدة من " . $_SESSION['full_name'];
        $notification_message = "لديك رسالة جديدة في نظام الدردشة";

        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')");
        $stmt_notify->bind_param("iss", $recipient_id, $notification_title, $notification_message);
        $stmt_notify->execute();

        $success_message = "تم إرسال الرسالة بنجاح";
    }
}

// جلب قائمة المحادثات (الأطباء الذين تم التواصل معهم)
$query_conversations = "SELECT DISTINCT
    u.id, u.full_name,
    (SELECT message FROM messages
     WHERE (sender_id = ? AND recipient_id = u.id) OR (sender_id = u.id AND recipient_id = ?)
     ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM messages
     WHERE (sender_id = ? AND recipient_id = u.id) OR (sender_id = u.id AND recipient_id = ?)
     ORDER BY created_at DESC LIMIT 1) as last_message_time,
    (SELECT COUNT(*) FROM messages
     WHERE sender_id = u.id AND recipient_id = ? AND status = 'pending') as unread_count
FROM users u
JOIN messages m ON (m.sender_id = u.id OR m.recipient_id = u.id)
WHERE u.user_type = 'doctor' AND (m.sender_id = ? OR m.recipient_id = ?)
GROUP BY u.id, u.full_name
ORDER BY last_message_time DESC";

$stmt_conversations = $conn->prepare($query_conversations);
$stmt_conversations->bind_param("iiiiiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt_conversations->execute();
$conversations_result = $stmt_conversations->get_result();

//Bringing children to the parent
$query_children = "SELECT id, name FROM children WHERE user_id = ? ORDER BY name";
$stmt_children = $conn->prepare($query_children);
$stmt_children->bind_param("i", $_SESSION['user_id']);
$stmt_children->execute();
$children_result = $stmt_children->get_result();

// Bring available doctors
$query_doctors = "SELECT id, full_name FROM users WHERE user_type = 'doctor' AND is_active = 1 ORDER BY full_name";
$stmt_doctors = $conn->prepare($query_doctors);
$stmt_doctors->execute();
$doctors_result = $stmt_doctors->get_result();

//Fetch unread messages to display in the sidebar
$query_unread_messages = "SELECT COUNT(*) as unread_count FROM messages WHERE recipient_id = ? AND status = 'pending'";
$stmt_unread = $conn->prepare($query_unread_messages);
$stmt_unread->bind_param("i", $_SESSION['user_id']);
$stmt_unread->execute();
$unread_messages = $stmt_unread->get_result()->fetch_assoc()['unread_count'];

// Setting up sidebar variables for pages under the parent folder
$base_path = './';
$parent_path = '';
$dashboard_link = 'index.php';
$main_dark = '#ad1457';
$main_text = '#880e4f';
$main_light = '#ffd1dc';
$main_deep = '#f8bbd0';
$bg_light = '#fff0f5';
$title_icon = 'fas fa-heartbeat';
$vaccine_alerts = ['upcoming'=>[], 'missed'=>[]];

// جلب المحادثة المحددة إذا تم اختيار طبيب
$selected_doctor = null;
$messages_result = null;
if (isset($_GET['doctor_id'])) {
    $doctor_id = (int)$_GET['doctor_id'];

    // Retrieve doctor's data
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ? AND user_type = 'doctor'");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $selected_doctor = $stmt->get_result()->fetch_assoc();

    if ($selected_doctor) {
        // Retrieve messages
        $query_messages = "SELECT m.*, u.full_name as sender_name,
                          CASE WHEN m.sender_id = ? THEN 1 ELSE 0 END as is_mine
                          FROM messages m
                          LEFT JOIN users u ON m.sender_id = u.id
                          WHERE (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?)
                          ORDER BY m.created_at ASC";
        $stmt_messages = $conn->prepare($query_messages);
        $stmt_messages->bind_param("iiiii", $_SESSION['user_id'], $_SESSION['user_id'], $doctor_id, $doctor_id, $_SESSION['user_id']);
        $stmt_messages->execute();
        $messages_result = $stmt_messages->get_result();

        //Update message status as read
        $stmt_update = $conn->prepare("UPDATE messages SET status = 'responded' WHERE sender_id = ? AND recipient_id = ? AND status = 'pending'");
        $stmt_update->bind_param("ii", $doctor_id, $_SESSION['user_id']);
        $stmt_update->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرسائل و الدردشة - لوحة الوالد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Dynamic Colors from profile.php */
        :root {
            --primary-light: <?= $bg_light ?>;
            --primary-pink: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;
        }

        /* Sidebar Styles */
        body { background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%); min-height: 100vh; color: #4A4A4A; font-family: 'Cairo', sans-serif; display: flex; }
        .sidebar { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-text) 100%); width: 250px; min-height: 100vh; padding: 20px; color: white; box-shadow: 0 8px 24px rgba(136, 14, 79, 0.12); transition: all 0.3s; }
        .sidebar a { color: rgba(255, 255, 255, 0.9); text-decoration: none; transition: all 0.2s; }
        .sidebar a:hover { color: white; transform: translateX(5px); }
        .sidebar .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255, 255, 255, 0.15); }
        .sidebar .logo { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        .sidebar .logo i { color: var(--primary-pink); }
        .logout-btn { background: rgba(255, 255, 255, 0.15); border: none; border-radius: 12px; padding: 12px; color: white; font-weight: 600; transition: all 0.3s; width: 100%; text-align: right; display: flex; align-items: center; gap: 10px; justify-content: flex-start; }
        .logout-btn:hover { background: rgba(255, 255, 255, 0.25); transform: translateY(-3px); }
        .main-container { flex: 1; padding: 20px; overflow-y: auto; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; }
        .alert-missed { background: linear-gradient(to right, #ef9a9a, #e57373); color: white; border: none; }
        .alert-upcoming { background: linear-gradient(to right, #ffd54f, #ffca28); color: #5d4037; border: none; }

        /* Original File Styles */
        .main-box { margin-top: 0; margin-bottom: 25px; box-shadow: 0 6px 24px rgba(100, 100, 100, 0.1); border-radius: 12px; background: #ffffff; padding: 30px; }
        .page-header { font-size: 1.9rem; color: #C7346F; font-weight: 700; }
        .page-header i { margin-left: 12px; font-size: 1.7rem; }
        .btn-success { background-color: #E7AAB4; border-color: #E7AAB4; color: #fff; font-weight: 600; padding: 0.5rem 1rem; }
        .btn-success:hover { background-color: #DB99A6; border-color: #DB99A6; }
        .child-card { border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06); background-color: #FFFAFB; border: 1px solid #FDEEF0; transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; height: 100%; }
        .child-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0, 0, 0, 0.09); }
        .child-card .card-title { color: #B34073; font-weight: 600; font-size: 1.25rem; }
        
        /* تحديث: استخدام الصورة الافتراضية */
        .child-icon-wrapper { 
            background-color: transparent; /* إزالة خلفية الأيقونة القديمة */
            width: 80px; 
            height: 80px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-left: auto; 
            margin-right: auto; 
            margin-bottom: 15px;
            overflow: hidden; /* لضمان ظهور الصورة بشكل دائري */
        }
        .child-card-image {
            width: 100%; /* ملء الحاوية */
            height: 100%;
            object-fit: cover;
        }
        /* نهاية تحديث الصورة */
        
        .info-line i { color: #E7AAB4; margin-left: 8px; font-size: 1rem; }
        .btn i { margin-left: 4px; }
        .text-muted { color: #888 !important; font-size: 1.1rem; }
        .flex-grow-1 { padding: 0; }

        /* Chat Modern UI (مثل صفحة الأطفال) */

.chat-container {
    height: calc(100vh - 220px);
    display: flex;
    gap: 20px;
}

/* conversations */
.conversations-list {
    width: 320px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    overflow-y: auto;
}

.conversation-item {
    padding: 15px;
    border-bottom: 1px solid #f1f1f1;
    cursor: pointer;
    transition: all 0.2s;
    border-radius: 10px;
}

.conversation-item:hover {
    background: #fff0f5;
}

.conversation-item.active {
    background: var(--primary-light);
}

.conversation-item strong {
    color: var(--primary-text);
}

.conversation-item small {
    font-size: 12px;
}
.unread-badge {
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 4px 8px;
    font-size: 11px;
}

/* chat box */
.chat-messages {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* header */
.chat-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    font-weight: 600;
    color: var(--primary-text);
}

/* messages */
.messages-list {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #fff7fa;
}

.message {
    margin-bottom: 15px;
    max-width: 70%;
}

.message.sent {
    margin-left: auto;
}

.message.received {
    margin-right: auto;
}

.message-bubble {
    padding: 10px 15px;
    border-radius: 20px;
    font-size: 14px;
}

/* 👇 أهم فرق */
.message.sent .message-bubble {
    background: linear-gradient(135deg, var(--primary-text), var(--primary-dark));
    color: white;
}

.message.received .message-bubble {
    background: #ffffff;
    border: 1px solid #f1d6de;
}

.message-time {
    font-size: 11px;
    margin-top: 5px;
    color: #999;
}

/* input */
.chat-input {
    border-top: 1px solid #eee;
    padding: 15px;
    background: #fff;
}

.chat-input input,
.chat-input select {
    border-radius: 10px !important;
}

.chat-input button {
    border-radius: 10px;
    background: var(--primary-text);
    border: none;
}

.chat-input button:hover {
    background: var(--primary-dark);
}
        .conversations-list {
            width: 350px;
            border-left: 1px solid #dee2e6;
            background: #f8f9fa;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .conversation-item:hover {
            background: #e9ecef;
        }
        .conversation-item.active {
            background: #007bff;
            color: white;
        }
        .conversation-item .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .chat-messages {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #ffffff;
        }
        .messages-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        .message {
            margin-bottom: 15px;
            max-width: 70%;
        }
        .message.sent {
            margin-left: auto;
            text-align: right;
        }
        .message.received {
            margin-right: auto;
        }
        .message-bubble {
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        .message.sent .message-bubble {
            background: #007bff;
            color: white;
        }
        .message.received .message-bubble {
            background: #f1f3f4;
            color: #333;
        }
        .message-time {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .chat-input {
            border-top: 1px solid #dee2e6;
            padding: 15px;
            background: #f8f9fa;
        }
        .online-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #28a745;
            display: inline-block;
            margin-left: 5px;
        }
  
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

    <!--main content-->
    <div class="main-container">
    <div class="dashboard-container">
        <div class="main-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="page-header">
    <i class="bi bi-chat-dots"></i> الرسائل والدردشة
</div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                <i class="fas fa-plus"></i> رسالة جديدة
            </button>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="chat-container">
            <!-- Conversation list-->
            <div class="conversations-list">
                <div class="p-3 border-bottom">
                    <h6>المحادثات</h6>
                </div>
                <?php if ($conversations_result->num_rows > 0): ?>
                    <?php while ($conversation = $conversations_result->fetch_assoc()): ?>
                        <div class="conversation-item <?php echo (isset($_GET['doctor_id']) && $_GET['doctor_id'] == $conversation['id']) ? 'active' : ''; ?>"
                             onclick="openConversation(<?php echo $conversation['id']; ?>)">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center">
                                        <strong><?php echo htmlspecialchars($conversation['full_name']); ?></strong>
                                        <span class="online-indicator" title="متصل"></span>
                                    </div>
                                    <p class="mb-1 small text-muted">
                                        <?php echo htmlspecialchars(substr($conversation['last_message'] ?? 'لا توجد رسائل', 0, 50)); ?>
                                    </p>
                                    <small class="text-muted">
                                        <?php echo $conversation['last_message_time'] ? date('d/m H:i', strtotime($conversation['last_message_time'])) : ''; ?>
                                    </small>
                                </div>
                                <?php if ($conversation['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p>لا توجد محادثات</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            ابدأ محادثة جديدة
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chat area-->
            <div class="chat-messages">
                <?php if ($selected_doctor): ?>
                    <div class="chat-header">
                        <h6><?php echo htmlspecialchars($selected_doctor['full_name']); ?></h6>
                    </div>

                    <div class="messages-list" id="messagesList">
                        <?php if ($messages_result && $messages_result->num_rows > 0): ?>
                            <?php while ($message = $messages_result->fetch_assoc()): ?>
                                <div class="message <?php echo $message['is_mine'] ? 'sent' : 'received'; ?>">
                                    <div class="message-bubble">
                                        <?php echo htmlspecialchars($message['message']); ?>
                                    </div>
                                    <div class="message-time">
                                        <?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted mt-5">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>لا توجد رسائل في هذه المحادثة</p>
                                <p>ابدأ المحادثة بإرسال رسالة</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="chat-input">
                        <form method="POST" id="sendMessageForm">
                            <input type="hidden" name="recipient_id" value="<?php echo $doctor_id; ?>">
                            <div class="input-group">
                                <select name="child_id" class="form-select" style="max-width: 150px;">
                                    <option value="">اختر الطفل (اختياري)</option>
                                    <?php
                                    $children_result->data_seek(0);
                                    while ($child = $children_result->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <select name="message_type" class="form-select" style="max-width: 120px;">
                                    <option value="general">عام</option>
                                    <option value="consultation">استشارة</option>
                                    <option value="urgent">عاجل</option>
                                </select>
                                <input type="text" name="message" class="form-control" placeholder="اكتب رسالتك هنا..." required>
                                <button type="submit" name="send_message" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <div class="text-center">
                            <i class="fas fa-comments fa-4x mb-3"></i>
                            <h5>اختر محادثة لبدء الدردشة</h5>
                            <p>انقر على أحد الأطباء من القائمة الجانبية</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
       </div>
    </div>
</div>

    <!-- Modal رسالة جديدة -->
    <div class="modal fade" id="newMessageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">رسالة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">المستلم (الطبيب)</label>
                            <select name="recipient_id" class="form-select" required>
                                <option value="">اختر الطبيب</option>
                                <?php
                                $doctors_result->data_seek(0);
                                while ($doctor = $doctors_result->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['full_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الطفل (اختياري)</label>
                            <select name="child_id" class="form-select">
                                <option value="">اختر الطفل</option>
                                <?php
                                $children_result->data_seek(0);
                                while ($child = $children_result->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">نوع الرسالة</label>
                            <select name="message_type" class="form-select">
                                <option value="general">عام</option>
                                <option value="consultation">استشارة طبية</option>
                                <option value="urgent">عاجل</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الرسالة</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="اكتب رسالتك هنا..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="send_message" class="btn btn-primary">إرسال الرسالة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openConversation(doctorId) {
            window.location.href = 'messages.php?doctor_id=' + doctorId;
        }

        // التمرير إلى أسفل الرسائل
        <?php if ($messages_result): ?>
            document.getElementById('messagesList').scrollTop = document.getElementById('messagesList').scrollHeight;
        <?php endif; ?>

        // تحديث الرسائل كل 30 ثانية
        <?php if (isset($_GET['doctor_id'])): ?>
            setInterval(function() {
                const doctorId = <?php echo $_GET['doctor_id']; ?>;
                fetch('check_new_messages.php?doctor_id=' + doctorId)
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

                            // تحديث حالة الرسائل كمقروءة
                            fetch('mark_messages_read.php?doctor_id=' + doctorId);
                        }
                    })
                    .catch(error => console.error('Error checking messages:', error));
            }, 30000); // كل 30 ثانية
        <?php endif; ?>
    </script>
</body>
</html>
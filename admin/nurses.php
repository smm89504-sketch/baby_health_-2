<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

//Bring the nurses
$query = "SELECT id, full_name, email, phone, created_at FROM users WHERE user_type = 'nurse' ORDER BY created_at DESC";
$result = $conn->query($query);
$nurses = [];
while ($row = $result->fetch_assoc()) {
    $nurses[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الممرضات - لوحة التحكم</title>
    <link rel="stylesheet" href="admin_shared.css">
    <style> .details-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.detail-item{padding:6px 0} </style>
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>🩺 الممرضات</h1>
            </div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?></div>
                <div>
                    <small style="color: #7a6880;">مرحباً</small>
                    <div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'إدمن'); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h1>إدارة الممرضات</h1>
        <div class="search-box">
            <input type="text" class="search-input" id="nurses-search" placeholder="ابحث عن الممرضات...">
            <button class="btn btn-primary" onclick="openAddNurseModal()">+ إضافة ممرضة</button>
            <button class="btn btn-secondary" onclick="exportTable('nurses')">⤓ تصدير CSV</button>
        </div>

        <div class="table-container">
            <table class="admin-table" id="nurses-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الهاتف</th>
                        <th>تاريخ التسجيل</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nurses as $nurse): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($nurse['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($nurse['email']); ?></td>
                        <td><?php echo htmlspecialchars($nurse['phone']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($nurse['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-secondary" onclick="viewNurseDetails(<?php echo $nurse['id']; ?>)">📋 عرض</button>
                                <button class="btn btn-small btn-secondary" onclick="openEditNurseModal(<?php echo $nurse['id']; ?>)">✏️ تعديل</button>
                                <button class="btn btn-small btn-danger btn-delete" data-action="delete_nurse" data-param="user_id" data-id="<?php echo $nurse['id']; ?>" data-label="<?php echo htmlspecialchars($nurse['full_name']); ?>">🗑️ حذف</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Nurse Modal -->
    <div id="addNurseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>إضافة ممرضة جديدة</h2>
                <button class="close" onclick="closeModal('addNurseModal')">&times;</button>
            </div>
            <form id="addNurseForm" onsubmit="submitAddNurse(event)">
                <div class="form-group">
                    <label>الاسم الكامل *</label>
                    <input type="text" name="full_name" required placeholder="أدخل الاسم الكامل">
                </div>
                <div class="form-group">
                    <label>البريد الإلكتروني *</label>
                    <input type="email" name="email" required placeholder="أدخل البريد الإلكتروني">
                </div>
                <div class="form-group">
                    <label>رقم الهاتف *</label>
                    <input type="tel" name="phone" required placeholder="أدخل رقم الهاتف">
                </div>
                <div class="form-group">
                    <label>كلمة المرور *</label>
                    <input type="password" name="password" required placeholder="أدخل كلمة مرور">
                    <small style="color: #999; display: block; margin-top: 5px;">يجب أن تحتوي على: 8 أحرف على الأقل، حرف كبير، حرف صغير، رقم، ورمز (!@#$%^&*).</small>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addNurseModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Nurse Modal -->
    <div id="editNurseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>تعديل بيانات الممرضة</h2>
                <button class="close" onclick="closeModal('editNurseModal')">&times;</button>
            </div>
            <form id="editNurseForm" onsubmit="submitEditNurse(event)">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label>الاسم الكامل *</label>
                    <input type="text" name="full_name" id="edit_full_name" required placeholder="أدخل الاسم الكامل">
                </div>
                <div class="form-group">
                    <label>البريد الإلكتروني *</label>
                    <input type="email" name="email" id="edit_email" required placeholder="أدخل البريد الإلكتروني">
                </div>
                <div class="form-group">
                    <label>رقم الهاتف *</label>
                    <input type="tel" name="phone" id="edit_phone" required placeholder="أدخل رقم الهاتف">
                </div>
                <div class="form-group">
                    <label>كلمة المرور (اتركها فارغة إذا لم تريد تغييرها)</label>
                    <input type="password" name="password" id="edit_password" placeholder="أدخل كلمة مرور جديدة (اختياري)">
                    <small style="color: #999; display: block; margin-top: 5px;">إذا أدخلت كلمة مرور جديدة، يجب أن تحتوي على: 8 أحرف على الأقل، حرف كبير، حرف صغير، رقم، ورمز (!@#$%^&*).</small>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editNurseModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewNurseModal" class="modal" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>تفاصيل الممرضة</h2>
                <button class="close" onclick="closeModal('viewNurseModal')">&times;</button>
            </div>
            <div id="nurseDetailsContent"><p style="text-align:center;color:#999;">جاري التحميل...</p></div>
        </div>
    </div>

    <script src="admin_shared.js"></script>
    <script>
        function openAddNurseModal() {
            document.getElementById('addNurseForm').reset();
            openModal('addNurseModal');
        }

        function openEditNurseModal(userId) {
            openModal('editNurseModal');
            loadNurseForEdit(userId);
        }

        function loadNurseForEdit(userId) {
            fetch('../admin_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'get_user_details', user_id: userId})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const u = data.data;
                    document.getElementById('edit_user_id').value = u.id;
                    document.getElementById('edit_full_name').value = u.full_name;
                    document.getElementById('edit_email').value = u.email;
                    document.getElementById('edit_phone').value = u.phone;
                } else {
                    showAlert('خطأ: ' + data.message, 'danger');
                }
            })
            .catch(e => { console.error(e); showAlert('حدث خطأ', 'danger'); });
        }

        function viewNurseDetails(userId) {
            openModal('viewNurseModal');
            const el = document.getElementById('nurseDetailsContent');
            el.innerHTML = '<p style="text-align:center;color:#999;">جاري التحميل...</p>';
            fetch('../admin_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'get_user_details', user_id: userId})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const u = data.data;
                    let html = `
                        <div style="padding:10px;">
                            <h3>${escapeHtml(u.full_name)}</h3>
                            <p><strong>البريد الإلكتروني:</strong> ${escapeHtml(u.email)}</p>
                            <p><strong>الهاتف:</strong> ${escapeHtml(u.phone)}</p>
                            <p><strong>تاريخ التسجيل:</strong> ${u.created_at}</p>
                            <p><strong>آخر تسجيل دخول:</strong> ${u.last_login || '-'} </p>
                        </div>
                    `;
                    // show operations for nurse if available
                    if (u.extra) {
                        if (u.extra.vaccines && u.extra.vaccines.length) {
                            html += `
                                <h4 style="margin-top:20px;">التطعيمات التي أدخلتها</h4>
                                <table class="admin-table" style="width:100%;font-size:90%;">
                                    <thead><tr><th>الطفل</th><th>اللقاح</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                                    <tbody>`;
                            u.extra.vaccines.forEach(v => {
                                const date = v.administered_date || v.due_date;
                                html += `<tr><td>${escapeHtml(v.child_name)}</td><td>${escapeHtml(v.vaccine_name)}</td><td>${escapeHtml(v.status)}</td><td>${date}</td></tr>`;
                            });
                            html += `</tbody></table>`;
                        } else {
                            html += `<p style="color:#666;margin-top:10px;">لا توجد تطعيمات مسجلة.</p>`;
                        }
                        if (u.extra.notes && u.extra.notes.length) {
                            html += `
                                <h4 style="margin-top:20px;">الملاحظات المهنية</h4>
                                <ul>`;
                            u.extra.notes.forEach(n => {
                                html += `<li><strong>${escapeHtml(n.child_name)}</strong> &ndash; ${escapeHtml(n.note_content)} <small>(${n.created_at})</small></li>`;
                            });
                            html += `</ul>`;
                        }
                    }
                    el.innerHTML = html;
                    // fetch and append stats
                    fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                        body:JSON.stringify({action:'get_user_stats',user_id:userId})
                    }).then(r=>r.json()).then(s=>{
                        if(s.success){
                            const statsHtml = `<p style="margin-top:10px;"><strong>عدد التطعيمات:</strong> ${s.vaccine_count} &nbsp; <strong>عدد الملاحظات:</strong> ${s.note_count}</p>`;
                            el.insertAdjacentHTML('beforeend', statsHtml);
                        }
                    });
                } else {
                    el.innerHTML = '<p style="color:#ef4444;">خطأ: ' + data.message + '</p>';
                }
            })
            .catch(e => { console.error(e); el.innerHTML = '<p style="color:#ef4444;">حدث خطأ</p>'; });
        }

        function deleteNurse(userId, name) {
            if (!confirm('هل أنت متأكد من حذف ' + name + '؟')) return;
            showAlert('جاري الحذف...', 'info');
            fetch('../admin_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'delete_nurse', user_id: userId})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showAlert('تم الحذف', 'success'); setTimeout(()=>location.reload(),1200); }
                else showAlert('خطأ: '+data.message, 'danger');
            })
            .catch(e => { console.error(e); showAlert('حدث خطأ', 'danger'); });
        }

        function submitAddNurse(e) {
            e.preventDefault();
            const fd = new FormData(document.getElementById('addNurseForm'));
            const full_name = fd.get('full_name');
            const email = fd.get('email');
            const phone = fd.get('phone');
            const password = fd.get('password');

            // validate password strength
            const strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/;
            if (!strong.test(password)) {
                showAlert('كلمة السر ضعيفة. يجب استخدامها حسب الشروط.', 'danger');
                return;
            }

            showAlert('جاري الإضافة...', 'info');
            fetch('../admin_handler.php', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({action: 'add_nurse', full_name, email, phone, password})
            })
            .then(r=>r.json())
            .then(data => {
                if (data.success) {
                    showAlert('تم إضافة الممرضة.', 'success');
                    closeModal('addNurseModal');
                    setTimeout(()=>location.reload(),1500);
                } else showAlert('خطأ: '+data.message,'danger');
            })
            .catch(e=>{console.error(e); showAlert('حدث خطأ','danger');});
        }

        function submitEditNurse(e) {
            e.preventDefault();
            const fd = new FormData(document.getElementById('editNurseForm'));
            const user_id = fd.get('user_id');
            const full_name = fd.get('full_name');
            const email = fd.get('email');
            const phone = fd.get('phone');
            const password = fd.get('password');

            if (password) {
                const strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/;
                if (!strong.test(password)) {
                    showAlert('كلمة السر الجديدة ضعيفة.', 'danger');
                    return;
                }
            }

            showAlert('جاري الحفظ...', 'info');
            fetch('../admin_handler.php', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({action: 'update_nurse', user_id, full_name, email, phone, password: password || null})
            })
            .then(r=>r.json())
            .then(data => {
                if (data.success) { showAlert('تم الحفظ','success'); closeModal('editNurseModal'); setTimeout(()=>location.reload(),1200); }
                else showAlert('خطأ: '+data.message,'danger');
            })
            .catch(e=>{console.error(e); showAlert('حدث خطأ','danger');});
        }

        // modal helpers
        function openModal(id){ document.getElementById(id).classList.add('show'); }
        function closeModal(id){ document.getElementById(id).classList.remove('show'); }
        document.addEventListener('click', function(ev){ if (ev.target.classList.contains('modal')) ev.target.classList.remove('show'); });

        document.addEventListener('DOMContentLoaded', ()=>{ attachSearchListener('nurses-search','nurses-table'); });

        function exportTable(table) {
            showAlert('جاري التصدير...','info');
            fetch('../admin_handler.php',{
                method:'POST',headers:{'Content-Type':'application/json'},
                body: JSON.stringify({action:'export_data',table:table})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    const blob = new Blob([data.csv], {type:'text/csv;charset=utf-8;'});
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = table + '.csv';
                    a.click();
                    URL.revokeObjectURL(url);
                } else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
    </script>
</body>
</html>

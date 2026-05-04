<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Parents brought along the number of their children
$query = "SELECT u.id, u.full_name, u.email, u.phone, u.created_at, COUNT(c.id) as children_count
          FROM users u
          LEFT JOIN children c ON u.id = c.user_id AND c.is_archived = 0
          WHERE u.user_type = 'parent'
          GROUP BY u.id
          ORDER BY u.created_at DESC";
$result = $conn->query($query);
$parents = [];
while ($row = $result->fetch_assoc()) {
    $parents[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الآباء - لوحة التحكم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
    <!--top strip-->
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>👨‍👩‍👧 الآباء</h1>
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

    <!-- sidebar-->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content-->
    <main class="main-content">
        <h1>إدارة الآباء والأمهات</h1>
        
        <div class="search-box">
            <input type="text" class="search-input" id="parents-search" placeholder="ابحث عن الآباء...">
            <button class="btn btn-primary" onclick="openAddParentModal()">+ إضافة أب/أم</button>
            <button class="btn btn-secondary" onclick="exportTable('parents')">⤓ تصدير CSV</button>
        </div>

        <div class="table-container">
            <table class="admin-table" id="parents-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الهاتف</th>
                        <th>عدد الأطفال</th>
                        <th>تاريخ التسجيل</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parents as $parent): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($parent['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($parent['email']); ?></td>
                        <td><?php echo htmlspecialchars($parent['phone']); ?></td>
                        <td><span class="badge"><?php echo $parent['children_count']; ?> طفل</span></td>
                        <td><?php echo date('Y-m-d', strtotime($parent['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-secondary" onclick="viewParentDetails(<?php echo $parent['id']; ?>)">📋 عرض</button>
                                <button class="btn btn-small btn-secondary" onclick="openEditParentModal(<?php echo $parent['id']; ?>)">✏️ تعديل</button>
                                <button class="btn btn-small btn-danger btn-delete" data-action="delete_parent" data-param="parent_id" data-id="<?php echo $parent['id']; ?>" data-label="<?php echo htmlspecialchars($parent['full_name']); ?>">🗑️ حذف</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal:Add a new father/mother-->
    <div id="addParentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>إضافة أب/أم جديد</h2>
                <button class="close" onclick="closeModal('addParentModal')">&times;</button>
            </div>
            <form id="addParentForm" onsubmit="submitAddParent(event)">
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
                    <small style="color: #999; display: block; margin-top: 5px;">يجب أن تحتوي على: 8 أحرف على الأقل، حرف كبير، حرف صغير، رقم، ورمز (!@#$%^&*)</small>
                </div>
                <div class="form-group">
                    <label>تأكيد كلمة المرور *</label>
                    <input type="password" name="password_confirm" required placeholder="أكد كلمة المرور">
                </div>
                <div class="form-group">
                    <label>سؤال الأمان *</label>
                    <input type="text" name="security_question" required placeholder="أدخل سؤال الأمان">
                </div>
                <div class="form-group">
                    <label>إجابة سؤال الأمان *</label>
                    <input type="text" name="security_answer" required placeholder="أدخل إجابة السؤال">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addParentModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit father/mother data-->
    <div id="editParentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>تعديل بيانات الأب/الأم</h2>
                <button class="close" onclick="closeModal('editParentModal')">&times;</button>
            </div>
            <form id="editParentForm" onsubmit="submitEditParent(event)">
                <input type="hidden" name="parent_id" id="edit_parent_id">
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
                    <small style="color: #999; display: block; margin-top: 5px;">إذا أدخلت كلمة مرور جديدة، يجب أن تحتوي على: 8 أحرف على الأقل، حرف كبير، حرف صغير، رقم، ورمز (!@#$%^&*)</small>
                </div>
                <div class="form-group">
                    <label>سؤال الأمان *</label>
                    <input type="text" name="security_question" id="edit_security_question" required placeholder="أدخل سؤال الأمان">
                </div>
                <div class="form-group">
                    <label>إجابة سؤال الأمان *</label>
                    <input type="text" name="security_answer" id="edit_security_answer" required placeholder="أدخل إجابة السؤال">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editParentModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: View father/mother details-->
    <div id="viewParentModal" class="modal" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2>تفاصيل الأب/الأم</h2>
                <button class="close" onclick="closeModal('viewParentModal')">&times;</button>
            </div>
            <div id="parentDetailsContent">
                <p style="text-align: center; color: #999;">جاري التحميل...</p>
            </div>
        </div>
    </div>

    <script src="admin_shared.js"></script>
    <script>
        // فتح modal To add a new father/mother
        function openAddParentModal() {
            document.getElementById('addParentForm').reset();
            openModal('addParentModal');
        }

        // فتح modal To edit father/mother
        function openEditParentModal(parentId) {
            openModal('editParentModal');
            loadParentDataForEdit(parentId);
        }

        //Upload father/mother data for editing
        function loadParentDataForEdit(parentId) {
            fetch('../admin_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'get_parent_details',
                    parent_id: parentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const parent = data.data;
                    document.getElementById('edit_parent_id').value = parentId;
                    document.getElementById('edit_full_name').value = parent.full_name;
                    document.getElementById('edit_email').value = parent.email;
                    document.getElementById('edit_phone').value = parent.phone;
                    document.getElementById('edit_security_question').value = parent.security_question;
                    document.getElementById('edit_security_answer').value = parent.security_answer;
                } else {
                    showAlert('خطأ في تحميل البيانات: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('خطأ:', error);
                showAlert('حدث خطأ في تحميل البيانات', 'danger');
            });
        }

        // View father/mother details
        function viewParentDetails(parentId) {
            openModal('viewParentModal');
            const contentDiv = document.getElementById('parentDetailsContent');
            contentDiv.innerHTML = '<p style="text-align: center; color: #999;">جاري التحميل...</p>';
            
            fetch('../admin_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'get_parent_full_details',
                    parent_id: parentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const parent = data.data;
                    contentDiv.innerHTML = buildParentDetailsHTML(parent);
                } else {
                    contentDiv.innerHTML = '<p style="color: #ef4444;">خطأ: ' + data.message + '</p>';
                }
            })
            .catch(error => {
                console.error('خطأ:', error);
                contentDiv.innerHTML = '<p style="color: #ef4444;">حدث خطأ في تحميل البيانات</p>';
            });
        }

        // بناء HTML Father's/Mother's Details
        function buildParentDetailsHTML(parent) {
            let html = `
                <div style="margin-bottom: 30px;">
                    <h3>البيانات الشخصية</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <strong>الاسم:</strong>
                            <span>${escapeHtml(parent.full_name)}</span>
                        </div>
                        <div class="detail-item">
                            <strong>البريد الإلكتروني:</strong>
                            <span>${escapeHtml(parent.email)}</span>
                        </div>
                        <div class="detail-item">
                            <strong>الهاتف:</strong>
                            <span>${escapeHtml(parent.phone)}</span>
                        </div>
                        <div class="detail-item">
                            <strong>تاريخ التسجيل:</strong>
                            <span>${parent.created_at}</span>
                        </div>
                    </div>
                </div>
            `;

            //children
            if (parent.children && parent.children.length > 0) {
                html += `<h3>الأطفال (${parent.children.length})</h3>`;
                parent.children.forEach(child => {
                    html += `
                        <div style="background: #f5f0ff; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                            <strong>${escapeHtml(child.name)}</strong>
                            <br><small>
                                تاريخ الميلاد: ${child.birth_date} | 
                                الوزن: ${child.weight} كغ | 
                                الطول: ${child.height} سم
                            </small>
                        </div>
                    `;
                });
            } else {
                html += '<p style="color: #999;">لا توجد أطفال مسجلة لهذا الأب/الأم</p>';
            }

            //Recent activities
            if (parent.recent_activities && parent.recent_activities.length > 0) {
                html += `<h3 style="margin-top: 30px;">آخر الأنشطة المسجلة</h3>`;
                parent.recent_activities.forEach(activity => {
                    html += `
                        <div style="background: #f5f0ff; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                            <strong>${escapeHtml(activity.child_name)}</strong>
                            <br><small>
                                ${getActivityLabel(activity.activity_type)} - ${activity.date}
                            </small>
                            <br><small style="color: #999;">${activity.details || 'بدون تفاصيل'}</small>
                        </div>
                    `;
                });
            }

            // vaccinations
            if (parent.vaccines && parent.vaccines.length > 0) {
                html += `<h3 style="margin-top: 30px;">حالة التطعيمات</h3>`;
                let vaccineStatus = { due: 0, administered: 0, missed: 0 };
                parent.vaccines.forEach(vaccine => {
                    vaccineStatus[vaccine.status]++;
                });
                html += `
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px;">
                        <div style="background: #fef3c7; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #f59e0b;">${vaccineStatus.due}</div>
                            <small>مستحقة</small>
                        </div>
                        <div style="background: #d1fae5; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #10b981;">${vaccineStatus.administered}</div>
                            <small>مقدمة</small>
                        </div>
                        <div style="background: #fee2e2; padding: 15px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #ef4444;">${vaccineStatus.missed}</div>
                            <small>فاتت</small>
                        </div>
                    </div>
                `;
            }

            return html;
        }

        //Delete father/mother
        function deleteParent(parentId, parentName) {
            if (!confirm('هل أنت متأكد من حذف ' + parentName + '؟\nسيتم حذف جميع بيانات الأطفال المرتبطة به أيضاً.')) {
                return;
            }

            showAlert('جاري حذف الأب/الأم...', 'info');
            fetch('../admin_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete_parent',
                    parent_id: parentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('تم حذف الأب/الأم بنجاح', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('خطأ: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('خطأ:', error);
                showAlert('حدث خطأ في حذف الأب/الأم', 'danger');
            });
        }

        // Submit a parent/parent addition form
        function submitAddParent(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('addParentForm'));
            const full_name = formData.get('full_name');
            const email = formData.get('email');
            const phone = formData.get('phone');
            const password = formData.get('password');
            const password_confirm = formData.get('password_confirm');
            const security_question = formData.get('security_question');
            const security_answer = formData.get('security_answer');

            //Password verification
            if (password !== password_confirm) {
                showAlert('كلمات المرور غير متطابقة', 'danger');
                return;
            }

            //Password strength check
            const strongPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/;
            if (!strongPassword.test(password)) {
                showAlert('كلمة السر ضعيفة. يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير وصغير، رقم ورمز (!@#$%^&*).', 'danger');
                return;
            }

            showAlert('جاري إضافة الأب/الأم...', 'info');
            fetch('../admin_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'add_parent',
                    full_name: full_name,
                    email: email,
                    phone: phone,
                    password: password,
                    security_question: security_question,
                    security_answer: security_answer
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('تم إضافة الأب/الأم بنجاح', 'success');
                    closeModal('addParentModal');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('خطأ: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('خطأ:', error);
                showAlert('حدث خطأ في إضافة الأب/الأم', 'danger');
            });
        }

        // Sending a parent/mother modification form
        function submitEditParent(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('editParentForm'));
            const parentId = formData.get('parent_id');
            const full_name = formData.get('full_name');
            const email = formData.get('email');
            const phone = formData.get('phone');
            const password = formData.get('password');
            const security_question = formData.get('security_question');
            const security_answer = formData.get('security_answer');

            // Check the strength of the password if a new one is entered.
            if (password) {
                const strongPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/;
                if (!strongPassword.test(password)) {
                    showAlert('كلمة السر ضعيفة. يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير وصغير، رقم ورمز (!@#$%^&*).', 'danger');
                    return;
                }
            }

            showAlert('جاري حفظ التعديلات...', 'info');
            fetch('../admin_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update_parent',
                    parent_id: parentId,
                    full_name: full_name,
                    email: email,
                    phone: phone,
                    password: password || null,
                    security_question: security_question,
                    security_answer: security_answer
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('تم حفظ التعديلات بنجاح', 'success');
                    closeModal('editParentModal');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('خطأ: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('خطأ:', error);
                showAlert('حدث خطأ في حفظ التعديلات', 'danger');
            });
        }

        // فتح/إغلاق modal
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
            }
        }

        // إغلاق modal When you click on the background
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        });

        // Search
        document.addEventListener('DOMContentLoaded', () => {
            attachSearchListener('parents-search', 'parents-table');
        });

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

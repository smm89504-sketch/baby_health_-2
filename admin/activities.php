<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// load children list
$childRes = $conn->query("SELECT id, name FROM children WHERE is_archived = 0");
$children = [];
while ($c = $childRes->fetch_assoc()) { $children[] = $c; }

$query = "SELECT da.id, da.activity_type, da.date, da.details, da.child_id, c.name as child_name
          FROM daily_activities da
          LEFT JOIN children c ON da.child_id = c.id
          ORDER BY da.created_at DESC";
$result = $conn->query($query);
$activities = [];
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأنشطة - لوحة التحكم</title>
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>📋 الأنشطة</h1>
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
        <h1>إدارة الأنشطة</h1>
        <div class="search-box">
            <input type="text" class="search-input" id="activities-search" placeholder="ابحث عن الأنشطة...">
            <button class="btn btn-primary" onclick="openAddActivityModal()">+ إضافة نشاط</button>
        </div>

        <div class="table-container">
            <table class="admin-table" id="activities-table">
                <thead>
                    <tr>
                        <th>الطفل</th>
                        <th>نوع النشاط</th>
                        <th>التاريخ</th>
                        <th>التفاصيل</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $act): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($act['child_name'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($act['activity_type']); ?></td>
                        <td><?php echo $act['date']; ?></td>
                        <td><?php echo htmlspecialchars($act['details']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-secondary" onclick="viewActivity(<?php echo $act['id']; ?>)">📋 عرض</button>
                                <button class="btn btn-small btn-secondary" onclick="openEditActivityModal(<?php echo $act['id']; ?>)">✏️ تعديل</button>
                                <button class="btn btn-small btn-danger btn-delete" data-action="delete_activity" data-param="activity_id" data-id="<?php echo $act['id']; ?>">🗑️ حذف</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add/Edit/View modals -->
    <div id="addActivityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>إضافة نشاط</h2>
                <button class="close" onclick="closeModal('addActivityModal')">&times;</button>
            </div>
            <form id="addActivityForm" onsubmit="submitAddActivity(event)">
                <div class="form-group">
                    <label>الطفل *</label>
                    <select name="child_id" required>
                        <option value="">-- اختر --</option>
                        <?php foreach ($children as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>نوع النشاط *</label>
                    <input type="text" name="activity_type" required>
                </div>
                <div class="form-group">
                    <label>التاريخ *</label>
                    <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>التفاصيل</label>
                    <textarea name="details"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addActivityModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editActivityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>تعديل النشاط</h2>
                <button class="close" onclick="closeModal('editActivityModal')">&times;</button>
            </div>
            <form id="editActivityForm" onsubmit="submitEditActivity(event)">
                <input type="hidden" name="activity_id" id="edit_activity_id">
                <div class="form-group">
                    <label>الطفل *</label>
                    <select name="child_id" id="edit_child_id" required>
                        <option value="">-- اختر --</option>
                        <?php foreach ($children as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>نوع النشاط *</label>
                    <input type="text" name="activity_type" id="edit_activity_type" required>
                </div>
                <div class="form-group">
                    <label>التاريخ *</label>
                    <input type="date" name="date" id="edit_date" required>
                </div>
                <div class="form-group">
                    <label>التفاصيل</label>
                    <textarea name="details" id="edit_details"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewActivityModal" class="modal" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="max-width:600px;">
            <div class="modal-header">
                <h2>تفاصيل النشاط</h2>
                <button class="close" onclick="closeModal('viewActivityModal')">&times;</button>
            </div>
            <div id="activityDetailsContent"><p style="text-align:center;color:#999;">جاري التحميل...</p></div>
        </div>
    </div>

    <script src="admin_shared.js"></script>
    <script>
        function openAddActivityModal(){document.getElementById('addActivityForm').reset();openModal('addActivityModal');}
        function openEditActivityModal(id){openModal('editActivityModal');loadActivityForEdit(id);}
        function loadActivityForEdit(id){
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'get_activity_details', activity_id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    const a=data.data;
                    document.getElementById('edit_activity_id').value=a.id;
                    document.getElementById('edit_child_id').value=a.child_id;
                    document.getElementById('edit_activity_type').value=a.activity_type;
                    document.getElementById('edit_date').value=a.date;
                    document.getElementById('edit_details').value=a.details;
                } else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function viewActivity(id){
            openModal('viewActivityModal');
            const el=document.getElementById('activityDetailsContent');
            el.innerHTML='<p style="text-align:center;color:#999;">جاري التحميل...</p>';
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'get_activity_details', activity_id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    const a=data.data;
                    el.innerHTML=`
                        <div class="details-grid">
                            <div class="detail-item"><strong>الطفل:</strong> ${escapeHtml(a.child_name||'-')}</div>
                            <div class="detail-item"><strong>النشاط:</strong> ${escapeHtml(a.activity_type)}</div>
                            <div class="detail-item"><strong>التاريخ:</strong> ${a.date}</div>
                            <div class="detail-item"><strong>التفاصيل:</strong> ${escapeHtml(a.details||'-')}</div>
                        </div>`;
                } else el.innerHTML='<p style="color:#ef4444;">خطأ: '+data.message+'</p>';
            }).catch(e=>{console.error(e);el.innerHTML='<p style="color:#ef4444;">حدث خطأ</p>';});
        }
        function deleteActivity(id){
            if(!confirm('هل أنت متأكد من حذف النشاط؟'))return;
            showAlert('جاري الحذف...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'delete_activity', activity_id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الحذف','success');setTimeout(()=>location.reload(),1200);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function submitAddActivity(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('addActivityForm'));
            const child_id=fd.get('child_id');const activity_type=fd.get('activity_type');
            const date=fd.get('date');const details=fd.get('details');
            showAlert('جاري الإضافة...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'add_activity', child_id, activity_type, date, details})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الإضافة','success');closeModal('addActivityModal');setTimeout(()=>location.reload(),1500);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function submitEditActivity(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('editActivityForm'));
            const activity_id=fd.get('activity_id');const child_id=fd.get('child_id');
            const activity_type=fd.get('activity_type');const date=fd.get('date');const details=fd.get('details');
            showAlert('جاري الحفظ...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'update_activity', activity_id, child_id, activity_type, date, details})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الحفظ','success');closeModal('editActivityModal');setTimeout(()=>location.reload(),1200);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function openModal(id){document.getElementById(id).classList.add('show');}
        function closeModal(id){document.getElementById(id).classList.remove('show');}
        document.addEventListener('click',evt=>{if(evt.target.classList.contains('modal'))evt.target.classList.remove('show');});
        document.addEventListener('DOMContentLoaded',()=>{attachSearchListener('activities-search','activities-table');});
    </script>
</body>
</html>

<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// fetch vaccine types
$query = "SELECT id, name, target_age, description, created_at FROM vaccines ORDER BY created_at DESC";
$res = $conn->query($query);
$types = [];
while ($row = $res->fetch_assoc()) { $types[] = $row; }
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أنواع التطعيمات - لوحة التحكم</title>
    <link rel="stylesheet" href="admin_shared.css">
    <style>.details-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.detail-item{padding:6px 0}</style>
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>💉 أنواع التطعيمات</h1>
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
        <h1>إدارة أنواع التطعيمات</h1>
        <div class="search-box">
            <input type="text" class="search-input" id="vaccinetypes-search" placeholder="ابحث عن النوع...">
            <button class="btn btn-primary" onclick="openAddTypeModal()">+ إضافة نوع جديد</button>
        </div>

        <div class="table-container">
            <table class="admin-table" id="vaccinetypes-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الفئة العمرية</th>
                        <th>الوصف</th>
                        <th>تاريخ الإضافة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($types as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['name']); ?></td>
                        <td><?php echo htmlspecialchars($t['target_age']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($t['description'])); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($t['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-secondary" onclick="viewType(<?php echo $t['id']; ?>)">📋 عرض</button>
                                <button class="btn btn-small btn-secondary" onclick="openEditTypeModal(<?php echo $t['id']; ?>)">✏️ تعديل</button>
                                <button class="btn btn-small btn-danger btn-delete" data-action="delete_vaccine_type" data-id="<?php echo $t['id']; ?>">🗑️ حذف</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add/Edit/View modals -->
    <div id="addTypeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>إضافة نوع تطعيم</h2>
                <button class="close" onclick="closeModal('addTypeModal')">&times;</button>
            </div>
            <form id="addTypeForm" onsubmit="submitAddType(event)">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>الفئة العمرية *</label>
                    <input type="text" name="target_age" required>
                </div>
                <div class="form-group">
                    <label>الوصف</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addTypeModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editTypeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>تعديل النوع</h2>
                <button class="close" onclick="closeModal('editTypeModal')">&times;</button>
            </div>
            <form id="editTypeForm" onsubmit="submitEditType(event)">
                <input type="hidden" name="id" id="edit_type_id">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>الفئة العمرية *</label>
                    <input type="text" name="target_age" id="edit_target_age" required>
                </div>
                <div class="form-group">
                    <label>الوصف</label>
                    <textarea name="description" id="edit_description"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editTypeModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewTypeModal" class="modal" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="max-width:600px;">
            <div class="modal-header">
                <h2>تفاصيل النوع</h2>
                <button class="close" onclick="closeModal('viewTypeModal')">&times;</button>
            </div>
            <div id="typeDetailsContent"><p style="text-align:center;color:#999;">جاري التحميل...</p></div>
        </div>
    </div>

    <script src="admin_shared.js"></script>
    <script>
        function openAddTypeModal(){document.getElementById('addTypeForm').reset();openModal('addTypeModal');}
        function openEditTypeModal(id){openModal('editTypeModal');loadTypeForEdit(id);}
        function loadTypeForEdit(id){
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',
                body:JSON.stringify({action:'get_vaccine_type_details', id:id})
            }).then(r=>r.json()).then(data=>{
                console.log('loadTypeForEdit response', data);
                if(data.success){
                    const t = data.data;
                    document.getElementById('edit_type_id').value=t.id;
                    document.getElementById('edit_name').value=t.name;
                    document.getElementById('edit_target_age').value=t.target_age;
                    document.getElementById('edit_description').value=t.description;
                } else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function viewType(id){
            openModal('viewTypeModal');
            const el=document.getElementById('typeDetailsContent');
            el.innerHTML='<p style="text-align:center;color:#999;">جاري التحميل...</p>';
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',
                body:JSON.stringify({action:'get_vaccine_type_details', id:id})
            }).then(r=>r.json()).then(data=>{
                console.log('viewType response', data);
                if(data.success){
                    const t=data.data;
                    el.innerHTML=`
                        <div class="details-grid">
                            <div class="detail-item"><strong>الاسم:</strong> ${escapeHtml(t.name)}</div>
                            <div class="detail-item"><strong>الفئة العمرية:</strong> ${escapeHtml(t.target_age)}</div>
                            <div class="detail-item" style="grid-column:1/3"><strong>الوصف:</strong> ${escapeHtml(t.description||'-')}</div>
                        </div>`;
                } else el.innerHTML='<p style="color:#ef4444;">خطأ: '+data.message+'</p>';
            }).catch(e=>{console.error(e);el.innerHTML='<p style="color:#ef4444;">حدث خطأ</p>';});
        }
        function deleteType(id){
                // استخدم SweetAlert2 عبر confirmAndSend (موجود في admin_shared.js)
                confirmAndSend({ action: 'delete_vaccine_type', id: id }, 'هل أنت متأكد من حذف هذا النوع؟', (data) => {
                    console.log('deleteType response', data);
                    showAlert('تم الحذف','success');
                    setTimeout(() => location.reload(), 1200);
                });
        }
        function submitAddType(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('addTypeForm'));
            const name=fd.get('name');const target_age=fd.get('target_age');const description=fd.get('description');
            showAlert('جاري الإضافة...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',
                body:JSON.stringify({action:'add_vaccine_type', name, target_age, description})
            }).then(r=>r.json()).then(data=>{
                console.log('submitAddType response', data);
                if(data.success){showAlert('تم الإضافة','success');closeModal('addTypeModal');setTimeout(()=>location.reload(),1500);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function submitEditType(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('editTypeForm'));
            const id=fd.get('id');const name=fd.get('name');const target_age=fd.get('target_age');const description=fd.get('description');
            showAlert('جاري الحفظ...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',
                body:JSON.stringify({action:'update_vaccine_type', id, name, target_age, description})
            }).then(r=>r.json()).then(data=>{
                console.log('submitEditType response', data);
                if(data.success){showAlert('تم الحفظ','success');closeModal('editTypeModal');setTimeout(()=>location.reload(),1200);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        document.addEventListener('click',evt=>{if(evt.target.classList.contains('modal'))evt.target.classList.remove('show');});
        document.addEventListener('DOMContentLoaded',()=>{attachSearchListener('vaccinetypes-search','vaccinetypes-table');});
    </script>
</body>
</html>
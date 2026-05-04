<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// fetch lists for dropdowns
$children = [];
$childRes = $conn->query("SELECT id, name FROM children WHERE is_archived = 0");
while ($c = $childRes->fetch_assoc()) { $children[] = $c; }

$vaccines = [];
$vacRes = $conn->query("SELECT id, name FROM vaccines");
while ($v = $vacRes->fetch_assoc()) { $vaccines[] = $v; }

$nurses = [];
$nuRes = $conn->query("SELECT id, full_name FROM users WHERE user_type = 'nurse'");
while ($n = $nuRes->fetch_assoc()) { $nurses[] = $n; }

// fetch existing vaccine records
$records = [];
$recQuery = "SELECT cv.id, cv.child_id, cv.vaccine_id, cv.due_date, cv.status, cv.administered_date, cv.nurse_id,
                c.name as child_name, v.name as vaccine_name, u.full_name as nurse_name
             FROM child_vaccines cv
             LEFT JOIN children c ON cv.child_id = c.id
             LEFT JOIN vaccines v ON cv.vaccine_id = v.id
             LEFT JOIN users u ON cv.nurse_id = u.id
             ORDER BY cv.created_at DESC";
$res = $conn->query($recQuery);
while ($row = $res->fetch_assoc()) { $records[] = $row; }

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التطعيمات - لوحة التحكم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>💉 التطعيمات</h1>
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
        <h1>إدارة التطعيمات</h1>
        <div class="search-box">
            <input type="text" class="search-input" id="vaccines-search" placeholder="ابحث عن التطعيمات...">
            <button class="btn btn-primary" onclick="openAddVaccineModal()">+ إضافة سجل مطعّم</button>
        </div>

        <div class="table-container">
            <table class="admin-table" id="vaccines-table">
                <thead>
                    <tr>
                        <th>الطفل</th>
                        <th>التطعيم</th>
                        <th>تاريخ الاستحقاق</th>
                        <th>الحالة</th>
                        <th>الممرضة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $rec): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rec['child_name'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($rec['vaccine_name'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($rec['due_date']); ?></td>
                        <td><?php
                            $status = $rec['status'];
                            $statusLabels = [
                                'due' => 'مستحقة',
                                'administered' => 'منفذة',
                                'overdue' => 'متأخرة'
                            ];
                            $statusText = $statusLabels[$status] ?? $status;
                            $cls = 'status-warning';
                            if ($status === 'administered') $cls = 'status-active';
                            if ($status === 'overdue') $cls = 'status-danger';
                            echo "<span class='status-badge $cls'>" . htmlspecialchars($statusText) . "</span>";
                        ?></td>
                        <td><?php echo htmlspecialchars($rec['nurse_name'] ?: '-'); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-secondary" onclick="viewVaccine(<?php echo $rec['id']; ?>)">📋 عرض</button>
                                <button class="btn btn-small btn-secondary" onclick="openEditVaccineModal(<?php echo $rec['id']; ?>)">✏️ تعديل</button>
                                <button class="btn btn-small btn-danger btn-delete" data-action="delete_vaccine_record" data-id="<?php echo $rec['id']; ?>">🗑️ حذف</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modals -->
    <div id="addVaccineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>إضافة سجل تطعيم</h2>
                <button class="close" onclick="closeModal('addVaccineModal')">&times;</button>
            </div>
            <form id="addVaccineForm" onsubmit="submitAddVaccine(event)">
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
                    <label>التطعيم *</label>
                    <select name="vaccine_id" required>
                        <option value="">-- اختر --</option>
                        <?php foreach ($vaccines as $v): ?>
                            <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>تاريخ الاستحقاق *</label>
                    <input type="date" name="due_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>الممرضة</label>
                    <select name="nurse_id">
                        <option value="">-- اختر --</option>
                        <?php foreach ($nurses as $n): ?>
                            <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addVaccineModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editVaccineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>تعديل سجل التطعيم</h2>
                <button class="close" onclick="closeModal('editVaccineModal')">&times;</button>
            </div>
            <form id="editVaccineForm" onsubmit="submitEditVaccine(event)">
                <input type="hidden" name="id" id="edit_vaccine_id">
                <div class="form-group">
                    <label>الحالة *</label>
                    <select name="status" id="edit_status" required>
                        <option value="due">مستحقة</option>
                        <option value="administered">منفذة</option>
                        <option value="overdue">متأخرة</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>تاريخ التنفيذ</label>
                    <input type="date" name="administered_date" id="edit_administered_date">
                </div>
                <div class="form-group">
                    <label>الممرضة</label>
                    <select name="nurse_id" id="edit_nurse_id">
                        <option value="">-- اختر --</option>
                        <?php foreach ($nurses as $n): ?>
                            <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editVaccineModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewVaccineModal" class="modal" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="max-width:600px;">
            <div class="modal-header">
                <h2>تفاصيل السجل</h2>
                <button class="close" onclick="closeModal('viewVaccineModal')">&times;</button>
            </div>
            <div id="vaccineDetailsContent"><p style="text-align:center;color:#999;">جاري التحميل...</p></div>
        </div>
    </div>
    <script src="admin_shared.js"></script>
    <script>
        function openAddVaccineModal(){document.getElementById('addVaccineForm').reset();openModal('addVaccineModal');}
        function openEditVaccineModal(id){openModal('editVaccineModal');loadVaccineForEdit(id);}
        function loadVaccineForEdit(id){
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'get_vaccine_record_details', id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    const r=data.data;
                    document.getElementById('edit_vaccine_id').value=r.id;
                    document.getElementById('edit_status').value=r.status;
                    document.getElementById('edit_administered_date').value=r.administered_date||'';
                    document.getElementById('edit_nurse_id').value=r.nurse_id||'';
                } else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function viewVaccine(id){
            openModal('viewVaccineModal');
            const el=document.getElementById('vaccineDetailsContent');
            el.innerHTML='<p style="text-align:center;color:#999;">جاري التحميل...</p>';
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'get_vaccine_record_details', id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    const r=data.data;
                    el.innerHTML=`
                        <div class="details-grid">
                            <div class="detail-item"><strong>الطفل:</strong> ${escapeHtml(r.child_name||'-')}</div>
                            <div class="detail-item"><strong>التطعيم:</strong> ${escapeHtml(r.vaccine_name||'-')}</div>
                            <div class="detail-item"><strong>تاريخ الاستحقاق:</strong> ${r.due_date}</div>
                            <div class="detail-item"><strong>الحالة:</strong> ${escapeHtml(r.status)}</div>
                            <div class="detail-item"><strong>تاريخ التنفيذ:</strong> ${r.administered_date||'-'}</div>
                            <div class="detail-item"><strong>الممرضة:</strong> ${escapeHtml(r.nurse_name||'-')}</div>
                        </div>`;
                } else el.innerHTML='<p style="color:#ef4444;">خطأ: '+data.message+'</p>';
            }).catch(e=>{console.error(e);el.innerHTML='<p style="color:#ef4444;">حدث خطأ</p>';});
        }
        function deleteVaccine(id){
            if(!confirm('هل أنت متأكد من حذف السجل؟')) return;
            showAlert('جاري الحذف...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'delete_vaccine_record', id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الحذف','success');setTimeout(()=>location.reload(),1200);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function submitAddVaccine(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('addVaccineForm'));
            const child_id=fd.get('child_id');const vaccine_id=fd.get('vaccine_id');
            const due_date=fd.get('due_date');const nurse_id=fd.get('nurse_id')||null;
            showAlert('جاري الإضافة...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'add_vaccine_record', child_id, vaccine_id, due_date, nurse_id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الإضافة','success');closeModal('addVaccineModal');setTimeout(()=>location.reload(),1500);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function submitEditVaccine(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('editVaccineForm'));
            const id=fd.get('id');const status=fd.get('status');
            const administered_date=fd.get('administered_date');const nurse_id=fd.get('nurse_id')||null;
            showAlert('جاري الحفظ...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'update_vaccine_record', id, status, administered_date, nurse_id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الحفظ','success');closeModal('editVaccineModal');setTimeout(()=>location.reload(),1200);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function openModal(id){document.getElementById(id).classList.add('show');}
        function closeModal(id){document.getElementById(id).classList.remove('show');}
        document.addEventListener('click',evt=>{if(evt.target.classList.contains('modal'))evt.target.classList.remove('show');});
        document.addEventListener('DOMContentLoaded', () => {
            attachSearchListener('vaccines-search', 'vaccines-table');
        });
    </script>
</body>
</html>

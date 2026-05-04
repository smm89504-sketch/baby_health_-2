<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$parentsRes = $conn->query("SELECT id, full_name FROM users WHERE user_type='parent'");
$parents = [];
while ($p = $parentsRes->fetch_assoc()) {
    $parents[] = $p;
}

$status = $_GET['status'] ?? 'active';
$validStatuses = ['active', 'archived', 'all'];
if (!in_array($status, $validStatuses, true)) {
    $status = 'active';
}

$whereClauses = [];
if ($status === 'active') {
    $whereClauses[] = 'c.is_archived = 0';
} elseif ($status === 'archived') {
    $whereClauses[] = 'c.is_archived = 1';
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

$query = "SELECT c.id, c.name, c.birth_date, c.weight, c.height, c.user_id, c.is_archived, u.full_name as parent_name, cv.certificate_filename
          FROM children c
          LEFT JOIN users u ON c.user_id = u.id
          LEFT JOIN (
              SELECT cv1.child_id, cv1.certificate_filename
              FROM child_vaccines cv1
              INNER JOIN (
                  SELECT child_id, MAX(created_at) AS max_created
                  FROM child_vaccines
                  WHERE certificate_filename IS NOT NULL AND certificate_filename != ''
                  GROUP BY child_id
              ) cv2 ON cv1.child_id = cv2.child_id AND cv1.created_at = cv2.max_created
              WHERE cv1.certificate_filename IS NOT NULL AND cv1.certificate_filename != ''
          ) cv ON cv.child_id = c.id
          $whereSql
          ORDER BY c.created_at DESC";
$result = $conn->query($query);
$children = [];
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأطفال - لوحة التحكم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>👶 الأطفال</h1>
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
        <h1>إدارة بيانات الأطفال</h1>
        
        <div class="search-box" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
            <div style="flex: 1; min-width: 220px; display: flex; gap: 8px; align-items: center;">
                <input type="text" class="search-input" id="children-search" placeholder="ابحث عن الأطفال..."></input>
                <div class="btn-group" role="group" aria-label="حالة الأرشفة">
                    <button type="button" class="btn <?= $status === 'active' ? 'btn-secondary' : 'btn-outline-secondary' ?>" onclick="onStatusChange('active')">غير مؤرشفين</button>
                    <button type="button" class="btn <?= $status === 'archived' ? 'btn-secondary' : 'btn-outline-secondary' ?>" onclick="onStatusChange('archived')">المؤرشفين</button>
                    <button type="button" class="btn <?= $status === 'all' ? 'btn-secondary' : 'btn-outline-secondary' ?>" onclick="onStatusChange('all')">الكل</button>
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button class="btn btn-primary" onclick="openAddChildModal()">+ إضافة طفل</button>
                <button class="btn btn-secondary" onclick="exportTable('children')">⤓ تصدير CSV</button>
            </div>
        </div>

        <div class="table-container">
            <table class="admin-table" id="children-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الأب/الأم</th>
                        <th>تاريخ الميلاد</th>
                        <th>الوزن</th>
                        <th>الطول</th>
                        <th>الحالة</th>
                        <th>الشهادة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($children as $child): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($child['name']); ?></td>
                        <td><?php echo htmlspecialchars($child['parent_name'] ?? '-'); ?></td>
                        <td><?php echo $child['birth_date']; ?></td>
                        <td><?php echo $child['weight']; ?> كغ</td>
                        <td><?php echo $child['height']; ?> سم</td>
                        <td><?php echo $child['is_archived'] ? '<span style="color:#c00; font-weight:600;">مؤرشف</span>' : '<span style="color:#0a6; font-weight:600;">نشط</span>'; ?></td>
                        <td>
                            <?php if (!empty($child['certificate_filename'])): ?>
                                <a href="uploads/vaccine_certs/<?php echo urlencode($child['certificate_filename']); ?>" target="_blank" class="btn btn-small btn-outline-success">📄 شهادة</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-secondary" onclick="viewChildDetails(<?php echo $child['id']; ?>)">📋 عرض</button>
                                <button class="btn btn-small btn-secondary" onclick="openEditChildModal(<?php echo $child['id']; ?>)">✏️ تعديل</button>
                                <?php if ($child['is_archived']): ?>
                                    <button class="btn btn-small btn-warning btn-unarchive" data-action="unarchive_child" data-id="<?php echo $child['id']; ?>" data-label="<?php echo htmlspecialchars($child['name']); ?>">♻️ إلغاء الأرشفة</button>
                                <?php else: ?>
                                    <button class="btn btn-small btn-warning btn-archive" data-action="archive_child" data-id="<?php echo $child['id']; ?>" data-label="<?php echo htmlspecialchars($child['name']); ?>">🗄️ أرشفة</button>
                                <?php endif; ?>
                                <button class="btn btn-small btn-danger btn-delete" data-action="delete_child" data-param="child_id" data-id="<?php echo $child['id']; ?>" data-label="<?php echo htmlspecialchars($child['name']); ?>">🗑️ حذف</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Child Modal -->
    <div id="addChildModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>إضافة طفل جديد</h2>
                <button class="close" onclick="closeModal('addChildModal')">&times;</button>
            </div>
            <form id="addChildForm" onsubmit="submitAddChild(event)">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" name="name" required placeholder="أدخل اسم الطفل">
                </div>
                <div class="form-group">
                    <label>الأب/الأم *</label>
                    <select name="user_id" required>
                        <option value="">-- اختر --</option>
                        <?php foreach ($parents as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>تاريخ الميلاد *</label>
                    <input type="date" name="birth_date" required>
                </div>
                <div class="form-group">
                    <label>الوزن (كغ)</label>
                    <input type="number" step="0.1" name="weight">
                </div>
                <div class="form-group">
                    <label>الطول (سم)</label>
                    <input type="number" step="0.1" name="height">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addChildModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Child Modal -->
    <div id="editChildModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>تعديل بيانات الطفل</h2>
                <button class="close" onclick="closeModal('editChildModal')">&times;</button>
            </div>
            <form id="editChildForm" onsubmit="submitEditChild(event)">
                <input type="hidden" name="child_id" id="edit_child_id">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>الأب/الأم *</label>
                    <select name="user_id" id="edit_user_id" required>
                        <option value="">-- اختر --</option>
                        <?php foreach ($parents as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>تاريخ الميلاد *</label>
                    <input type="date" name="birth_date" id="edit_birth_date" required>
                </div>
                <div class="form-group">
                    <label>الوزن (كغ)</label>
                    <input type="number" step="0.1" name="weight" id="edit_weight">
                </div>
                <div class="form-group">
                    <label>الطول (سم)</label>
                    <input type="number" step="0.1" name="height" id="edit_height">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editChildModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Child Modal -->
    <div id="viewChildModal" class="modal" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>تفاصيل الطفل</h2>
                <button class="close" onclick="closeModal('viewChildModal')">&times;</button>
            </div>
            <div id="childDetailsContent"><p style="text-align:center;color:#999;">جاري التحميل...</p></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="admin_shared.js"></script>
    <script>
        let growthChartInstance = null;

        function openAddChildModal() {
            document.getElementById('addChildForm').reset();
            openModal('addChildModal');
        }
        function openEditChildModal(id) {
            openModal('editChildModal');
            loadChildForEdit(id);
        }
        function loadChildForEdit(id) {
            fetch('../admin_handler.php',{
                method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'get_child_details', child_id:id})
            })
            .then(r=>r.json()).then(data=>{
                if(data.success){
                    const c=data.data;
                    document.getElementById('edit_child_id').value=c.id;
                    document.getElementById('edit_name').value=c.name;
                    document.getElementById('edit_user_id').value=c.user_id;
                    document.getElementById('edit_birth_date').value=c.birth_date;
                    document.getElementById('edit_weight').value=c.weight;
                    document.getElementById('edit_height').value=c.height;
                }else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function viewChildDetails(id){
            openModal('viewChildModal');
            const el=document.getElementById('childDetailsContent');
            el.innerHTML='<p style="text-align:center;color:#999;">جاري التحميل...</p>';
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'get_child_details', child_id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    const c=data.data;
                    let html = `
                        <div class="details-grid">
                            <div class="detail-item"><strong>الاسم:</strong> ${escapeHtml(c.name)}</div>
                            <div class="detail-item"><strong>العمر:</strong> ${c.age || '-'} </div>
                            <div class="detail-item"><strong>الأب/الأم:</strong> ${escapeHtml(c.parent_name||'-')}</div>
                            <div class="detail-item"><strong>البريد الإلكتروني للأب/الأم:</strong> ${escapeHtml(c.parent_email||'-')}</div>
                            <div class="detail-item"><strong>الهاتف للأب/الأم:</strong> ${escapeHtml(c.parent_phone||'-')}</div>
                            <div class="detail-item"><strong>تاريخ الميلاد:</strong> ${c.birth_date}</div>
                            <div class="detail-item"><strong>الوزن:</strong> ${c.weight||'-'} كغ</div>
                            <div class="detail-item"><strong>الطول:</strong> ${c.height||'-'} سم</div>
                        </div>`;

                    // growth chart
                    if (c.growth_records && c.growth_records.length) {
                        html += `<h4 style="margin-top:20px;">مخطط النمو</h4>
                                 <div class="chart-container">
                                     <canvas id="growthChart" width="400" height="250"></canvas>
                                 </div>`;
                    }

                    // vaccines
                    if (c.vaccines && c.vaccines.length) {
                        html += `<h4 style="margin-top:20px;">سجل التطعيمات</h4>
                                 <table class="admin-table" style="width:100%; font-size:90%;">
                                   <thead><tr><th>التطعيم</th><th>الحالة</th><th>تاريخ</th><th>بواسطة</th></tr></thead><tbody>`;
                        c.vaccines.forEach(v => {
                            const date = v.administered_date || v.due_date;
                            html += `<tr><td>${escapeHtml(v.vaccine_name)}</td><td>${escapeHtml(v.status)}</td><td>${date}</td><td>${escapeHtml(v.nurse_name||'-')}</td></tr>`;
                        });
                        html += `</tbody></table>`;
                    }
                    // activities
                    if (c.activities && c.activities.length) {
                        html += `<h4 style="margin-top:20px;">آخر الأنشطة</h4>
                                 <ul style="font-size:90%;">`;
                        c.activities.forEach(a => {
                            html += `<li>${a.date} - ${escapeHtml(a.activity_type)}${a.details?': '+escapeHtml(a.details):''}</li>`;
                        });
                        html += `</ul>`;
                    }
                    // notes
                    if (c.notes && c.notes.length) {
                        html += `<h4 style="margin-top:20px;">الملاحظات المهنية</h4>
                                 <ul>`;
                        c.notes.forEach(n => {
                            html += `<li><strong>${escapeHtml(n.author_name)} (${escapeHtml(n.user_type)})</strong>: ${escapeHtml(n.note_content)} <small>(${n.created_at})</small></li>`;
                        });
                        html += `</ul>`;
                    }
                    el.innerHTML = html;

                    // رسم مخطط النمو إذا كانت البيانات متاحة
                    if (c.growth_records && c.growth_records.length) {
                        const ctx = document.getElementById('growthChart');
                        if (ctx) {
                            const labels = c.growth_records.map(r => r.date);
                            const weights = c.growth_records.map(r => parseFloat(r.weight) || 0);
                            const heights = c.growth_records.map(r => parseFloat(r.height) || 0);

                            if (growthChartInstance) {
                                growthChartInstance.destroy();
                            }

                            growthChartInstance = new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels,
                                    datasets: [
                                        {
                                            label: 'الوزن (كغ)',
                                            data: weights,
                                            borderColor: '#C7346F',
                                            backgroundColor: 'rgba(199, 52, 111, 0.2)',
                                            tension: 0.25,
                                            fill: true
                                        },
                                        {
                                            label: 'الطول (سم)',
                                            data: heights,
                                            borderColor: '#3B82F6',
                                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                                            tension: 0.25,
                                            fill: true
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: {
                                            position: 'top'
                                        }
                                    },
                                    scales: {
                                        x: {
                                            title: { display: true, text: 'التاريخ' }
                                        },
                                        y: {
                                            title: { display: true, text: 'القياس' }
                                        }
                                    }
                                }
                            });
                        }
                    }

                    // stats
                    fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                        body:JSON.stringify({action:'get_child_stats', child_id:id})
                    }).then(r=>r.json()).then(s=>{
                        if(s.success){
                            const statline = `<p style="margin-top:10px;"><strong>تطعيمات:</strong> ${s.vaccine_count} &nbsp; <strong>أنشطة:</strong> ${s.activity_count} &nbsp; <strong>ملاحظات:</strong> ${s.note_count}</p>`;
                            el.insertAdjacentHTML('beforeend', statline);
                        }
                    });
                } else el.innerHTML='<p style="color:#ef4444;">خطأ: '+data.message+'</p>';
            }).catch(e=>{console.error(e);el.innerHTML='<p style="color:#ef4444;">حدث خطأ</p>';});
        }
        function deleteChild(id,name){
            if(!confirm('هل أنت متأكد من حذف '+name+'؟'))return;
            showAlert('جاري الحذف...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'delete_child', child_id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الحذف','success');setTimeout(()=>location.reload(),1200);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function submitAddChild(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('addChildForm'));
            const name=fd.get('name');const user_id=fd.get('user_id');const birth_date=fd.get('birth_date');
            const weight=fd.get('weight');const height=fd.get('height');
            showAlert('جاري الإضافة...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'add_child', name,user_id,birth_date,weight,height})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم إضافة الطفل','success');closeModal('addChildModal');setTimeout(()=>location.reload(),1500);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function submitEditChild(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('editChildForm'));
            const child_id=fd.get('child_id');const name=fd.get('name');const user_id=fd.get('user_id');
            const birth_date=fd.get('birth_date');const weight=fd.get('weight');const height=fd.get('height');
            showAlert('جاري الحفظ...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'update_child', child_id,name,user_id,birth_date,weight,height})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الحفظ','success');closeModal('editChildModal');setTimeout(()=>location.reload(),1200);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        // modal helpers & search
        function openModal(id){document.getElementById(id).classList.add('show');}
        function closeModal(id){document.getElementById(id).classList.remove('show');}
        document.addEventListener('click',evt=>{if(evt.target.classList.contains('modal'))evt.target.classList.remove('show');});
        document.addEventListener('DOMContentLoaded',()=>{attachSearchListener('children-search','children-table');});

        function onStatusChange(status) {
            const url = new URL(window.location);
            url.searchParams.set('status', status);
            window.location = url.toString();
        }

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

<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// fetch symptoms for initial render
$query = "SELECT id, symptom_name, description, age_range_min_months, age_range_max_months, severity_level, home_remedies, when_to_see_doctor, created_at FROM common_symptoms ORDER BY created_at DESC";
$res = $conn->query($query);
$symptoms = [];
while ($row = $res->fetch_assoc()) { $symptoms[] = $row; }
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأعراض الشائعة - لوحة التحكم</title>
    <link rel="stylesheet" href="admin_shared.css">
    <style>.details-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.detail-item{padding:6px 0}</style>
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>⚕️ الأعراض الشائعة</h1>
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
        <h1>إدارة الأعراض الشائعة</h1>
        <div class="search-box">
            <input type="text" class="search-input" id="symptoms-search" placeholder="ابحث عن عرض...">
            <button class="btn btn-primary" onclick="openAddSymptomModal()">+ إضافة عرض جديد</button>
        </div>

        <div class="table-container">
            <table class="admin-table" id="symptoms-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الفئة العمرية</th>
                        <th>الحدة</th>
                        <th>تاريخ الإضافة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($symptoms as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['symptom_name']); ?></td>
                        <td><?php echo intval($s['age_range_min_months']).' - '.intval($s['age_range_max_months']); ?> شهر</td>
                        <td><?php echo htmlspecialchars($s['severity_level']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($s['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-secondary" onclick="viewSymptom(<?php echo $s['id']; ?>)">📋 عرض</button>
                                <button class="btn btn-small btn-secondary" onclick="openEditSymptomModal(<?php echo $s['id']; ?>)">✏️ تعديل</button>
                                <button class="btn btn-small btn-danger btn-delete" data-action="delete_common_symptom" data-param="id" data-id="<?php echo $s['id']; ?>" data-label="<?php echo htmlspecialchars($s['symptom_name']); ?>">🗑️ حذف</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- modals -->
    <div id="addSymptomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>إضافة عرض شائع</h2>
                <button class="close" onclick="closeModal('addSymptomModal')">&times;</button>
            </div>
            <form id="addSymptomForm" onsubmit="submitAddSymptom(event)">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" name="symptom_name" required>
                </div>
                <div class="form-group">
                    <label>الوصف</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="form-group">
                    <label>الفئة العمرية (أدنى - أقصى بالشهور)</label>
                    <input type="number" name="age_range_min_months" placeholder="0"> - <input type="number" name="age_range_max_months" placeholder="24">
                </div>
                <div class="form-group">
                    <label>الحدة</label>
                    <select name="severity_level">
                        <option value="mild">خفيف</option>
                        <option value="moderate">متوسط</option>
                        <option value="severe">شديد</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>العلاجات المنزلية</label>
                    <textarea name="home_remedies"></textarea>
                </div>
                <div class="form-group">
                    <label>متى ترى الطبيب</label>
                    <textarea name="when_to_see_doctor"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addSymptomModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editSymptomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>تعديل العرض</h2>
                <button class="close" onclick="closeModal('editSymptomModal')">&times;</button>
            </div>
            <form id="editSymptomForm" onsubmit="submitEditSymptom(event)">
                <input type="hidden" name="id" id="edit_symptom_id">
                <div class="form-group">
                    <label>الاسم *</label>
                    <input type="text" name="symptom_name" id="edit_symptom_name" required>
                </div>
                <div class="form-group">
                    <label>الوصف</label>
                    <textarea name="description" id="edit_description"></textarea>
                </div>
                <div class="form-group">
                    <label>الفئة العمرية (أدنى - أقصى بالشهور)</label>
                    <input type="number" name="age_range_min_months" id="edit_age_min"> - <input type="number" name="age_range_max_months" id="edit_age_max">
                </div>
                <div class="form-group">
                    <label>الحدة</label>
                    <select name="severity_level" id="edit_severity">
                        <option value="mild">خفيف</option>
                        <option value="moderate">متوسط</option>
                        <option value="severe">شديد</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>العلاجات المنزلية</label>
                    <textarea name="home_remedies" id="edit_remedies"></textarea>
                </div>
                <div class="form-group">
                    <label>متى ترى الطبيب</label>
                    <textarea name="when_to_see_doctor" id="edit_when"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editSymptomModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewSymptomModal" class="modal" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="max-width:600px;">
            <div class="modal-header">
                <h2>تفاصيل العرض</h2>
                <button class="close" onclick="closeModal('viewSymptomModal')">&times;</button>
            </div>
            <div id="symptomDetailsContent"><p style="text-align:center;color:#999;">جاري التحميل...</p></div>
        </div>
    </div>

    <script src="admin_shared.js"></script>
    <script>
        function openAddSymptomModal(){document.getElementById('addSymptomForm').reset();openModal('addSymptomModal');}
        function openEditSymptomModal(id){openModal('editSymptomModal');loadSymptomForEdit(id);}
        function loadSymptomForEdit(id){
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',
                body:JSON.stringify({action:'get_common_symptom_details', id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    const s=data.data;
                    document.getElementById('edit_symptom_id').value=s.id;
                    document.getElementById('edit_symptom_name').value=s.symptom_name;
                    document.getElementById('edit_description').value=s.description;
                    document.getElementById('edit_age_min').value=s.age_range_min_months;
                    document.getElementById('edit_age_max').value=s.age_range_max_months;
                    document.getElementById('edit_severity').value=s.severity_level;
                    document.getElementById('edit_remedies').value=s.home_remedies;
                    document.getElementById('edit_when').value=s.when_to_see_doctor;
                } else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function viewSymptom(id){
            openModal('viewSymptomModal');
            const el=document.getElementById('symptomDetailsContent');
            el.innerHTML='<p style="text-align:center;color:#999;">جاري التحميل...</p>';
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',
                body:JSON.stringify({action:'get_common_symptom_details', id:id})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    const s=data.data;
                    el.innerHTML=`
                        <div class="details-grid">
                            <div class="detail-item"><strong>الاسم:</strong> ${escapeHtml(s.symptom_name)}</div>
                            <div class="detail-item"><strong>الوصف:</strong> ${escapeHtml(s.description||'-')}</div>
                            <div class="detail-item"><strong>الفئة العمرية:</strong> ${s.age_range_min_months} - ${s.age_range_max_months} ش</div>
                            <div class="detail-item"><strong>الحدة:</strong> ${escapeHtml(s.severity_level)}</div>
                            <div class="detail-item" style="grid-column:1/3"><strong>العلاجات المنزلية:</strong> ${escapeHtml(s.home_remedies||'-')}</div>
                            <div class="detail-item" style="grid-column:1/3"><strong>متى ترى الطبيب:</strong> ${escapeHtml(s.when_to_see_doctor||'-')}</div>
                        </div>`;
                } else el.innerHTML='<p style="color:#ef4444;">خطأ: '+data.message+'</p>';
            }).catch(e=>{console.error(e);el.innerHTML='<p style="color:#ef4444;">حدث خطأ</p>';});
        }
        function submitAddSymptom(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('addSymptomForm'));
            const symptom_name=fd.get('symptom_name');
            const description=fd.get('description');
            const age_range_min_months=fd.get('age_range_min_months');
            const age_range_max_months=fd.get('age_range_max_months');
            const severity_level=fd.get('severity_level');
            const home_remedies=fd.get('home_remedies');
            const when_to_see_doctor=fd.get('when_to_see_doctor');
            showAlert('جاري الإضافة...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',
                body:JSON.stringify({action:'add_common_symptom', symptom_name, description, age_range_min_months, age_range_max_months, severity_level, home_remedies, when_to_see_doctor})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الإضافة','success');closeModal('addSymptomModal');setTimeout(()=>location.reload(),1500);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function submitEditSymptom(e){
            e.preventDefault();
            const fd=new FormData(document.getElementById('editSymptomForm'));
            const id=fd.get('id');
            const symptom_name=fd.get('symptom_name');
            const description=fd.get('description');
            const age_range_min_months=fd.get('age_range_min_months');
            const age_range_max_months=fd.get('age_range_max_months');
            const severity_level=fd.get('severity_level');
            const home_remedies=fd.get('home_remedies');
            const when_to_see_doctor=fd.get('when_to_see_doctor');
            showAlert('جاري الحفظ...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',
                body:JSON.stringify({action:'update_common_symptom', id, symptom_name, description, age_range_min_months, age_range_max_months, severity_level, home_remedies, when_to_see_doctor})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم الحفظ','success');closeModal('editSymptomModal');setTimeout(()=>location.reload(),1200);}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        document.addEventListener('click',evt=>{if(evt.target.classList.contains('modal'))evt.target.classList.remove('show');});
        document.addEventListener('DOMContentLoaded',()=>{attachSearchListener('symptoms-search','symptoms-table');});
    </script>
</body>
</html>
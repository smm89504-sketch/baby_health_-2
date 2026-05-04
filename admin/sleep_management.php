<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// fetch children for selector
$chRes = $conn->query("SELECT id,name,birth_date FROM children ORDER BY name");
$children = [];
while ($r = $chRes->fetch_assoc()) {
    $children[] = $r;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة النوم - لوحة الأدمن</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand"><h1>😴 إدارة النوم</h1></div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A',0,1)); ?></div>
                <div><small style="color:#7a6880;">مرحباً</small><div style="color:#3d2c4d;font-weight:600;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'إدمن'); ?></div></div>
            </div>
        </div>
    </nav>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <h1>تسجيل فترات النوم</h1>
        <form id="sleepForm" style="margin-bottom:20px;">
            <div class="form-row">
                <div class="form-group">
                    <label>الطفل</label>
                    <select name="child_id" required>
                        <option value="">-- اختر --</option>
                        <?php foreach($children as $c): ?>
                            <option value="<?php echo $c['id']; ?>" data-birth="<?php echo $c['birth_date']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>بداية</label>
                    <input type="datetime-local" name="start_datetime" required>
                </div>
                <div class="form-group">
                    <label>نهاية</label>
                    <input type="datetime-local" name="end_datetime" required>
                </div>
                <div class="form-group">
                    <label>ليلي؟</label>
                    <select name="is_night"><option value="0">لا</option><option value="1">نعم</option></select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </form>
        <div id="sleepStats" style="margin-bottom:20px;"></div>
        <canvas id="sleepChart" style="max-width:600px;margin-bottom:20px;"></canvas>
        <h2>نصائح لتحسين النوم</h2>
        <div style="margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:220px;">اختر طفلاً للحصول على نصيحة مخصصة حسب العمر.</div>
            <button class="btn btn-secondary" onclick="openManageTipsModal()">⚙️ إدارة النصائح</button>
        </div>
        <div id="sleepTipsContainer" style="min-height: 70px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background:#fff; margin-bottom:20px;">
            <em>اختر طفلاً لعرض النصائح.</em>
        </div>
        <h2>السجلات</h2>
        <table class="admin-table" id="sleepTable"><thead><tr><th>طفل</th><th>البداية</th><th>النهاية</th><th>ليلي</th><th>إجراءات</th></tr></thead><tbody></tbody></table>

        <!-- إدارة نصائح النوم -->
        <div id="manageTipsModal" class="modal">
            <div class="modal-content" style="max-width:800px;">
                <div class="modal-header">
                    <h2>إدارة نصائح النوم</h2>
                    <button class="close" onclick="closeModal('manageTipsModal')">&times;</button>
                </div>
                <div style="padding: 0 20px 20px;">
                    <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 12px;">
                        <div><strong>النصائح الحالية</strong></div>
                        <button class="btn btn-primary" onclick="openAddTipModal()">+ إضافة نصيحة</button>
                    </div>
                    <div style="max-height: 320px; overflow:auto;">
                        <table class="admin-table" id="sleepTipsTable">
                            <thead>
                                <tr><th>الفئة العمرية (شهور)</th><th>النصيحة</th><th>إجراءات</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="addTipModal" class="modal">
            <div class="modal-content" style="max-width:520px;">
                <div class="modal-header">
                    <h2>إضافة نصيحة للنوم</h2>
                    <button class="close" onclick="closeModal('addTipModal')">&times;</button>
                </div>
                <form id="addTipForm" onsubmit="submitAddTip(event)">
                    <div class="form-group">
                        <label>العمر من (شهور)</label>
                        <input type="number" name="min_age_months" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label>العمر إلى (شهور) <small>(اتركه فارغاً للتطبيق لأي عمر بعد الحد الأدنى)</small></label>
                        <input type="number" name="max_age_months" min="0" placeholder="مثلاً 11">
                    </div>
                    <div class="form-group">
                        <label>النصيحة</label>
                        <textarea name="tip_text" required rows="3"></textarea>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addTipModal')">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="editTipModal" class="modal">
            <div class="modal-content" style="max-width:520px;">
                <div class="modal-header">
                    <h2>تعديل نصيحة</h2>
                    <button class="close" onclick="closeModal('editTipModal')">&times;</button>
                </div>
                <form id="editTipForm" onsubmit="submitEditTip(event)">
                    <input type="hidden" name="id" id="edit_tip_id">
                    <div class="form-group">
                        <label>العمر من (شهور)</label>
                        <input type="number" name="min_age_months" id="edit_min_age_months" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>العمر إلى (شهور) <small>(اتركه فارغاً للتطبيق لأي عمر بعد الحد الأدنى)</small></label>
                        <input type="number" name="max_age_months" id="edit_max_age_months" min="0" placeholder="مثلاً 11">
                    </div>
                    <div class="form-group">
                        <label>النصيحة</label>
                        <textarea name="tip_text" id="edit_tip_text" required rows="3"></textarea>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editTipModal')">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="admin_shared.js"></script>
    <script>
        const sleepForm = document.getElementById('sleepForm');
        const sleepTable = document.getElementById('sleepTable').querySelector('tbody');
        const statsDiv = document.getElementById('sleepStats');
        const chartCtx = document.getElementById('sleepChart').getContext('2d');
        const sleepTipsContainer = document.getElementById('sleepTipsContainer');
        let sleepChart;
        let sleepTips = [];

        const childSelect = sleepForm.querySelector('[name="child_id"]');
        sleepForm.addEventListener('submit', e=>{
            e.preventDefault();
            const fd = new FormData(sleepForm);
            const body = {action:'add_sleep_record'};
            fd.forEach((v,k)=>body[k]=v);
            fetch(API_BASE_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
            .then(r=>r.json()).then(d=>{
                if(d.success){showAlert('تم الحفظ','success'); loadSleep(childSelect.value);} 
                else showAlert('خطأ: '+d.message,'danger');
            });
        });
        childSelect.addEventListener('change', () => {
            loadSleep(childSelect.value);
            renderTips();
        });

        function loadSleep(childId=null){
            const params={action:'get_sleep_records'};
            if(childId) params.child_id=childId;
            fetch(API_BASE_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(params)})
            .then(r=>r.json()).then(d=>{
                if(d.success){
                    sleepTable.innerHTML='';
                    const names={<?php foreach($children as $c){echo $c['id'].':"'.addslashes($c['name']).'",';}?>};
                    let totalHours=0;
                    let wakeUps=0;
                    d.data.forEach(r=>{
                        const start=new Date(r.start_datetime);
                        const end=new Date(r.end_datetime);
                        const hrs=(end-start)/36e5;
                        totalHours+=hrs;
                        if(!r.is_night) wakeUps++;
                        const tr=document.createElement('tr');
                        tr.innerHTML = `
                            <td>${names[r.child_id]||r.child_id}</td>
                            <td>${r.start_datetime.replace('T',' ')}</td>
                            <td>${r.end_datetime.replace('T',' ')}</td>
                            <td>${r.is_night? 'نعم':'لا'}</td>
                            <td><button onclick="deleteSleep(${r.id})">حذف</button></td>
                        `;
                        sleepTable.appendChild(tr);
                    });
                    statsDiv.innerHTML = `<p>إجمالي ساعات: ${totalHours.toFixed(1)}<br>استيقاظات نهار: ${wakeUps}</p>`;
                    renderSleepChart(d.data);
                    renderTips();
                }
            });
        }
        function renderSleepChart(records){
            if(sleepChart) sleepChart.destroy();
            const labels=[];
            const data=[];
            records.forEach(r=>{
                labels.push(r.start_datetime.split('T')[0]);
                const start=new Date(r.start_datetime);
                const end=new Date(r.end_datetime);
                data.push(((end-start)/36e5).toFixed(1));
            });
            sleepChart=new Chart(chartCtx,{type:'line',data:{labels, datasets:[{label:'ساعات نوم',data,backgroundColor:'rgba(56,161,105,0.2)',borderColor:'#38a169'}]},options:{responsive:true}});
        }
        function renderTips(){
            const birth = childSelect.selectedOptions[0]?.dataset.birth;
            if(!birth){
                sleepTipsContainer.innerHTML = '<em>اختر طفلاً لعرض النصائح.</em>';
                return;
            }
            const ageMonths = Math.floor((new Date() - new Date(birth)) / (1000*60*60*24*30));
            const tipObj = findTipForAge(ageMonths);
            const tipText = tipObj ? tipObj.tip_text : 'لا توجد نصائح مخصصة لهذا العمر، يمكنك إضافة نصيحة جديدة.';
            const rangeLabel = tipObj ? formatAgeRange(tipObj) : '';
            sleepTipsContainer.innerHTML = `
                <div><strong>العمر الحالي:</strong> ${ageMonths} شهر</div>
                <div style="margin-top:8px;"><strong>النصيحة${rangeLabel ? ' ('+rangeLabel+')' : ''}:</strong> ${escapeHtml(tipText)}</div>
            `;
        }

        function formatAgeRange(tip) {
            const min = Number(tip.min_age_months);
            const max = tip.max_age_months !== null ? Number(tip.max_age_months) : null;
            if (max === null) return `${min}+`;
            if (min === max) return `${min}`;
            return `${min} - ${max}`;
        }

        function findTipForAge(ageMonths) {
            if (!Array.isArray(sleepTips) || sleepTips.length === 0) return null;
            const sorted = sleepTips.slice().sort((a, b) => Number(b.min_age_months) - Number(a.min_age_months));
            return sorted.find(tip => {
                const min = Number(tip.min_age_months);
                const max = tip.max_age_months !== null ? Number(tip.max_age_months) : null;
                if (ageMonths < min) return false;
                if (max !== null && ageMonths > max) return false;
                return true;
            }) || null;
        }

        function loadSleepTips(){
            fetch(API_BASE_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'get_sleep_tips'})})
            .then(r=>r.json()).then(d=>{
                if(d.success){
                    sleepTips = d.data || [];
                    renderTipsList();
                    renderTips();
                }
            });
        }

        function renderTipsList(){
            const tbody = document.querySelector('#sleepTipsTable tbody');
            if(!tbody) return;
            tbody.innerHTML = '';
            sleepTips.forEach(tip=>{
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${escapeHtml(formatAgeRange(tip))}</td>
                    <td>${escapeHtml(tip.tip_text)}</td>
                    <td>
                        <button class="btn btn-small btn-secondary" onclick="openEditTipModal(${tip.id})">✏️</button>
                        <button class="btn btn-small btn-danger" onclick="deleteTip(${tip.id})">🗑️</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function openManageTipsModal(){
            loadSleepTips();
            openModal('manageTipsModal');
        }

        function openAddTipModal(){
            const form = document.getElementById('addTipForm');
            form.reset();
            openModal('addTipModal');
        }

        function openEditTipModal(id){
            const tip = sleepTips.find(t=>t.id === id);
            if(!tip){
                showAlert('النصيحة غير موجودة', 'danger');
                return;
            }
            document.getElementById('edit_tip_id').value = tip.id;
            document.getElementById('edit_min_age_months').value = tip.min_age_months;
            document.getElementById('edit_max_age_months').value = tip.max_age_months ?? '';
            document.getElementById('edit_tip_text').value = tip.tip_text;
            openModal('editTipModal');
        }

        function submitAddTip(e){
            e.preventDefault();
            const fd = new FormData(document.getElementById('addTipForm'));
            const body = { action: 'add_sleep_tip' };
            fd.forEach((v,k)=> body[k]=v);
            fetch(API_BASE_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
            .then(r=>r.json()).then(d=>{
                if(d.success){
                    showAlert('تم الإضافة','success');
                    closeModal('addTipModal');
                    loadSleepTips();
                } else {
                    showAlert('خطأ: '+d.message,'danger');
                }
            });
        }

        function submitEditTip(e){
            e.preventDefault();
            const fd = new FormData(document.getElementById('editTipForm'));
            const body = { action: 'update_sleep_tip' };
            fd.forEach((v,k)=> body[k]=v);
            fetch(API_BASE_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
            .then(r=>r.json()).then(d=>{
                if(d.success){
                    showAlert('تم الحفظ','success');
                    closeModal('editTipModal');
                    loadSleepTips();
                } else {
                    showAlert('خطأ: '+d.message,'danger');
                }
            });
        }

        function deleteTip(id){
            confirmAndSend({ action:'delete_sleep_tip', id }, 'هل أنت متأكد من حذف هذه النصيحة؟', () => {
                showAlert('تم الحذف','success');
                loadSleepTips();
            });
        }

        function deleteSleep(id){
            if(!confirm('هل تريد حذف هذا السجل؟')) return;
            fetch(API_BASE_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete_sleep_record',id})})
            .then(r=>r.json()).then(d=>{if(d.success){showAlert('تم الحذف','success');loadSleep();}});
        }

        // initial load
        loadSleepTips();
        loadSleep();
    </script>
</body>
</html>

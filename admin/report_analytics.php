<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/db_config.php';

// no prefetch required
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحليلات وتقارير</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand"><h1>📊 تقارير</h1></div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A',0,1)); ?></div>
                <div><small style="color:#7a6880;">مرحباً</small><div style="color:#3d2c4d;font-weight:600;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'إدمن'); ?></div></div>
            </div>
        </div>
    </nav>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <h1>تقارير النمو والصحة</h1>
        <form id="reportForm" style="margin-bottom:20px;">
            <div class="form-row">
                <div class="form-group">
                    <label>النوع</label>
                    <select name="type">
                        <option value="growth">نمو</option>
                        <option value="health">صحة</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>السنة</label>
                    <input type="number" name="year" value="<?php echo date('Y'); ?>" min="2020" max="2100" required>
                </div>
                <div class="form-group">
                    <label>الشهر</label>
                    <input type="number" name="month" value="<?php echo date('m'); ?>" min="1" max="12" required>
                </div>
                <div class="form-group">
                    <label>التنسيق</label>
                    <select name="format">
                        <option value="csv">CSV / Excel</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">عرض</button>
            <button type="button" id="exportBtn" class="btn btn-secondary">تصدير</button>
        </form>
        <div id="reportResult"></div>
    </main>
    <script src="admin_shared.js"></script>
    <script>
        const form = document.getElementById('reportForm');
        const resultDiv = document.getElementById('reportResult');
        form.addEventListener('submit', e=>{
            e.preventDefault();
            const fd=new FormData(form);
            const body={action:'get_report_data'};
            fd.forEach((v,k)=>body[k]=v);
            fetch(API_BASE_URL,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
            .then(r=>r.json()).then(d=>{
                if(d.success){
                    renderReport(d.data);
                } else showAlert('خطأ: '+d.message,'danger');
            });
        });
        
        document.getElementById('exportBtn').addEventListener('click',()=>{
            const fd=new FormData(form);
            const params={action:'export_report'};
            fd.forEach((v,k)=>params[k]=v);
            // include folder path if needed
            const url=API_BASE_URL; // points to ../admin_handler.php
            // create form to download
            const f=document.createElement('form');
            f.method='POST'; f.action=url; f.style.display='none';
            for(const k in params){
                const inp=document.createElement('input'); inp.name=k; inp.value=params[k]; f.appendChild(inp);
            }
            document.body.appendChild(f); f.submit(); document.body.removeChild(f);
        });
        
        function renderReport(data){
            if(!data.length){resultDiv.innerHTML='<p>لا توجد بيانات</p>';return;}
            let html='<table class="admin-table"><thead><tr>';
            Object.keys(data[0]).forEach(h=>html+=`<th>${h}</th>`);
            html+='</tr></thead><tbody>';
            data.forEach(row=>{
                html+='<tr>';
                Object.values(row).forEach(v=>html+=`<td>${v}</td>`);
                html+='</tr>';
            });
            html+='</tbody></table>';
            resultDiv.innerHTML=html;
        }
    </script>
</body>
</html>
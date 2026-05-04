<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات النظام - لوحة التحكم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>⚙️ إعدادات النظام</h1>
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
        <h1>إعدادات النظام</h1>
        
        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 6px 20px rgba(157, 132, 202, 0.08); margin-bottom: 30px;">
            <h2 style="margin-bottom: 20px;">معلومات النظام</h2>
            
            <div class="form-group">
                <label>اسم الموقع</label>
                <input type="text" id="sys_site_name" />
            </div>
            
            <div class="form-group">
                <label>وضع الصيانة</label>
                <select id="sys_maintenance">
                    <option value="off">معطل</option>
                    <option value="on">مفعل</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>البريد الإلكتروني للدعم</label>
                <input type="email" id="sys_support_email" />
            </div>
            
            <div class="form-group">
                <label>رقم الهاتف</label>
                <input type="tel" id="sys_phone" />
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button class="btn btn-primary" id="saveSettingsBtn">حفظ الإعدادات</button>
                <button class="btn btn-secondary" id="resetSettingsBtn">إعادة تعيين</button>
            </div>
        </div>
    </main>

    <script src="admin_shared.js"></script>
    <script>
        function loadSettings() {
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'get_system_settings'})
            }).then(r=>r.json()).then(data=>{
                if(data.success){
                    const s = data.settings || {};
                    document.getElementById('sys_site_name').value = s.site_name || '';
                    document.getElementById('sys_maintenance').value = s.maintenance || 'off';
                    document.getElementById('sys_support_email').value = s.support_email || '';
                    document.getElementById('sys_phone').value = s.phone || '';
                } else showAlert('خطأ في جلب الإعدادات','danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        function saveSettings(){
            const settings={
                site_name: document.getElementById('sys_site_name').value,
                maintenance: document.getElementById('sys_maintenance').value,
                support_email: document.getElementById('sys_support_email').value,
                phone: document.getElementById('sys_phone').value
            };
            showAlert('جاري حفظ الإعدادات...','info');
            fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({action:'update_system_settings', settings})
            }).then(r=>r.json()).then(data=>{
                if(data.success){showAlert('تم حفظ الإعدادات','success');}else showAlert('خطأ: '+data.message,'danger');
            }).catch(e=>{console.error(e);showAlert('حدث خطأ','danger');});
        }
        document.addEventListener('DOMContentLoaded',()=>{
            loadSettings();
            document.getElementById('saveSettingsBtn').addEventListener('click',saveSettings);
            document.getElementById('resetSettingsBtn').addEventListener('click',()=>{loadSettings();showAlert('تمت إعادة تعيين','info');});
        });
    </script>
</body>
</html>

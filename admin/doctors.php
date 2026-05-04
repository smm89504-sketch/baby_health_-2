<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// The doctors brought
$query = "SELECT id, full_name, email, phone, created_at FROM users WHERE user_type = 'doctor' ORDER BY created_at DESC";
$result = $conn->query($query);
$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأطباء - لوحة التحكم</title>
    <link rel="stylesheet" href="admin_shared.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-oX4+PB8Bf2+3VkT3w3oP9mA0G+8SYnG+BYlYF6D7XNk=" crossorigin="" />
    <style> .details-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.detail-item{padding:6px 0} #mapPicker{height:400px;width:100%;border-radius:10px;margin-top:8px;} </style>
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>🩺 الأطباء</h1>
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
        <h1>إدارة الأطباء</h1>
        <div class="search-box">
            <input type="text" class="search-input" id="doctors-search" placeholder="ابحث عن الأطباء...">
            <button class="btn btn-primary" onclick="openAddDoctorModal()">+ إضافة طبيب</button>
            <button class="btn btn-secondary" onclick="exportTable('doctors')">⤓ تصدير CSV</button>
        </div>

        <div class="table-container">
            <table class="admin-table" id="doctors-table">
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
                    <?php foreach ($doctors as $doctor): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doctor['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                        <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($doctor['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-secondary" onclick="viewDoctorDetails(<?php echo $doctor['id']; ?>)">📋 عرض</button>
                                <button class="btn btn-small btn-secondary" onclick="openEditDoctorModal(<?php echo $doctor['id']; ?>)">✏️ تعديل</button>
                                <button class="btn btn-small btn-danger btn-delete" data-action="delete_doctor" data-param="user_id" data-id="<?php echo $doctor['id']; ?>" data-label="<?php echo htmlspecialchars($doctor['full_name']); ?>">🗑️ حذف</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Doctor Modal -->
    <div id="addDoctorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>إضافة طبيب جديد</h2>
                <button class="close" onclick="closeModal('addDoctorModal')">&times;</button>
            </div>
            <form id="addDoctorForm" onsubmit="submitAddDoctor(event)">
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
                    <label>العنوان (العيادة)</label>
                    <input type="text" name="address" placeholder="أدخل العنوان مثلاً: شارع الملك فهد، دبي">
                </div>
                <div class="form-group" style="display: flex; gap: 10px;">
                    <input type="number" step="0.000001" name="latitude" id="add_latitude" placeholder="خط العرض">
                    <input type="number" step="0.000001" name="longitude" id="add_longitude" placeholder="خط الطول">
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="openMapPicker('add')">اختر الموقع من الخريطة</button>
                </div>
                <div class="form-group">
                    <label>كلمة المرور *</label>
                    <input type="password" name="password" required placeholder="أدخل كلمة مرور">
                    <small style="color: #999; display: block; margin-top: 5px;">يجب أن تحتوي على: 8 أحرف على الأقل، حرف كبير، حرف صغير، رقم، ورمز (!@#$%^&*).</small>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addDoctorModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div id="editDoctorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>تعديل بيانات الطبيب</h2>
                <button class="close" onclick="closeModal('editDoctorModal')">&times;</button>
            </div>
            <form id="editDoctorForm" onsubmit="submitEditDoctor(event)">
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
                    <label>العنوان (العيادة)</label>
                    <input type="text" name="address" id="edit_address" placeholder="أدخل العنوان مثلاً: شارع الملك فهد، دبي">
                </div>
                <div class="form-group" style="display: flex; gap: 10px;">
                    <input type="number" step="0.000001" name="latitude" id="edit_latitude" placeholder="خط العرض">
                    <input type="number" step="0.000001" name="longitude" id="edit_longitude" placeholder="خط الطول">
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="openMapPicker('edit')">اختر الموقع من الخريطة</button>
                </div>
                <div class="form-group">
                    <label>كلمة المرور (اتركها فارغة إذا لم تريد تغييرها)</label>
                    <input type="password" name="password" id="edit_password" placeholder="أدخل كلمة مرور جديدة (اختياري)">
                    <small style="color: #999; display: block; margin-top: 5px;">إذا أدخلت كلمة مرور جديدة، يجب أن تحتوي على: 8 أحرف على الأقل، حرف كبير، حرف صغير، رقم، ورمز (!@#$%^&*).</small>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editDoctorModal')">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewDoctorModal" class="modal" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>تفاصيل الطبيب</h2>
                <button class="close" onclick="closeModal('viewDoctorModal')">&times;</button>
            </div>
            <div id="doctorDetailsContent"><p style="text-align:center;color:#999;">جاري التحميل...</p></div>
        </div>
    </div>

    <!-- Map Picker Modal -->
    <div id="mapPickerModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2>اختر الموقع من الخريطة</h2>
                <button class="close" onclick="closeMapPicker()">&times;</button>
            </div>
            <div id="mapPicker"></div>
            <div style="text-align: center; margin-top: 10px;">
                <button type="button" class="btn btn-primary" onclick="applyMapLocation()">تثبيت الموقع</button>
                <button type="button" class="btn btn-secondary" onclick="closeMapPicker()">إلغاء</button>
            </div>
        </div>
    </div>

    <script src="admin_shared.js"></script>
    <script>
        function openAddDoctorModal() {
            document.getElementById('addDoctorForm').reset();
            openModal('addDoctorModal');
        }

        function openEditDoctorModal(userId) {
            openModal('editDoctorModal');
            loadDoctorForEdit(userId);
        }

        function loadDoctorForEdit(userId) {
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
                    document.getElementById('edit_address').value = u.address || '';
                    document.getElementById('edit_latitude').value = u.latitude || '';
                    document.getElementById('edit_longitude').value = u.longitude || '';
                } else {
                    showAlert('خطأ: ' + data.message, 'danger');
                }
            })
            .catch(e => { console.error(e); showAlert('حدث خطأ', 'danger'); });
        }

        function viewDoctorDetails(userId) {
            openModal('viewDoctorModal');
            const el = document.getElementById('doctorDetailsContent');
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
                    if (u.extra && u.extra.notes && u.extra.notes.length) {
                        html += `
                            <h4 style="margin-top:20px;">الملاحظات المهنية</h4>
                            <ul>`;
                        u.extra.notes.forEach(n => {
                            html += `<li><strong>${escapeHtml(n.child_name)}</strong> &ndash; ${escapeHtml(n.note_content)} <small>(${n.created_at})</small></li>`;
                        });
                        html += `</ul>`;
                    }
                    el.innerHTML = html;
                    fetch('../admin_handler.php',{method:'POST',headers:{'Content-Type':'application/json'},
                        body:JSON.stringify({action:'get_user_stats',user_id:userId})
                    }).then(r=>r.json()).then(s=>{
                        if(s.success){
                            const statsHtml = `<p style="margin-top:10px;"><strong>عدد الملاحظات:</strong> ${s.note_count}</p>`;
                            el.insertAdjacentHTML('beforeend', statsHtml);
                        }
                    });
                } else {
                    el.innerHTML = '<p style="color:#ef4444;">خطأ: ' + data.message + '</p>';
                }
            })
            .catch(e => { console.error(e); el.innerHTML = '<p style="color:#ef4444;">حدث خطأ</p>'; });
        }

        // حذف يتم عبر confirmAndSend من admin_shared.js

        function submitAddDoctor(e) {
            e.preventDefault();
            const fd = new FormData(document.getElementById('addDoctorForm'));
            const full_name = fd.get('full_name');
            const email = fd.get('email');
            const phone = fd.get('phone');
            const address = fd.get('address');
            const latitude = fd.get('latitude');
            const longitude = fd.get('longitude');
            const password = fd.get('password');
            const strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/;
            if (!strong.test(password)) {
                showAlert('كلمة السر ضعيفة. يجب استخدامها حسب الشروط.', 'danger');
                return;
            }
            showAlert('جاري الإضافة...', 'info');
            fetch('../admin_handler.php', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({action: 'add_doctor', full_name, email, phone, address, latitude, longitude, password})
            })
            .then(r=>r.json())
            .then(data => {
                if (data.success) {
                    showAlert('تم إضافة الطبيب.', 'success');
                    closeModal('addDoctorModal');
                    setTimeout(()=>location.reload(),1500);
                } else showAlert('خطأ: '+data.message,'danger');
            })
            .catch(e=>{console.error(e); showAlert('حدث خطأ','danger');});
        }

        function submitEditDoctor(e) {
            e.preventDefault();
            const fd = new FormData(document.getElementById('editDoctorForm'));
            const user_id = fd.get('user_id');
            const full_name = fd.get('full_name');
            const email = fd.get('email');
            const phone = fd.get('phone');
            const address = fd.get('address');
            const latitude = fd.get('latitude');
            const longitude = fd.get('longitude');
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
                body: JSON.stringify({action: 'update_doctor', user_id, full_name, email, phone, address, latitude, longitude, password: password || null})
            })
            .then(r=>r.json())
            .then(data => {
                if (data.success) { showAlert('تم الحفظ','success'); closeModal('editDoctorModal'); setTimeout(()=>location.reload(),1200); }
                else showAlert('خطأ: '+data.message,'danger');
            })
            .catch(e=>{console.error(e); showAlert('حدث خطأ','danger');});
        }

        // modal helpers
        function openModal(id){ document.getElementById(id).classList.add('show'); }
        function closeModal(id){ document.getElementById(id).classList.remove('show'); }
        document.addEventListener('click', function(ev){ if (ev.target.classList.contains('modal')) ev.target.classList.remove('show'); });

        document.addEventListener('DOMContentLoaded', ()=>{ attachSearchListener('doctors-search','doctors-table'); });

        let mapPicker, mapPickerMarker, mapPickerTarget;

        function openMapPicker(target) {
            mapPickerTarget = target; // 'add' أو 'edit'
            openModal('mapPickerModal');

            setTimeout(() => {
                if (!mapPicker) {
                    mapPicker = L.map('mapPicker').setView([24.7136, 46.6753], 6); // default مكة
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap'
                    }).addTo(mapPicker);

                    mapPicker.on('click', function(e) {
                        const latlng = e.latlng;
                        if (mapPickerMarker) mapPicker.removeLayer(mapPickerMarker);
                        mapPickerMarker = L.marker(latlng).addTo(mapPicker);
                        mapPickerMarker.bindPopup('الموقع المحدد').openPopup();
                        document.querySelector('#mapPickerModal').dataset.lat = latlng.lat.toFixed(6);
                        document.querySelector('#mapPickerModal').dataset.lng = latlng.lng.toFixed(6);
                    });
                }

                mapPicker.invalidateSize();
                const currentLat = parseFloat(document.getElementById(mapPickerTarget === 'add' ? 'add_latitude' : 'edit_latitude')?.value || 24.7136);
                const currentLng = parseFloat(document.getElementById(mapPickerTarget === 'add' ? 'add_longitude' : 'edit_longitude')?.value || 46.6753);
                mapPicker.setView([currentLat, currentLng], 12);
                if (mapPickerMarker) mapPicker.removeLayer(mapPickerMarker);
                mapPickerMarker = L.marker([currentLat, currentLng]).addTo(mapPicker);
                mapPickerMarker.bindPopup('الموقع الحالي').openPopup();
                document.querySelector('#mapPickerModal').dataset.lat = currentLat;
                document.querySelector('#mapPickerModal').dataset.lng = currentLng;
            }, 200);
        }

        function closeMapPicker() {
            closeModal('mapPickerModal');
        }

        function applyMapLocation() {
            const modal = document.querySelector('#mapPickerModal');
            const lat = modal.dataset.lat;
            const lng = modal.dataset.lng;
            if (!lat || !lng) {
                alert('يرجى اختيار الموقع في الخريطة أولاً.');
                return;
            }
            if (mapPickerTarget === 'add') {
                document.querySelector('input[name="latitude"]').value = parseFloat(lat).toFixed(6);
                document.querySelector('input[name="longitude"]').value = parseFloat(lng).toFixed(6);
            } else {
                document.querySelector('#edit_latitude').value = parseFloat(lat).toFixed(6);
                document.querySelector('#edit_longitude').value = parseFloat(lng).toFixed(6);
            }
            closeMapPicker();
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-o9N1j7k1SAa2aEA/mVrbzOlonekH0EcN5sNnPsm2w4Y=" crossorigin=""></script>
</body>
</html>

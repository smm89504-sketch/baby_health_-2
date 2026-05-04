<?php
$current_page = basename($_SERVER['PHP_SELF']);

function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>

<style>
    :root {
        --doctor-bg: #ffeef3;
        --doctor-dark: #842029;
        --doctor-red: #dc3545;
        --doctor-light: #f8d7da;
        --doctor-border: rgba(220,53,69,0.12);
    }
    html, body {
        min-height: 100%;
        margin: 0;
        padding: 0;
        font-family: 'Cairo', sans-serif;
        background: linear-gradient(135deg, var(--doctor-light) 0%, var(--doctor-bg) 100%);
        color: #333;
    }
    .main-content {
        min-height: 100vh;
        margin-right: 290px;
        padding: 100px 32px 32px;
        transition: margin 0.2s ease;
    }
    .dashboard-container {
        max-width: 1180px;
        margin: 0 auto;
        padding-left: 0;
    }
    .main-box {
        background: #ffffff;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 24px 60px rgba(0,0,0,0.08);
        border: 1px solid rgba(220,53,69,0.08);
    }
    .page-header {
        color: var(--doctor-red) !important;
        font-size: 2rem !important;
        font-weight: 700 !important;
        margin-bottom: 22px !important;
    }
    @media (max-width: 992px) {
        .main-content {
            margin-right: 0 !important;
            padding: 80px 20px 20px;
        }
    }
    .navbar {
        background: linear-gradient(135deg, var(--doctor-dark) 0%, var(--doctor-red) 100%);
        color: white;
        padding: 16px 24px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin: 0;
    }
    .navbar .navbar-brand h1,
    .navbar .navbar-user small,
    .navbar .navbar-user div {
        color: white;
    }
    .navbar .badge {
        background: #ffffff;
        color: var(--doctor-dark);
        font-weight: 700;
    }
    .user-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: white;
        color: var(--doctor-dark);
        font-weight: 700;
        margin-right: 10px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    }
  
    .main-container {
        max-width: 1220px;
        margin: 0 auto;
    }
    .dashboard-container {
        width: 100%;
    }
    .main-box,
    .chart-box,
    .table-box,
    .section-box,
    .card,
    .appointment-card,
    .modal-content {
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 16px 38px rgba(0,0,0,0.08);
        border: 1px solid var(--doctor-border);
    }
    .appointment-card {
        padding: 22px;
        margin-bottom: 20px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .appointment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 24px 48px rgba(0,0,0,0.12);
    }
    .btn-action {
        min-width: 140px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }
    .page-header {
        color: var(--doctor-red) !important;
        font-size: 2rem !important;
        font-weight: 700 !important;
        margin-bottom: 0 !important;
    }
    .empty-message {
        background: #ffffff;
        padding: 35px;
        border-radius: 18px;
        text-align: center;
        box-shadow: 0 18px 44px rgba(0,0,0,0.06);
        color: #6c757d;
    }
    .page-header {
        color: var(--doctor-red) !important;
        font-size: 2rem !important;
        font-weight: 700 !important;
        margin-bottom: 22px !important;
    }
    .btn-primary,
    .btn-info,
    .btn-success,
    .btn-danger,
    .btn-secondary {
        border-radius: 12px !important;
        font-weight: 700 !important;
        padding: 0.9rem 1.2rem !important;
    }
    .btn-primary {
        background: var(--doctor-red) !important;
        border-color: var(--doctor-red) !important;
        color: #fff !important;
    }
    .btn-info {
        background: var(--doctor-dark) !important;
        border-color: var(--doctor-dark) !important;
        color: #fff !important;
    }
    .btn-success {
        background: #198754 !important;
        border-color: #198754 !important;
        color: #fff !important;
    }
    .table thead th {
        background: rgba(220, 53, 69, 0.12) !important;
        color: var(--doctor-dark) !important;
        border: none !important;
    }
    .table tbody tr:hover {
        background: rgba(220, 53, 69, 0.06) !important;
    }
    .status-badge {
        border-radius: 20px !important;
        padding: 8px 16px !important;
        font-weight: 700 !important;
    }
    .status-scheduled { background: #f8d7da !important; color: var(--doctor-red) !important; }
    .status-confirmed { background: #d6e4ff !important; color: #084298 !important; }
    .status-completed { background: #c3e6cb !important; color: #0f5132 !important; }
    .empty-box,
    .empty-message {
        color: #777 !important;
    }
    .sidebar {
        position: fixed;
        top: 0;
        right: 0;
        width: 270px;
        min-height: 100vh;
        background: linear-gradient(135deg, var(--doctor-dark) 0%, var(--doctor-red) 100%);
        padding: 20px;
        color: white;
        box-shadow: 0 8px 24px rgba(136, 14, 79, 0.12);
        z-index: 100;
    }
    .sidebar-menu a, .sidebar .nav-link { color: rgba(255,255,255,0.95) !important; text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px; transition: all 0.2s ease; }
    .sidebar-menu a:hover, .sidebar .nav-link:hover, .sidebar-menu a.active, .sidebar .nav-link.active { background: rgba(255,255,255,0.18) !important; }
    .sidebar .logo { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); }
    .sidebar .logo i { color: var(--doctor-light); }
    @media (max-width: 992px) {
        .navbar,
        .main-content,
        .main-container,
        .dashboard-container {
            margin-right: 0 !important;
        }
        .sidebar { position: relative; width: 100%; }
        .main-content { padding: 20px; }
    }
</style>

<aside class="sidebar">

    <!-- Logo -->
    <div class="logo">
        👨‍⚕️ لوحة الطبيب
    </div>

    <ul class="sidebar-menu">

        <li>
            <a href="/baby_health/doctor/index.php" class="<?= ($current_page === 'index.php') ? 'active' : '' ?>">
                <span class="menu-icon">📊</span> لوحة التحكم
            </a>
        </li>

        <div class="sidebar-divider"></div>
        <strong>إدارة المرضى</strong>

        <li>
            <a href="/baby_health/doctor/patients.php" class="<?= ($current_page === 'patients.php') ? 'active' : '' ?>">
                <span class="menu-icon">👶</span> المرضى
            </a>
        </li>

        <li>
            <a href="/baby_health/doctor/medical_visits.php" class="<?= ($current_page === 'medical_visits.php') ? 'active' : '' ?>">
                <span class="menu-icon">📅</span> الزيارات الطبية
            </a>
        </li>

        <li>
            <a href="/baby_health/doctor/messages.php" class="<?= ($current_page === 'messages.php') ? 'active' : '' ?>">
                <span class="menu-icon">💬</span> الدردشة
                <span id="doctorMessageCount" class="badge bg-danger" style="display:none;"></span>
            </a>
        </li>

            <li class="nav-item">
                <a href="/baby_health/doctor/my_appointments.php" class="nav-link <?= ($current_page == 'my_appointments.php' ? 'active' : '') ?>">📅<span>الحجوزات</span></a>
            </li>

        <div class="sidebar-divider"></div>
        <strong>الرعاية الطبية</strong>

        <li>
            <a href="/baby_health/doctor/prescriptions.php" class="<?= ($current_page === 'prescriptions.php') ? 'active' : '' ?>">
                <span class="menu-icon">💊</span> الوصفات الطبية
            </a>
        </li>

        <li>
            <a href="/baby_health/doctor/medications.php" class="<?= ($current_page === 'medications.php') ? 'active' : '' ?>">
                <span class="menu-icon">🧾</span> الأدوية
            </a>
        </li>

        <li>
            <a href="/baby_health/doctor/nutrition.php" class="<?= ($current_page === 'nutrition.php') ? 'active' : '' ?>">
                <span class="menu-icon">🥗</span> التغذية
            </a>
        </li>

        <div class="sidebar-divider"></div>
        <strong>التقارير</strong>

        <li>
            <a href="/baby_health/doctor/reports.php" class="<?= ($current_page === 'reports.php') ? 'active' : '' ?>">
                <span class="menu-icon">📊</span> التقارير
            </a>
        </li>

        <li>
            <a href="/baby_health/doctor/growth_charts.php" class="<?= ($current_page === 'growth_charts.php') ? 'active' : '' ?>">
                <span class="menu-icon">📈</span> النمو
            </a>
        </li>

        <li>
            <a href="/baby_health/doctor/twins_comparison.php" class="<?= ($current_page === 'twins_comparison.php') ? 'active' : '' ?>">
                <span class="menu-icon">👯</span> التوائم
            </a>
        </li>

        <div class="sidebar-divider"></div>
        <strong>المكتبة</strong>

        <li>
            <a href="/baby_health/doctor/medical_articles.php" class="<?= ($current_page === 'medical_articles.php') ? 'active' : '' ?>">
                <span class="menu-icon">📚</span> المقالات
            </a>
        </li>

        <li>
            <a href="/baby_health/doctor/educational_videos.php" class="<?= ($current_page === 'educational_videos.php') ? 'active' : '' ?>">
                <span class="menu-icon">🎥</span> الفيديوهات
            </a>
        </li>

        <div class="sidebar-divider"></div>
        <strong>الإعدادات</strong>

        <li>
            <a href="/baby_health/doctor/settings.php" class="<?= ($current_page === 'settings.php') ? 'active' : '' ?>">
                <span class="menu-icon">⚙️</span> الإعدادات
            </a>
        </li>

        <li>
            <a href="/baby_health/doctor/profile.php" class="<?= ($current_page === 'profile.php') ? 'active' : '' ?>">
                <span class="menu-icon">👤</span> الملف الشخصي
            </a>
        </li>

        <div class="sidebar-divider"></div>

        <li>
            <a href="../logout.php" class="logout-link">
                <span class="menu-icon">🚪</span> تسجيل خروج
            </a>
        </li>

    </ul>
</aside>

<script>
function updateDoctorMessageBadge() {
    const badge = document.getElementById('doctorMessageCount');
    if (!badge) return;

    fetch('check_notifications.php')
        .then(res => res.json())
        .then(data => {
            const count = data.unread_messages || 0;

            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(() => {
            badge.style.display = 'none';
        });
}

updateDoctorMessageBadge();
setInterval(updateDoctorMessageBadge, 15000);
</script>
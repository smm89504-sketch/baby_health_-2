<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <li><a href="index.php" class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>"><span class="menu-icon">📊</span> لوحة التحكم</a></li>
        <li style="border-top: 1px solid #ede4f5; padding-top: 10px; margin-top: 10px;"><strong style="padding: 0 20px; color: #9d84ca;">إدارة المستخدمين</strong></li>
        <li><a href="parents.php" class="<?php echo ($current_page === 'parents.php') ? 'active' : ''; ?>"><span class="menu-icon">👨‍👩‍👧</span> إدارة الآباء</a></li>
        <li><a href="nurses.php" class="<?php echo ($current_page === 'nurses.php') ? 'active' : ''; ?>"><span class="menu-icon">⚕️</span> إدارة الممرضات</a></li>
        <li><a href="doctors.php" class="<?php echo ($current_page === 'doctors.php') ? 'active' : ''; ?>"><span class="menu-icon">👨‍⚕️</span> إدارة الأطباء</a></li>
        <li style="border-top: 1px solid #ede4f5; padding-top: 10px; margin-top: 10px;"><strong style="padding: 0 20px; color: #9d84ca;">البيانات</strong></li>
        <li><a href="children.php" class="<?php echo ($current_page === 'children.php') ? 'active' : ''; ?>"><span class="menu-icon">👶</span> الأطفال</a></li>
        <li><a href="activities.php" class="<?php echo ($current_page === 'activities.php') ? 'active' : ''; ?>"><span class="menu-icon">📋</span> الأنشطة</a></li>
        <li><a href="vaccines.php" class="<?php echo ($current_page === 'vaccines.php') ? 'active' : ''; ?>"><span class="menu-icon">💉</span> سجلات التطعيم</a></li>
        <li><a href="overdue_vaccines.php" class="<?php echo ($current_page === 'overdue_vaccines.php') ? 'active' : ''; ?>"><span class="menu-icon">⏰</span> الأطفال المتأخرون</a></li>
        <li><a href="vaccine_types.php" class="<?php echo ($current_page === 'vaccine_types.php') ? 'active' : ''; ?>"><span class="menu-icon">📋</span> أنواع التطعيم</a></li>
        <li><a href="common_symptoms.php" class="<?php echo ($current_page === 'common_symptoms.php') ? 'active' : ''; ?>"><span class="menu-icon">⚕️</span> الأعراض الشائعة</a></li>
        <li><a href="age_group_medication_lists.php" class="<?php echo ($current_page === 'age_group_medication_lists.php') ? 'active' : ''; ?>"><span class="menu-icon">💊</span> قوائم الأدوية حسب الفئة العمرية</a></li>
        <li><a href="medication_interactions.php" class="<?php echo ($current_page === 'medication_interactions.php') ? 'active' : ''; ?>"><span class="menu-icon">⚠️</span> التفاعلات الدوائية</a></li>
        <li><a href="prescriptions.php" class="<?php echo ($current_page === 'prescriptions.php') ? 'active' : ''; ?>"><span class="menu-icon">📋</span> إدارة الوصفات الطبية</a></li>
        <li style="border-top: 1px solid #ede4f5; padding-top: 10px; margin-top: 10px;"><strong style="padding: 0 20px; color: #9d84ca;">المكتبة</strong></li>
        <li><a href="manage_articles.php" class="<?php echo ($current_page === 'manage_articles.php') ? 'active' : ''; ?>"><span class="menu-icon">📚</span> المقالات الطبية</a></li>
        <li><a href="manage_videos.php" class="<?php echo ($current_page === 'manage_videos.php') ? 'active' : ''; ?>"><span class="menu-icon">🎥</span> الفيديوهات التعليمية</a></li>
         <li style="border-top: 1px solid #ede4f5; padding-top: 10px; margin-top: 10px;"><strong style="padding: 0 20px; color: #9d84ca;">النظام</strong></li>
        <li><a href="report_analytics.php" class="<?php echo ($current_page === 'report_analytics.php') ? 'active' : ''; ?>"><span class="menu-icon">📊</span> تقارير</a></li>
        <li><a href="sleep_management.php" class="<?php echo ($current_page === 'sleep_management.php') ? 'active' : ''; ?>"><span class="menu-icon">😴</span> النوم</a></li>
        <li><a href="logs.php" class="<?php echo ($current_page === 'logs.php') ? 'active' : ''; ?>"><span class="menu-icon">📜</span> السجلات</a></li>
        <li><a href="system.php" class="<?php echo ($current_page === 'system.php') ? 'active' : ''; ?>"><span class="menu-icon">⚙️</span> إعدادات</a></li>
        <li style="border-top: 1px solid #ede4f5; padding-top: 10px; margin-top: 10px;">
            <a href="../logout.php"><span class="menu-icon">🚪</span> تسجيل خروج</a>
        </li>
    </ul>
</aside>

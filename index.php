<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="app_name">نظام رعاية الطفل - الزوار</title>
    <link rel="stylesheet" href="guest_landing.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

</head>
<body>
    <!-- navigation bar-->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <button id="menuToggle" class="btn-icon menu-btn" aria-label="قائمة">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 data-i18n="app_name"><i class="fas fa-baby baby-icon"></i> نظام رعاية الطفل</h1>
                <p data-i18n="app_desc">نظام متقدم لتتبع صحة الطفل والنمو</p>
            </div>
            <div class="navbar-nav">
                <a href="#home" class="nav-link active" data-i18n="nav_home">الرئيسية</a>
                <a href="#features" class="nav-link" data-i18n="nav_features">المميزات</a>
                <a href="#ai-tools" class="nav-link" data-i18n="nav_ai_tools">أدوات ذكية</a>
                <a href="#statistics" class="nav-link" data-i18n="nav_stats">الإحصائيات</a>
                 <a href="guest_articles.php" class="nav-link">المقالات</a>
                <a href="guest_videos.php" class="nav-link">الفيديوهات</a>
                <a href="#contact" class="nav-link" data-i18n="nav_contact">التواصل</a>
                <a href="guest_landing.php" class="nav-link" data-i18n="nav_login">تسجيل الدخول</a>
                <div class="settings-group">
                    <button id="themeToggle" class="btn-icon" title="تبديل الثيم" aria-label="تغيير الوضع">
                        <i class="fas fa-moon"></i>
                    </button>

                    <div class="language-dropdown">
                        <button id="langToggle" class="btn-icon lang-btn" aria-label="تغيير اللغة">
                            <span class="current-lang">العربية</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="lang-menu" id="langMenu">
                            <button data-lang="ar">العربية</button>
                            <button data-lang="en">English</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section id="home" class="hero">
        <div class="container">
            <h1 data-i18n="hero_title">طفلك يستحق كل الحنان... وكل الذكاء</h1>
            <p class="lead" data-i18n="hero_subtitle">نتابع معك نموه، نفهم بكاءه، نطمئنك على صحته بكل حب وسهولة</p>

            <div class="hero-buttons">
                <button class="btn btn-primary" onclick="document.getElementById('ai-tools').scrollIntoView({behavior: 'smooth'})" data-i18n="btn_try_now">
                    جرّب الأدوات الآن مجاناً
                </button>
                <a href="#features" class="btn btn-outline" data-i18n="btn_discover_more">اكتشف المزيد</a>
            </div>

            <div class="hero-visual">
                <img src="https://thumbs.dreamstime.com/b/love-399540052.jpg" alt="أم تحتضن طفلها بابتسامة دافئة">
            </div>
        </div>
    </section>

    <!-- Features section-->
    <section id="features" class="features">
        <div class="container">
            <h2 data-i18n="section_features">لماذا تختار نظام رعاية الطفل؟</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 data-i18n="feature_growth">تتبع النمو</h3>
                    <p data-i18n="feature_growth_desc">تتبع ذكي لنمو الطفل بناءً على معايير منظمة الصحة العالمية</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3 data-i18n="feature_cry">تحليل البكاء</h3>
                    <p data-i18n="feature_cry_desc">تحليل ذكي لأنماط بكاء الطفل لتحديد احتياجاته</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💊</div>
                    <h3 data-i18n="feature_health">استشارات صحية</h3>
                    <p data-i18n="feature_health_desc">استشارات طبية موثوقة للأعراض الشائعة عند الأطفال</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3 data-i18n="feature_easy">سهل الاستخدام</h3>
                    <p data-i18n="feature_easy_desc">واجهة سهلة وبسيطة مصممة للآباء المشغولين</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3 data-i18n="feature_secure">آمن وموثوق</h3>
                    <p data-i18n="feature_secure_desc">بيانات الطفل محمية بمعايير أمان عالية</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3 data-i18n="feature_ai">ذكاء اصطناعي</h3>
                    <p data-i18n="feature_ai_desc">تقنيات متقدمة للتنبؤ الدقيق والتحليل الذكي</p>
                </div>
            </div>
        </div>
    </section>

    <!--Statistics Department-->
    <section id="statistics" class="statistics">
        <div class="container">
            <h2 data-i18n="section_statistics">تأثيرنا</h2>
            <div class="stats-grid">
                <div class="stat-card" id="stat-babies">
                    <div class="stat-number">--</div>
                    <div class="stat-label" data-i18n="stat_babies">أطفال نشطين</div>
                </div>
                <div class="stat-card" id="stat-parents">
                    <div class="stat-number">--</div>
                    <div class="stat-label" data-i18n="stat_parents">حسابات الآباء</div>
                </div>
                <div class="stat-card" id="stat-vaccines">
                    <div class="stat-number">--</div>
                    <div class="stat-label" data-i18n="stat_vaccines">تطعيمات تم تتبعها</div>
                </div>
                <div class="stat-card" id="stat-healthy">
                    <div class="stat-number">--</div>
                    <div class="stat-label" data-i18n="stat_healthy">نمو صحي</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Smart Tools Section-->
    <section id="ai-tools" class="ai-tools">
        <div class="container">
            <h2 data-i18n="section_ai_tools">جرّب أدواتنا الذكية</h2>
            <div class="tools-layout">
                <div class="tools-row">
                    <!-- Growth tracking tool -->
                    <div class="tool-card">
                        <div class="tool-icon">📈</div>
                        <h3 data-i18n="tool_growth_title">تنبؤ النمو</h3>
                        <p data-i18n="tool_growth_desc">احصل على رؤى ذكية عن نمو طفلك</p>
                        <form id="growthForm" class="ai-form">
                            <div class="form-group">
                                <label data-i18n="form_child_name">اسم الطفل:</label>
                                <input type="text" name="guest_name" placeholder="أدخل اسم الطفل" data-i18n-placeholder="form_child_name" required>
                            </div>
                            <div class="form-group">
                                <label data-i18n="form_email">بريدك الإلكتروني:</label>
                                <input type="email" name="guest_email" placeholder="بريدك@البريد.com" data-i18n-placeholder="form_email" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label data-i18n="form_age_months">العمر (شهور):</label>
                                    <input type="number" name="age_months" min="0" max="24" placeholder="0-24" data-i18n-placeholder="form_age_months" required>
                                </div>
                                <div class="form-group">
                                    <label data-i18n="form_weight">الوزن (كغ):</label>
                                    <input type="number" name="weight" step="0.1" min="1" max="20" placeholder="كغ" data-i18n-placeholder="form_weight" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label data-i18n="form_height">الطول (سم):</label>
                                    <input type="number" name="height" step="0.1" min="40" max="100" placeholder="سم" data-i18n-placeholder="form_height" required>
                                </div>
                                <div class="form-group">
                                    <label data-i18n="form_gender">الجنس:</label>
                                    <select name="gender">
                                        <option value="male" data-i18n="gender_male">ذكر</option>
                                        <option value="female" data-i18n="gender_female">أنثى</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-predict" data-i18n="btn_predict_growth">تحليل النمو</button>
                        </form>
                        <div class="result-box" id="growthResult"></div>
                    </div>

                    <!-- Cry analysis tool-->
                    <div class="tool-card">
                        <div class="tool-icon">🎙️</div>
                        <h3 data-i18n="tool_cry_title">تحليل البكاء</h3>
                        <p data-i18n="tool_cry_desc">افهم ما يحتاجه طفلك من خلال أنماط بكاؤه</p>
                        <form id="cryForm" class="ai-form">
                            <div class="form-group">
                                <label data-i18n="form_child_name">اسم الطفل:</label>
                                <input type="text" name="guest_name" placeholder="أدخل اسم الطفل" data-i18n-placeholder="form_child_name" required>
                            </div>
                            <div class="form-group">
                                <label data-i18n="form_email">بريدك الإلكتروني:</label>
                                <input type="email" name="guest_email" placeholder="بريدك@البريد.com" data-i18n-placeholder="form_email" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label data-i18n="form_duration_sec">المدة (ثانية):</label>
                                    <input type="number" name="duration_seconds" min="0" placeholder="0-7200" data-i18n-placeholder="form_duration_sec" required>
                                </div>
                                <div class="form-group">
                                    <label data-i18n="form_intensity">الشدة (1-10):</label>
                                    <input type="range" name="intensity_1_10" min="1" max="10" value="5" oninput="document.getElementById('intensityValue').textContent = this.value">
                                    <span id="intensityValue">5</span>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label data-i18n="form_time_of_day">الوقت:</label>
                                    <select name="time_of_day" required>
                                        <option value="" data-i18n="select_placeholder">-- اختر --</option>
                                        <option value="morning" data-i18n="time_morning">الصباح</option>
                                        <option value="afternoon" data-i18n="time_afternoon">بعد الظهر</option>
                                        <option value="evening" data-i18n="time_evening">المساء</option>
                                        <option value="night" data-i18n="time_night">الليل</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label data-i18n="form_last_fed">آخر رضاعة (دقيقة):</label>
                                    <input type="number" name="last_fed_minutes_ago" min="0" placeholder="اختياري" data-i18n-placeholder="form_last_fed">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>عمر الطفل (شهور):</label>
                                <input type="number" name="age_months" min="0" max="24" placeholder="اختياري">
                            </div>
                            <button type="submit" class="btn btn-predict" data-i18n="btn_predict_cry">تحليل البكاء</button>
                        </form>
                        <div class="result-box" id="cryResult"></div>
                    </div>
                </div>

                <!-- Symptom screening tool-->
                <div class="tools-center">
                    <div class="tool-card center-card">
                        <div class="tool-icon">🩺</div>
                        <h3 data-i18n="tool_symptom_title">فحص الأعراض</h3>
                        <p data-i18n="tool_symptom_desc">احصل على توجيهات حول الأعراض الشائعة عند الأطفال</p>
                        <form id="symptomForm" class="ai-form">
                            <div class="form-group">
                                <label data-i18n="form_child_name">اسم الطفل:</label>
                                <input type="text" name="guest_name" placeholder="أدخل اسم الطفل" data-i18n-placeholder="form_child_name" required>
                            </div>
                            <div class="form-group">
                                <label data-i18n="form_email">بريدك الإلكتروني:</label>
                                <input type="email" name="guest_email" placeholder="بريدك@البريد.com" data-i18n-placeholder="form_email" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label data-i18n="form_symptom">العرض:</label>
                                    <select name="symptom" required>
                                        <option value="" data-i18n="select_symptom">-- اختر العرض --</option>
                                        <option value="fever" data-i18n="symptom_fever">حمى</option>
                                        <option value="cough" data-i18n="symptom_cough">سعال</option>
                                        <option value="diarrhea" data-i18n="symptom_diarrhea">إسهال</option>
                                        <option value="constipation" data-i18n="symptom_constipation">إمساك</option>
                                        <option value="rash" data-i18n="symptom_rash">طفح جلدي</option>
                                        <option value="vomiting" data-i18n="symptom_vomiting">قيء</option>
                                        <option value="other" data-i18n="symptom_other">أخرى</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label data-i18n="form_temperature">درجة الحرارة (°م):</label>
                                    <input type="number" name="temperature" step="0.1" value="36.5" placeholder="36.5" data-i18n-placeholder="form_temperature">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label data-i18n="form_duration_hrs">المدة (ساعات):</label>
                                    <input type="number" name="duration_hours" min="0" value="1" data-i18n-placeholder="form_duration_hrs" required>
                                </div>
                                <div class="form-group">
                                    <label>عمر الطفل (شهور):</label>
                                    <input type="number" name="age_months" min="0" max="24" value="6" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-predict" data-i18n="btn_symptom_guidance">احصل على التوجيهات</button>
                        </form>
                        <div class="result-box" id="symptomResult"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Common Symptoms Section-->
    <section class="common-symptoms">
        <div class="container">
            <h2>دليل الأعراض الشائعة</h2>
            <div class="symptoms-grid" id="symptomsContainer">
                <!-- يتم ملءه بواسطة JavaScript -->
            </div>
        </div>
    </section>

    <!-- Growth Standards Section-->
    <section class="growth-benchmarks">
        <div class="container">
            <h2>معايير النمو (معايير منظمة الصحة العالمية)</h2>
            <div class="benchmark-table-wrapper">
                <table class="benchmark-table" id="benchmarkTable">
                    <thead>
                        <tr>
                            <th>العمر (شهور)</th>
                            <th>متوسط الوزن (كغ)</th>
                            <th>نطاق الوزن (كغ)</th>
                            <th>متوسط الطول (سم)</th>
                            <th>نطاق الطول (سم)</th>
                        </tr>
                    </thead>
                    <tbody id="benchmarkTableBody">
                        <!-- يتم ملءه بواسطة JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    
    <!-- Library section: Articles and videos - Independent pages -->
     <section class="library">
        <div class="container">
            <h2 data-i18n="section_library">مكتبتنا التعليمية</h2>
            <p class="section-subtitle">اقرأ المقالات التعليمية وشاهد الفيديوهات لفهم أفضل لصحة طفلك</p>

            <div class="library-grid">
                <a href="guest_articles.php" class="library-card">
                    <div class="card-icon">📚</div>
                    <h3 data-i18n="tab_articles">المقالات</h3>
                    <p>اكتشف المقالات الطبية والتربوية المنشورة بواسطة الفريق الطبي.</p>
                </a>
                <a href="guest_videos.php" class="library-card">
                    <div class="card-icon">🎥</div>
                    <h3 data-i18n="tab_videos">الفيديوهات</h3>
                    <p>شاهد الفيديوهات التعليمية المباشرة مع شرح للممارسات الصحية.</p>
                </a>
            </div>
        </div>
    </section>



    <!-- Appendix-->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>نظام رعاية الطفل</h3>
                    <p>نظام متقدم لتتبع صحة الطفل مع استشارات ذكية</p>
                </div>
                <div class="footer-section">
                    <h3>روابط سريعة</h3>
                    <ul>
                        <li><a href="#home">الرئيسية</a></li>
                        <li><a href="#features">المميزات</a></li>
                        <li><a href="#ai-tools">الأدوات الذكية</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>التواصل</h3>
                    <ul>
                        <li>البريد: info@babycare.com</li>
                        <li>الهاتف: +1 (555) 123-4567</li>
                        <li>الدعم: support@babycare.com</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>قانوني</h3>
                    <ul>
                        <li><a href="#">سياسة الخصوصية</a></li>
                        <li><a href="#">شروط الخدمة</a></li>
                        <li><a href="#">تحذير طبي</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p data-i18n="footer_copyright">&copy; 2026 نظام رعاية الطفل. جميع الحقوق محفوظة.</p>
                <p><em data-i18n="footer_disclaimer">تنبيه: هذا النظام يوفر توجيهات فقط. استشر المتخصصين الطبيين دائماً.</em></p>
            </div>
        </div>
    </footer>


    <script src="guest_landing.js"></script>
</body>

</html>
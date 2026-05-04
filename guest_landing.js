// ──────────────────────────────────────────────
// Translations
// ──────────────────────────────────────────────
const translations = {
    ar: {
        app_name: "نظام رعاية الطفل",
        app_desc: "نظام متقدم لتتبع صحة الطفل والنمو",
        nav_home: "الرئيسية",
        nav_features: "المميزات",
        nav_ai_tools: "أدوات ذكية",
        nav_stats: "الإحصائيات",
        nav_contact: "التواصل",
        nav_login: "تسجيل الدخول",
        hero_title: "طفلك يستحق كل الحنان... وكل الذكاء",
        hero_subtitle: "نتابع معك نموه، نفهم بكاءه، نطمئنك على صحته بكل حب وسهولة",
        btn_try_now: "جرّب الأدوات الآن مجاناً",
        btn_discover_more: "اكتشف المزيد",
        section_features: "لماذا تختار نظام رعاية الطفل؟",
        feature_growth: "تتبع النمو",
        feature_growth_desc: "تتبع ذكي لنمو الطفل بناءً على معايير منظمة الصحة العالمية",
        feature_cry: "تحليل البكاء",
        feature_cry_desc: "تحليل ذكي لأنماط بكاء الطفل لتحديد احتياجاته",
        feature_health_desc: "استشارات طبية موثوقة للأعراض الشائعة عند الأطفال",
        feature_easy_desc: "واجهة سهلة وبسيطة مصممة للآباء المشغولين",
        feature_secure_desc: "بيانات الطفل محمية بمعايير أمان عالية",
        feature_ai_desc: "تقنيات متقدمة للتنبؤ الدقيق والتحليل الذكي",
        section_ai_tools: "جرّب أدواتنا الذكية",
        section_statistics: "تأثيرنا",
        tool_growth_title: "تنبؤ النمو",
        tool_growth_desc: "احصل على رؤى ذكية عن نمو طفلك",
        tool_cry_title: "تحليل البكاء",
        tool_cry_desc: "افهم ما يحتاجه طفلك من خلال أنماط بكاؤه",
        tool_symptom_title: "فحص الأعراض",
        tool_symptom_desc: "احصل على توجيهات حول الأعراض الشائعة عند الأطفال",
        form_child_name: "اسم الطفل:",
        form_email: "بريدك الإلكتروني:",
        form_age_months: "العمر (شهور):",
        form_weight: "الوزن (كغ):",
        form_height: "الطول (سم):",
        form_gender: "الجنس:",
        form_duration_sec: "المدة (ثانية):",
        form_intensity: "الشدة (1-10):",
        form_time_of_day: "الوقت:",
        form_last_fed: "آخر رضاعة (دقيقة):",
        form_age_optional: "عمر الطفل (شهور):",
        form_symptom: "العرض:",
        form_temperature: "درجة الحرارة (°م):",
        form_duration_hrs: "المدة (ساعات):",
        btn_predict_growth: "تحليل النمو",
        btn_predict_cry: "تحليل البكاء",
        btn_symptom_guidance: "احصل على التوجيهات",
        stat_babies: "أطفال نشطين",
        stat_parents: "حسابات الآباء",
        stat_vaccines: "تطعيمات تم تتبعها",
        stat_healthy: "نمو صحي",
        gender_male: "ذكر",
        gender_female: "أنثى",
        time_morning: "الصباح",
        time_afternoon: "بعد الظهر",
        time_evening: "المساء",
        time_night: "الليل",
        symptom_fever: "حمى",
        symptom_cough: "سعال",
        symptom_diarrhea: "إسهال",
        symptom_constipation: "إمساك",
        symptom_rash: "طفح جلدي",
        symptom_vomiting: "قيء",
        symptom_other: "أخرى",
        select_placeholder: "-- اختر --",
        select_symptom: "-- اختر العرض --",
        footer_copyright: "© 2026 نظام رعاية الطفل. جميع الحقوق محفوظة.",
        footer_disclaimer: "تنبيه: هذا النظام يوفر توجيهات فقط. استشر المتخصصين الطبيين دائماً.",
        section_library: "مكتبتنا التعليمية",
        tab_articles: "المقالات",
        tab_videos: "الفيديوهات"
    },
    en: {
        app_name: "Baby Care System",
        app_desc: "Advanced system for tracking child health and growth",
        nav_home: "Home",
        nav_features: "Features",
        nav_ai_tools: "Smart Tools",
        nav_stats: "Statistics",
        nav_contact: "Contact",
        nav_login: "Login",
        hero_title: "Your baby deserves all the love... and all the intelligence",
        hero_subtitle: "We track growth, understand cries, reassure you about health with love & ease",
        btn_try_now: "Try tools now – Free",
        btn_discover_more: "Discover more",
        section_features: "Why choose Baby Care System?",
        feature_growth: "Growth Tracking",
        feature_growth_desc: "Smart tracking of child growth based on WHO standards",
        feature_cry: "Cry Analysis",
        feature_cry_desc: "Intelligent analysis of crying patterns to identify needs",
        feature_health_desc: "Trusted medical advice for common pediatric symptoms",
        feature_easy_desc: "Easy, user-friendly interface designed for busy parents",
        feature_secure_desc: "Child data protected with high security standards",
        feature_ai_desc: "Advanced AI for accurate predictions and smart analysis",
        section_ai_tools: "Try Our Smart Tools",
        section_statistics: "Our Impact",
        tool_growth_title: "Growth Prediction",
        tool_growth_desc: "Get smart insights on your child's growth",
        tool_cry_title: "Cry Analysis",
        tool_cry_desc: "Understand your baby's needs through cry patterns",
        tool_symptom_title: "Symptom Checker",
        tool_symptom_desc: "Get guidance for common child symptoms",
        form_child_name: "Child's Name:",
        form_email: "Your Email:",
        form_age_months: "Age (months):",
        form_weight: "Weight (kg):",
        form_height: "Height (cm):",
        form_gender: "Gender:",
        form_duration_sec: "Duration (seconds):",
        form_intensity: "Intensity (1–10):",
        form_time_of_day: "Time of Day:",
        form_last_fed: "Last Fed (minutes):",
        form_age_optional: "Child's Age (months):",
        form_symptom: "Symptom:",
        form_temperature: "Temperature (°C):",
        form_duration_hrs: "Duration (hours):",
        btn_predict_growth: "Analyze Growth",
        btn_predict_cry: "Analyze Cry",
        btn_symptom_guidance: "Get Guidance",
        stat_babies: "Active babies",
        stat_parents: "Parent accounts",
        stat_vaccines: "Vaccines tracked",
        stat_healthy: "Healthy growth",
        gender_male: "Male",
        gender_female: "Female",
        time_morning: "Morning",
        time_afternoon: "Afternoon",
        time_evening: "Evening",
        time_night: "Night",
        symptom_fever: "Fever",
        symptom_cough: "Cough",
        symptom_diarrhea: "Diarrhea",
        symptom_constipation: "Constipation",
        symptom_rash: "Rash",
        symptom_vomiting: "Vomiting",
        symptom_other: "Other",
        select_placeholder: "-- choose --",
        select_symptom: "-- choose symptom --",
        footer_copyright: "© 2026 Baby Care System. All rights reserved.",
        footer_disclaimer: "Disclaimer: This system provides guidance only. Always consult medical professionals.",
        section_library: "Our Learning Library",
        tab_articles: "Articles",
        tab_videos: "Videos"
    }
};

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────
const t = (key) => translations[currentLang]?.[key] || key;

function setLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('lang', lang);
    document.documentElement.lang = lang;
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (key) el.textContent = t(key);
    });
    // also handle placeholders and values
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (key) el.setAttribute('placeholder', t(key));
    });
    document.querySelectorAll('[data-i18n-value]').forEach(el => {
        const key = el.getAttribute('data-i18n-value');
        if (key) el.value = t(key);
    });
    document.querySelector('.current-lang').textContent = lang === 'ar' ? 'العربية' : 'English';
    // update document title if translation exists
    const titleKey = 'app_name';
    if (translations[currentLang] && translations[currentLang][titleKey]) {
        document.title = translations[currentLang][titleKey];
    }
}

function toggleTheme() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    document.getElementById('themeToggle').innerHTML = 
        `<i class="fas fa-${newTheme === 'dark' ? 'sun' : 'moon'}"></i>`;
}

// ──────────────────────────────────────────────
// Initialization
// ──────────────────────────────────────────────
let currentLang = localStorage.getItem('lang') || 'ar';
let currentTheme = localStorage.getItem('theme') || 'light';

document.documentElement.setAttribute('data-theme', currentTheme);
document.documentElement.setAttribute('dir', currentLang === 'ar' ? 'rtl' : 'ltr');
document.documentElement.setAttribute('lang', currentLang);

const API_BASE_URL = './guest_handler.php';


document.addEventListener('DOMContentLoaded', () => {
    setLanguage(currentLang);  // تطبيق الترجمة بعد تحميل الـ DOM

    // Theme button
    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.innerHTML = `<i class="fas fa-${currentTheme === 'dark' ? 'sun' : 'moon'}"></i>`;
        themeBtn.addEventListener('click', toggleTheme);
    }

    // Language dropdown
    const langToggle = document.getElementById('langToggle');
    const langMenu = document.getElementById('langMenu');
    if (langToggle && langMenu) {
        langToggle.addEventListener('click', () => langMenu.classList.toggle('show'));
        langMenu.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                setLanguage(btn.dataset.lang);
                langMenu.classList.remove('show');
                // يمكنك إزالة reload إذا أردت تجربة بدون إعادة تحميل
                // window.location.reload();
            });
        });
        document.addEventListener('click', e => {
            if (!langToggle.contains(e.target) && !langMenu.contains(e.target)) {
                langMenu.classList.remove('show');
            }
        });
    }
    
    loadStatistics();
    loadCommonSymptoms();
    loadGrowthBenchmarks();
    
    attachFormListeners();
    
    attachNavigation();

    // mobile menu toggle
    const menuBtn = document.getElementById('menuToggle');
    if (menuBtn) {
        menuBtn.addEventListener('click', () => {
            document.querySelector('.navbar-nav').classList.toggle('show');
        });
    }
   
});

// ==================== مستمعي النماذج ====================

function attachFormListeners() {
    const growthForm = document.getElementById('growthForm');
    const cryForm = document.getElementById('cryForm');
    const symptomForm = document.getElementById('symptomForm');
    
    if (growthForm) {
        growthForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleGrowthPrediction();
        });
    }
    
    if (cryForm) {
        cryForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleCryAnalysis();
        });
    }
    
    if (symptomForm) {
        symptomForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleSymptomGuidance();
        });
    }
}

// ==================== التنبؤ بالنمو ====================

async function handleGrowthPrediction() {
    const form = document.getElementById('growthForm');
    const resultBox = document.getElementById('growthResult');
    
    const formData = {
        action: 'growth_prediction',
        age_months: form.querySelector('[name="age_months"]').value,
        weight: form.querySelector('[name="weight"]').value,
        height: form.querySelector('[name="height"]').value,
        gender: form.querySelector('[name="gender"]').value,
        guest_name: form.querySelector('[name="guest_name"]').value,
        guest_email: form.querySelector('[name="guest_email"]').value
    };
    
    showLoading();
    
    try {
        console.log('📤 إرسال التنبؤ بالنمو إلى:', API_BASE_URL);
        console.log('البيانات:', formData);
        
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        console.log('📨 حالة الرد:', response.status);
        const data = await response.json();
        console.log('📥 بيانات الرد:', data);
        
        hideLoading();
        
        if (data.success) {
            console.log('✅ العرض الآن...');
            displayGrowthResult(data.data, resultBox);
        } else {
            showAlert('❌ خطأ: ' + (data.message || 'خطأ غير معروف'), 'error');
            resultBox.classList.remove('show');
        }
    } catch (error) {
        hideLoading();
        console.error('💥 الخطأ:', error);
        showAlert('❌ خطأ في الاتصال: ' + error.message + '\n\nتأكد من:\n1. تشغيل Python API على المنفذ 5000\n2. تشغيل XAMPP وتشغيل MySQL', 'error');
    }
}

function displayGrowthResult(result, container) {
    console.log('🎨 عرض نتائج النمو:', result);
    
    if (!result) {
        showAlert('❌ لم تتلقَّ البيانات بشكل صحيح', 'error');
        return;
    }
    
    const statusEmoji = {
        'NORMAL': '✅',
        'LOW': '⚠️',
        'HIGH': '⚡',
        'منخفض': '⚠️',
        'طبيعي': '✅',
        'مرتفع': '⚡',
        'default': '📊'
    };
    
    const statusColor = {
        'NORMAL': '#4CAF50',
        'LOW': '#FF9800',
        'HIGH': '#2196F3',
        'منخفض': '#FF9800',
        'طبيعي': '#4CAF50',
        'مرتفع': '#2196F3'
    };
    
    // التعامل مع التوصيات - قد تكون مصفوفة أو نص
    let recommendationsHtml = '';
    if (Array.isArray(result.recommendations)) {
        recommendationsHtml = result.recommendations.map(rec => `<li>${rec}</li>`).join('');
    } else if (result.recommendations) {
        recommendationsHtml = `<li>${result.recommendations}</li>`;
    }
    
    const status = result.status || 'default';
    const message = result.message || 'تحليل النمو';
    
    const html = `
        <div class="result-content" style="border-left: 4px solid ${statusColor[status] || '#2196F3'}; padding-left: 1rem; background: #f9f9f9; padding: 1.5rem; border-radius: 8px;">
            <div class="result-title" style="font-size: 1.5rem; margin-bottom: 1rem; color: #333;">
                ${statusEmoji[status] || statusEmoji.default}
                ${message}
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0; background: white; padding: 1rem; border-radius: 5px;">
                <div>
                    <strong style="color: #666;">📊 نسبة الوزن المئوية:</strong> 
                    <span style="font-size: 1.2rem; color: #2196F3;">${result.weight_percentile || 'N/A'}%</span>
                </div>
                <div>
                    <strong style="color: #666;">📏 نسبة الطول المئوية:</strong> 
                    <span style="font-size: 1.2rem; color: #2196F3;">${result.height_percentile || 'N/A'}%</span>
                </div>
                <div>
                    <strong style="color: #666;">📈 متوسط النسبة المئوية:</strong> 
                    <span style="font-size: 1.2rem; color: #2196F3;">${result.average_percentile || 'N/A'}%</span>
                </div>
                <div>
                    <strong style="color: #666;">🎯 مستوى الثقة:</strong> 
                    <span style="font-size: 1.2rem; color: #2196F3;">${result.confidence_score ? (result.confidence_score * 100).toFixed(1) : 'N/A'}%</span>
                </div>
            </div>
            
            ${recommendationsHtml ? `
            <div style="background: linear-gradient(135deg, #E3F2FD 0%, #F3E5F5 100%); padding: 1rem; border-radius: 5px; margin: 1rem 0; border-right: 4px solid #9C27B0;">
                <strong style="color: #1976D2;">💡 التوصيات المخصصة:</strong>
                <ul class="result-list" style="list-style: none; padding: 1rem 0 0 0; margin: 0;">
                    ${recommendationsHtml}
                </ul>
            </div>
            ` : ''}
            
            <div style="margin-top: 1.5rem; font-size: 0.85rem; color: #999; text-align: center; padding-top: 1rem; border-top: 1px solid #eee;">
                ⏰ وقت التحليل: ${new Date().toLocaleString('ar-SA')}
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    container.classList.add('show');
    console.log('✅ تم عرض النتائج بنجاح');
    setTimeout(() => container.scrollIntoView({ behavior: 'smooth' }), 300);
}

// ==================== تحليل البكاء ====================

async function handleCryAnalysis() {
    const form = document.getElementById('cryForm');
    const resultBox = document.getElementById('cryResult');
    
    const formData = {
        action: 'cry_analysis',
        duration_seconds: form.querySelector('[name="duration_seconds"]').value,
        intensity_1_10: form.querySelector('[name="intensity_1_10"]').value,
        time_of_day: form.querySelector('[name="time_of_day"]').value,
        last_fed_minutes_ago: form.querySelector('[name="last_fed_minutes_ago"]').value || null,
        age_months: form.querySelector('[name="age_months"]').value || null,
        guest_name: form.querySelector('[name="guest_name"]').value,
        guest_email: form.querySelector('[name="guest_email"]').value
    };
    
    showLoading();
    
    try {
        console.log('📤 إرسال تحليل البكاء إلى:', API_BASE_URL);
        console.log('البيانات:', formData);
        
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        console.log('📨 حالة الرد:', response.status);
        const data = await response.json();
        console.log('📥 بيانات الرد:', data);
        
        hideLoading();
        
        if (data.success) {
            console.log('✅ العرض الآن...');
            displayCryResult(data.data, resultBox, formData);
        } else {
            showAlert('❌ خطأ: ' + (data.message || 'خطأ غير معروف'), 'error');
            resultBox.classList.remove('show');
        }
    } catch (error) {
        hideLoading();
        console.error('💥 الخطأ:', error);
        showAlert('❌ خطأ في الاتصال: ' + error.message, 'error');
    }
}

function displayCryResult(result, container, formData) {
    console.log('🎨 عرض نتائج تحليل البكاء:', result);
    
    if (!result) {
        showAlert('❌ لم تتلقَّ البيانات بشكل صحيح', 'error');
        return;
    }
    
    const reasonCategoryEmoji = {
        'جوع': '🍼',
        'عدم ارتياح': '😫',
        'التعب': '😴',
        'فرط التحفيز': '⚡',
        'المرض': '🤒',
        'ملل': '😐',
        'غير معروف': '❓',
        'جوع': '🍼',
        'عدم ارتياح': '😫',
        'تعب': '😴',
        'فرط التحفيز': '⚡',
        'المرض': '🤒'
    };
    
    const recommendations = Array.isArray(result.recommendations) ? 
        result.recommendations : 
        (result.recommendations ? [result.recommendations] : []);
    
    const detailedAnalysis = Array.isArray(result.detailed_analysis) ? 
        result.detailed_analysis : 
        (result.detailed_analysis ? [result.detailed_analysis] : []);
    
    const durationMinutes = formData ? Math.floor(formData.duration_seconds / 60) : 0;
    const intensity = formData ? formData.intensity_1_10 : (result.intensity_analysis || 'N/A');
    const timeOfDay = formData ? formData.time_of_day : (result.time_of_day || 'unknown');
    
    let detailedAnalysisHtml = '';
    if (detailedAnalysis && detailedAnalysis.length) {
        detailedAnalysisHtml = detailedAnalysis.map(analysis => {
            const cause = analysis.السبب || analysis.reason || analysis.cause || 'Unknown';
            const solution = analysis.الحل_المقترح || analysis.solution || analysis.action || 'No solution';
            const intensity_level = analysis.مستوى_الشدة || analysis.intensity_level || 'N/A';
            
            return `
                <div style="background: white; padding: 1rem; margin: 0.5rem 0; border-radius: 5px; border-right: 4px solid #FF9800;">
                    <strong style="color: #FF9800;">🔍 ${cause}</strong><br>
                    <span style="color: #666;">${solution}</span><br>
                    <small style="color: #999;">الشدة: ${intensity_level}/10</small>
                </div>
            `;
        }).join('');
    }
    
    const html = `
        <div class="result-content" style="border-left: 4px solid #FF9800; padding: 1.5rem; background: #f9f9f9; border-radius: 8px;">
            <div class="result-title" style="font-size: 1.3rem; margin-bottom: 1rem; color: #333;">
                ${reasonCategoryEmoji[result.primary_cause] || '🔊'} 
                ${result.primary_cause || 'تحليل البكاء'}
            </div>
            
            <div style="background: white; padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                <strong style="color: #666;">📋 تفاصيل البكاء:</strong>
                <div style="margin-top: 0.5rem;">
                    <div>⏱️ المدة: ${durationMinutes} دقيقة</div>
                    <div>📊 الشدة: ${intensity} / 10</div>
                    <div>🕐 الوقت: ${timeOfDayArabic(timeOfDay)}</div>
                </div>
            </div>
            
            ${result.urgent_recommendation ? `
            <div style="background: #FFF3E0; padding: 1rem; border-radius: 5px; margin: 1rem 0; border-right: 4px solid #FF5722;">
                <strong style="color: #E65100;">⚡ الخطوة الأساسية:</strong> <br>
                <span style="color: #D84315;">${result.urgent_recommendation}</span>
            </div>
            ` : ''}
            
            ${detailedAnalysisHtml ? `
            <div style="margin: 1rem 0;">
                <strong style="color: #666;">🔬 التحليل التفصيلي:</strong>
                ${detailedAnalysisHtml}
            </div>
            ` : ''}
            
            <div style="margin-top: 1.5rem; font-size: 0.85rem; color: #999; text-align: center; padding-top: 1rem; border-top: 1px solid #eee;">
                ⏰ وقت التحليل: ${new Date().toLocaleString('ar-SA')}
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    container.classList.add('show');
    console.log('✅ تم عرض نتائج البكاء بنجاح');
    setTimeout(() => container.scrollIntoView({ behavior: 'smooth' }), 300);
}

// ==================== فحص الأعراض ====================

async function handleSymptomGuidance() {
    const form = document.getElementById('symptomForm');
    const resultBox = document.getElementById('symptomResult');

    const formData = {
        action: 'symptom_guidance',
        symptom: form.querySelector('[name="symptom"]').value,
        temperature: form.querySelector('[name="temperature"]').value || null,
        duration_hours: form.querySelector('[name="duration_hours"]').value || null,
        age_months: form.querySelector('[name="age_months"]').value || null,
        guest_name: form.querySelector('[name="guest_name"]').value,
        guest_email: form.querySelector('[name="guest_email"]').value
    };

    showLoading();

    try {
        const response = await fetch('http://127.0.0.1:5000/api/symptom-guidance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const data = await response.json();
        hideLoading();

        if (data.success) {
            displaySymptomResult(data, resultBox);
        } else {
            showAlert('❌ خطأ: ' + (data.message || 'خطأ غير معروف'), 'error');
            resultBox.classList.remove('show');
        }
    } catch (error) {
        hideLoading();
        showAlert('❌ خطأ في الاتصال: ' + error.message, 'error');
    }
}

function displaySymptomResult(result, container) {
    if (!result || !result.data) return;

    const urgencyColor = { HIGH: '#F44336', MEDIUM: '#FF9800', LOW: '#4CAF50' };
    const urgencyLabel = {
        HIGH: '🔴 طوارئ - اطلبي المساعدة الطبية فوراً',
        MEDIUM: '🟠 متوسط - استشيري طبيب الأطفال',
        LOW: '🟢 منخفض - يمكن المتابعة في المنزل'
    };

    const urgency = result.urgency_level || 'MEDIUM';
    const symptomName = result.data.arabic_name || 'Unknown';

    const homeCareHtml = result.data.home_care
        ? `<li>${result.data.home_care}</li>` : '';

    const html = `
        <div class="result-content" style="border-left: 4px solid ${urgencyColor[urgency]}; padding:1.5rem; background:#f9f9f9; border-radius:8px;">
            <div class="result-title" style="font-size:1.3rem; color:${urgencyColor[urgency]};">
                ${urgencyLabel[urgency]}
            </div>
            <div style="background:white; padding:1rem; border-radius:5px; margin:1rem 0;">
                <strong>🏥 العرض الأساسي:</strong>
                <div style="font-size:1.2rem; color:#2196F3; margin-top:0.5rem;">
                    ${symptomName}
                </div>
                ${result.data.normal_range ? `<div style="color:#999; margin-top:0.5rem;">النطاق الطبيعي: ${result.data.normal_range}</div>` : ''}
            </div>
            ${homeCareHtml ? `<div style="background:#E8F5E9; padding:1rem; border-radius:5px;"><strong>💊 العلاجات المنزلية:</strong><ul>${homeCareHtml}</ul></div>` : ''}
            <div style="background:#FFEBEE; padding:1rem; border-radius:5px; margin:1rem 0;">
                <strong>⚠️ ما يجب فعله:</strong>
                <div style="color:#D32F2F; margin-top:0.5rem;">${result.data.action}</div>
            </div>
            <div style="margin-top:1rem; font-size:0.85rem; color:#999; text-align:center;">⏰ وقت التحليل: ${new Date(result.timestamp).toLocaleString('ar-SA')}</div>
        </div>
    `;

    container.innerHTML = html;
    container.classList.add('show');
    setTimeout(() => container.scrollIntoView({ behavior: 'smooth' }), 300);
}

// ==================== تحميل البيانات الإحصائية ====================

async function loadStatistics() {
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'get_statistics' })
        });
        
        const data = await response.json();
        console.log('📊 بيانات الإحصائيات:', data);
        
        if (data.success && data.data) {
            const stats = data.data;
            
            // Update stat cards
            const stat1 = document.getElementById('stat-babies');
            const stat2 = document.getElementById('stat-parents');
            const stat3 = document.getElementById('stat-vaccines');
            const stat4 = document.getElementById('stat-healthy');
            
            if (stat1) {
                const num1 = stat1.querySelector('.stat-number');
                if (num1) num1.textContent = stats.growth?.active_babies || stats.babies_tracked || '0';
            }
            
            if (stat2) {
                const num2 = stat2.querySelector('.stat-number');
                if (num2) num2.textContent = stats.parents_count || '0';
            }
            
            if (stat3) {
                const num3 = stat3.querySelector('.stat-number');
                if (num3) num3.textContent = stats.vaccination?.total_vaccines || stats.vaccines_given || '0';
            }
            
            if (stat4) {
                const num4 = stat4.querySelector('.stat-number');
                if (num4) num4.textContent = stats.growth?.healthy_percentage || '95%';
            }
        }
    } catch (error) {
        console.error('❌ خطأ في تحميل الإحصائيات:', error);
    }
}

// ==================== تحميل الأعراض الشائعة ====================

async function loadCommonSymptoms() {
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'get_common_symptoms' })
        });
        
        const data = await response.json();
        console.log('📋 بيانات الأعراض:', data);
        
        if (data.success && data.data) {
            const symptomsContainer = document.getElementById('symptomsContainer');
            
            if (symptomsContainer) {
                let html = '';
                for (const symptom of data.data) {
                    const name = symptom.symptom_name || symptom.name || 'Unknown';
                    const desc = symptom.description || 'لا توجد وصف';
                    const remedies = symptom.home_remedies || symptom.remedies || 'لا توجد علاجات محددة';
                    
                    html += `
                        <div class="symptom-item" style="background: white; padding: 1rem; margin: 0.5rem; border-radius: 5px; border-right: 4px solid #9C27B0;">
                            <h4 style="color: #6A1B9A; margin: 0 0 0.5rem 0;">${name}</h4>
                            <p style="color: #666; margin: 0 0 0.5rem 0; font-size: 0.9rem;">${desc}</p>
                            <small style="color: #999;"><strong>🏠 العلاجات:</strong> ${remedies}</small>
                        </div>
                    `;
                }
                
                symptomsContainer.innerHTML = html;
            }
        }
    } catch (error) {
        console.error('❌ خطأ في تحميل الأعراض:', error);
    }
}

// ==================== تحميل معايير النمو ====================

async function loadGrowthBenchmarks() {
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'get_growth_benchmarks' })
        });
        
        const data = await response.json();
        console.log('📈 بيانات معايير النمو:', data);
        
        if (data.success && data.data) {
            const tbody = document.getElementById('benchmarkTableBody');
            
            if (tbody) {
                let html = '';
                for (const benchmark of data.data) {
                    const age = benchmark.age_months || 0;
                    const avgWeight = benchmark.avg_weight_kg || 'N/A';
                    const weightRange = `${benchmark.weight_lower_percentile_kg || '?'} - ${benchmark.weight_upper_percentile_kg || '?'}`;
                    const avgHeight = benchmark.avg_height_cm || 'N/A';
                    const heightRange = `${benchmark.height_lower_percentile_cm || '?'} - ${benchmark.height_upper_percentile_cm || '?'}`;
                    
                    html += `
                        <tr>
                            <td>${age} شهر</td>
                            <td>${avgWeight} كغ</td>
                            <td>${weightRange} كغ</td>
                            <td>${avgHeight} سم</td>
                            <td>${heightRange} سم</td>
                        </tr>
                    `;
                }
                
                tbody.innerHTML = html;
            }
        }
    } catch (error) {
        console.error('❌ خطأ في تحميل معايير النمو:', error);
    }
}

// ==================== دوال مساعدة ====================

function showLoading() {
    const modal = document.getElementById('loadingModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function hideLoading() {
    const modal = document.getElementById('loadingModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function showAlert(message, type = 'info') {
    const modal = document.getElementById('alertModal');
    const messageDiv = document.getElementById('alertMessage');
    
    if (messageDiv) {
        messageDiv.innerHTML = message;
    }
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeAlert() {
    const modal = document.getElementById('alertModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function handleNewsletterSignup(event) {
    event.preventDefault();
    const email = event.target.querySelector('input[type="email"]').value;
    showAlert(`✅ شكراً لك! تم الاشتراك بـ ${email} بنجاح.\n\nسوف تتلقين آخر الأخبار والنصائح المهمة.`);
    event.target.reset();
}

function attachNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            navLinks.forEach(l => l.classList.remove('active'));
            e.target.classList.add('active');
            // if mobile menu is open, hide it after clicking
            const nav = document.querySelector('.navbar-nav');
            if (nav && nav.classList.contains('show')) {
                nav.classList.remove('show');
            }
        });
    });
}

function timeOfDayArabic(timeOfDay) {
    const times = {
        'morning': '🌅 الصباح',
        'afternoon': '☀️ بعد الظهر',
        'evening': '🌆 المساء',
        'night': '🌙 الليل'
    };
    return times[timeOfDay] || timeOfDay;
}

// ==================== تحميل المكتبة: المقالات والفيديوهات ====================

async function loadLibraryContent() {
    await loadPublicArticles();
    await loadPublicVideos();
}

async function loadPublicArticles() {
    try {
        const response = await fetch('api/get_public_articles.php');
        const articles = await response.json();
        
        const container = document.getElementById('articlesContainer');
        if (!container) return;
        
        if (articles.length === 0) {
            container.innerHTML = '<div class="loading-spinner">لا توجد مقالات حالياً</div>';
            return;
        }
        
        let html = '';
        articles.forEach(article => {
            html += `
                <div class="article-card">
                    <div class="card-header">
                        <i class="fas fa-book"></i> ${article.title}
                    </div>
                    <div class="card-body">
                        <p class="card-text">${article.excerpt}</p>
                        <div class="article-meta">
                            <span class="badge">${article.category}</span>
                            <small class="text-muted">${article.date}</small>
                        </div>
                        <small style="color: var(--text-secondary);">👤 ${article.author}</small>
                    </div>
                    <div class="card-footer">
                        <button class="read-more-btn" onclick="alert('يتطلب تسجيل الدخول للقراءة الكاملة')">
                            <i class="fas fa-arrow-left"></i> اقرأ المزيد
                        </button>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    } catch (error) {
        console.error('خطأ في تحميل المقالات:', error);
        document.getElementById('articlesContainer').innerHTML = '<div class="loading-spinner">حدث خطأ في تحميل المقالات</div>';
    }
}

async function loadPublicVideos() {
    try {
        const response = await fetch('api/get_public_videos.php');
        const videos = await response.json();
        
        const container = document.getElementById('videosContainer');
        if (!container) return;
        
        if (videos.length === 0) {
            container.innerHTML = '<div class="loading-spinner">لا توجد فيديوهات حالياً</div>';
            return;
        }
        
        let html = '';
        videos.forEach(video => {
            html += `
                <div class="video-card">
                    <div class="card-header">
                        <i class="fas fa-video"></i> ${video.title}
                    </div>
                    <div class="card-body">
                        <p class="card-text">${video.description}</p>
                        <div class="video-meta">
                            <span class="badge">${video.age_group}</span>
                            <span class="badge" style="background: #4caf50;">${video.category}</span>
                        </div>
                        <small style="color: var(--text-secondary);">👤 ${video.author} | 📅 ${video.date}</small>
                    </div>
                    <div class="card-footer">
                        <a href="${video.video_url}" target="_blank" class="watch-btn">
                            <i class="fas fa-play"></i> شاهد الفيديو
                        </a>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    } catch (error) {
        console.error('خطأ في تحميل الفيديوهات:', error);
        document.getElementById('videosContainer').innerHTML = '<div class="loading-spinner">حدث خطأ في تحميل الفيديوهات</div>';
    }
}

function showLibraryTab(tabName) {
    // إخفاء جميع التبويبات
    document.getElementById('articlesTab').classList.remove('active');
    document.getElementById('videosTab').classList.remove('active');
    
    // إظهار التبويب المختار
    if (tabName === 'articles') {
        document.getElementById('articlesTab').classList.add('active');
    } else if (tabName === 'videos') {
        document.getElementById('videosTab').classList.add('active');
    }
    
    // تحديث الأزرار
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

// تحميل المكتبة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    loadLibraryContent();
});

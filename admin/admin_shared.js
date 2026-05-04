const API_BASE_URL = '../admin_handler.php';

function showAlert(message, type = 'info') {
    console.log('📢 عرض تنبيه:', message);
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + type;
    alertDiv.style.width = '100%';
    alertDiv.innerHTML = '<span style="flex: 1;">' + message + '</span><button onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; color: inherit; font-size: 1.2rem; padding: 0;">✕</button>';
    
    const mainContent = document.querySelector('.main-content');
    console.log('البحث عن .main-content:', mainContent ? '✅ موجود' : '❌ غير موجود');
    
    if (!mainContent) {
        console.warn('استخدام browser alert بديل');
        alert(message);
        return;
    }
    
    if (mainContent.firstChild) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
    } else {
        mainContent.appendChild(alertDiv);
    }
    
    console.log('✅ تم إدراج التنبيه بنجاح');
    
    setTimeout(() => {
        try {
            if (alertDiv && alertDiv.parentElement) {
                alertDiv.remove();
            }
        } catch(e) {
            console.error('خطأ في إزالة التنبيه:', e);
        }
    }, 5000);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
}

function filterTable(tableId, searchValue) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const searchTerm = searchValue.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

function attachSearchListener(searchId, tableId) {
    const searchInput = document.getElementById(searchId);
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            filterTable(tableId, e.target.value);
        });
    }
}

document.addEventListener('click', (e) => {
    const modal = document.getElementById('editModal');
    if (e.target === modal) {
        closeModal();
    }

    const btn = e.target.closest('button, [data-action]');
    if (!btn) return;

    if (btn.classList.contains('btn-view')) {
        const id = btn.getAttribute('data-id');
        const type = btn.getAttribute('data-type');
        showAlert('عرض بيانات: ' + type + ' #' + id, 'info');
        return;
    }

    if (btn.classList.contains('btn-edit')) {
        const id = btn.getAttribute('data-id');
        showAlert('تعديل #' + id, 'info');
        openEditModal(id);
        return;
    }

    if (btn.classList.contains('btn-delete')) {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        const paramName = btn.getAttribute('data-param') || 'id';
        const label = btn.getAttribute('data-label');
        if (action && id) {
            const body = { action: action };
            body[paramName] = id;
            const confirmText = label ? `هل أنت متأكد من حذف ${label}؟` : 'هل أنت متأكد من الحذف؟';
            confirmAndSend(body, confirmText, (res) => {
                showAlert('تم الحذف', 'success');
                setTimeout(() => location.reload(), 1000);
            });
        } else {
            if (confirm('هل أنت متأكد من الحذف؟')) {
                handleDelete(id);
            }
        }
        return;
    }

    if (btn.classList.contains('btn-archive') || btn.classList.contains('btn-unarchive')) {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        const label = btn.getAttribute('data-label');
        if (action && id) {
            const body = { action: action, child_id: id };
            const isUnarchive = btn.classList.contains('btn-unarchive');
            const confirmText = label ? `هل أنت متأكد من ${isUnarchive ? 'إلغاء أرشفة' : 'أرشفة'} ${label}؟` : (isUnarchive ? 'هل أنت متأكد من إلغاء الأرشفة؟' : 'هل أنت متأكد من الأرشفة؟');
            confirmAndSend(body, confirmText, (res) => {
                showAlert(isUnarchive ? 'تم إلغاء الأرشفة' : 'تم الأرشفة', 'success');
                setTimeout(() => location.reload(), 1000);
            });
        }
        return;
    }
});

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

document.addEventListener('click', (e) => {
    if (e.target && e.target.classList && e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

function openEditModal(id) {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.classList.add('show');
    }
}

async function handleDelete(id) {
    showAlert('جاري الحذف...', 'info');
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        if (document.querySelector('script[src="' + src + '"]')) return resolve();
        const s = document.createElement('script');
        s.src = src;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Failed to load ' + src));
        document.head.appendChild(s);
    });
}

function confirmAndSend(bodyObj, confirmText = 'هل أنت متأكد؟', onSuccess = null) {
    const swalUrl = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    loadScript(swalUrl).then(() => {
        Swal.fire({
            title: confirmText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                showAlert('جاري التنفيذ...', 'info');
                fetch(API_BASE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(bodyObj)
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        if (onSuccess) onSuccess(data);
                    } else {
                        showAlert('خطأ: ' + (data.message || 'فشل العملية'), 'danger');
                    }
                }).catch(err => {
                    console.error('confirmAndSend error', err);
                    showAlert('حدث خطأ في الاتصال', 'danger');
                });
            }
        });
    }).catch(err => {
        console.error('Failed to load Swal', err);
        if (confirm(confirmText)) {
            fetch(API_BASE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(bodyObj)
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    if (onSuccess) onSuccess(data);
                } else {
                    showAlert('خطأ: ' + (data.message || 'فشل العملية'), 'danger');
                }
            }).catch(e => { console.error(e); showAlert('حدث خطأ', 'danger'); });
        }
    });
}

function getActivityLabel(activityType) {
    const labels = {
        'breast_feed': 'رضاعة طبيعية',
        'formula_feed': 'رضاعة صناعية',
        'nap': 'قيلولة',
        'night_sleep': 'نوم ليلي',
        'growth_record': 'تسجيل نمو'
    };
    return labels[activityType] || activityType;
}

function getVaccineStatus(status) {
    const statuses = {
        'due': 'مستحقة',
        'administered': 'مقدمة',
        'missed': 'فاتت'
    };
    return statuses[status] || status;
}


function loadDashboardData() {
    console.log('📋 تحميل بيانات لوحة التحكم');
    renderDashboardCharts();
}

function renderDashboardCharts() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js غير محمّل');
        return;
    }
    const usersCtx = document.getElementById('usersChart');
    const vaccinesCtx = document.getElementById('vaccinesChart');
    if (usersCtx) {
        console.log('>>> usersChart canvas found', usersCtx);
        const ctx = usersCtx.getContext('2d');
        const labels = [];
        const data = [];
        for (const type in dashboardData.userStats) {
            labels.push(type);
            data.push(dashboardData.userStats[type]);
        }
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#9d84ca','#6b5eb2','#10b981','#f59e0b']
                }]
            },
            options: {
                plugins: {legend:{position:'bottom'}},
                responsive: true
            }
        });
    }
    if (vaccinesCtx) {
        console.log('>>> vaccinesChart canvas found', vaccinesCtx);
        const ctx2 = vaccinesCtx.getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['أطفال','مستحقة','متأخرة','أسبوع'],
                datasets: [{
                    label: 'العدد',
                    data: [dashboardData.totalChildren, dashboardData.dueVaccines, dashboardData.overdueVaccines, dashboardData.upcomingVaccines],
                    backgroundColor: ['#10b981','#f59e0b','#e53e3e','#3182ce']
                }]
            },
            options: {
                plugins: {legend:{display:false}},
                responsive: true
            }
        });
    }
   
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof dashboardData !== 'undefined') {
        loadDashboardData();
    }
});

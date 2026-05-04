// نظام إشعارات Toast لصفحات الأهل
(function() {
    'use strict';
    
    // معرف آخر إشعار تم عرضه (لتجنب العرض المكرر)
    let lastNotificationTime = localStorage.getItem('lastNotificationTime') || 0;
    let isCheckingNotifications = false;
    
    // دالة لعرض Toast notification
    function showToastNotification(notification) {
        const toastContainer = document.getElementById('appointmentToastsContainer');
        if (!toastContainer) return;
        
        // إنشاء عنصر Toast
        const toastElement = document.createElement('div');
        toastElement.className = 'toast align-items-center border-0';
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        
        // تحديد اللون بناءً على نوع الإشعار
        let bgColor = 'bg-info';
        let icon = 'bi-bell';
        let typeText = 'إخطار';
        
        if (notification.notification_type === 'confirmation') {
            bgColor = 'bg-success';
            icon = 'bi-check-circle';
            typeText = 'تأكيد';
        } else if (notification.notification_type === 'reminder') {
            bgColor = 'bg-warning';
            icon = 'bi-exclamation-circle';
            typeText = 'تذكير';
        } else if (notification.notification_type === 'follow_up') {
            bgColor = 'bg-primary';
            icon = 'bi-clipboard-check';
            typeText = 'متابعة';
        }
        
        const formattedTime = new Date(notification.sent_at).toLocaleString('ar-EG');
        const appointmentTime = new Date(notification.appointment_date + ' ' + notification.appointment_time)
            .toLocaleString('ar-EG', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        
        toastElement.innerHTML = `
            <div class="${bgColor} text-white d-flex align-items-center w-100 p-3">
                <i class="bi ${icon} ms-3 fs-5"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold">${typeText}: ${notification.child_name}</div>
                    <div class="small" style="opacity: 0.9">
                        <strong>${notification.doctor_name}</strong><br>
                        📅 ${appointmentTime}
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" 
                        aria-label="إغلاق" style="font-size: 0.7rem;"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastElement);
        
        // إنشاء Bootstrap Toast وعرضه
        const bootstrap = window.bootstrap;
        if (bootstrap && bootstrap.Toast) {
            const toast = new bootstrap.Toast(toastElement, {
                delay: 15000, // إغلاق تلقائي بعد 15 ثانية
                autohide: true
            });
            toast.show();
            
            // إزالة العنصر من الـ DOM بعد إغلاقه
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
    }
    
    // دالة لفحص الإشعارات الجديدة
    function checkForNewNotifications() {
        if (isCheckingNotifications) return;
        
        isCheckingNotifications = true;
        
        const endpoint = window.location.pathname.includes('/parent/') ? 'get_new_appointment_notifications.php' : 'parent/get_new_appointment_notifications.php';
        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notifications && data.notifications.length > 0) {
                    // عرض كل إشعار جديد
                    data.notifications.forEach(notification => {
                        // تحويل التاريخ إلى timestamp
                        const notifTime = new Date(notification.sent_at).getTime();
                        
                        if (notifTime > lastNotificationTime) {
                            showToastNotification(notification);
                            lastNotificationTime = notifTime;
                        }
                    });
                    
                    // حفظ وقت آخر إشعار
                    localStorage.setItem('lastNotificationTime', lastNotificationTime);
                    
                    // تحديث عداد الإشعارات في الـ badge
                    updateNotificationBadge(data.count);
                }
            })
            .catch(error => {
                console.warn('خطأ في جلب الإشعارات:', error);
            })
            .finally(() => {
                isCheckingNotifications = false;
            });
    }
    
    // دالة لتحديث عداد الإشعارات
    function updateNotificationBadge(count) {
        const badge = document.querySelector('[data-notification-badge]');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    // بدء الفحص الأول عند تحميل الصفحة
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            checkForNewNotifications();
            // فحص كل 30 ثانية
            setInterval(checkForNewNotifications, 30000);
        });
    } else {
        checkForNewNotifications();
        setInterval(checkForNewNotifications, 30000);
    }
})();

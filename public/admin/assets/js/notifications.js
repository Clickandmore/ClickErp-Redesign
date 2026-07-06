$(document).ready(function() {
    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function formatNotificationTime(value) {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return escapeHtml(value);
        }

        return date.toLocaleString(document.documentElement.lang || undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function iconForType(type) {
        const icons = {
            complaint_status_changed: 'fa-arrow-right-arrow-left',
            complaint_assigned: 'fa-user-check',
            complaint_reassigned: 'fa-user-clock',
            complaint_priority_changed: 'fa-flag',
            complaint_updated: 'fa-message',
            new_complaint: 'fa-circle-exclamation'
        };

        return icons[type] || 'fa-bell';
    }

    function renderState(icon, title, message, modifier) {
        return `
            <div class="cm-notification-state ${modifier || ''}">
                <i class="fa ${icon}"></i>
                <strong>${title}</strong>
                <span>${message}</span>
            </div>
        `;
    }

    // Function to load notifications
    function loadNotifications() {
        $.ajax({
            url: '/admin/notifications',
            type: 'GET',
            success: function(response) {
                const notifications = response.notifications;
                const unreadCount = response.unread_count;

                // Update notification badge
                if (unreadCount > 0) {
                    $('.notification-badge').text(unreadCount).show();
                    $('.notification-summary').text(unreadCount + ' إشعارات غير مقروءة');
                    $('.mark-all-read').prop('disabled', false).show();
                } else {
                    $('.notification-badge').hide();
                    $('.notification-summary').text('لا توجد إشعارات غير مقروءة');
                    $('.mark-all-read').prop('disabled', true).show();
                }

                // Update notification list
                const notificationList = $('.notification-list');
                notificationList.empty();

                if (notifications.length === 0) {
                    notificationList.append(renderState('fa-bell-slash', 'لا توجد إشعارات', 'ستظهر التحديثات المهمة هنا عند وصولها.'));
                    return;
                }

                notifications.forEach(function(notification) {
                    const title = escapeHtml(notification.data.title);
                    const message = escapeHtml(notification.data.message);
                    const link = notification.data.link ? escapeHtml(notification.data.link) : '#';
                    const time = formatNotificationTime(notification.created_at);
                    const unreadClass = notification.read_at ? '' : 'is-unread';
                    const icon = iconForType(notification.data.type);
                    const notificationHtml = `
                        <div class="cm-notification-item ${unreadClass}">
                            <a class="cm-notification-icon-link cm-notification-icon" href="${link}" aria-label="${title}">
                                <i class="fa ${icon}"></i>
                            </a>
                            <a class="cm-notification-body text-decoration-none" href="${link}">
                                <div class="cm-notification-title">
                                    ${!notification.read_at ? '<span class="cm-notification-unread-dot"></span>' : ''}
                                    <span>${title}</span>
                                </div>
                                <p class="cm-notification-message">${message}</p>
                                <small class="cm-notification-time"><i class="fa fa-clock"></i>${time}</small>
                            </a>
                            ${!notification.read_at ? `
                                <button type="button" class="cm-notification-read-action mark-read" data-id="${notification.id}" title="تحديد كمقروء">
                                    <i class="fa fa-check"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                    notificationList.append(notificationHtml);
                });
            },
            error: function(xhr) {
                const notificationList = $('.notification-list');
                notificationList.empty();
                notificationList.append(renderState('fa-triangle-exclamation', 'تعذر تحميل الإشعارات', 'حاول تحديث الصفحة أو تسجيل الدخول مرة أخرى.', 'text-danger'));

                if (window.console) {
                    console.warn('Failed to load admin notifications', xhr.status, xhr.responseText);
                }
            }
        });
    }

    // Load notifications on page load
    loadNotifications();

    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);

    // Mark single notification as read
    $(document).on('click', '.mark-read', function(event) {
        event.preventDefault();
        event.stopPropagation();
        const notificationId = $(this).data('id');
        $.ajax({
            url: '/admin/notifications/' + notificationId + '/mark-as-read',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                loadNotifications();
            }
        });
    });

    // Mark all notifications as read
    $(document).on('click', '.mark-all-read', function(event) {
        event.preventDefault();
        event.stopPropagation();
        $.ajax({
            url: '/admin/notifications/mark-all-read',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                loadNotifications();
            }
        });
    });
});

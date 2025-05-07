$(document).ready(function() {
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
                } else {
                    $('.notification-badge').hide();
                }

                // Update notification list
                const notificationList = $('.notification-list');
                notificationList.empty();

                if (notifications.length === 0) {
                    notificationList.append('<div class="p-3 text-center">لا توجد إشعارات</div>');
                    return;
                }

                notifications.forEach(function(notification) {
                    const notificationHtml = `
                        <div class="notification-item p-3 border-bottom ${notification.read_at ? '' : 'bg-light'}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="mb-1">${notification.data.message}</p>
                                    <small class="text-muted">${moment(notification.created_at).fromNow()}</small>
                                </div>
                                ${!notification.read_at ? `
                                    <button class="btn btn-sm btn-link mark-read" data-id="${notification.id}">
                                        <i class="fa fa-check"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                    notificationList.append(notificationHtml);
                });
            }
        });
    }

    // Load notifications on page load
    loadNotifications();

    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);

    // Mark single notification as read
    $(document).on('click', '.mark-read', function() {
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
    $(document).on('click', '.mark-all-read', function() {
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

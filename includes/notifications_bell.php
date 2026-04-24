<?php
/*
 * Reusable bell + dropdown partial. Include AFTER bootstrap/functions.php is
 * loaded and when the visitor is logged in. Renders:
 *   - a bell icon button with the unread count badge,
 *   - the dropdown panel populated with the user's latest notifications,
 *   - a hidden data endpoint URL that the JS picks up.
 *
 * Usage (from any page, inside the body):
 *   require __DIR__ . '/../includes/notifications_bell.php';
 * The CSS in assets/css/style.css and the JS in assets/js/notifications.js
 * handle layout + interactions.
 */

if (!function_exists('current_user')) {
    require_once __DIR__ . '/functions.php';
}

$bellUser = current_user();
if (!$bellUser) {
    // Not logged in -> render nothing. This keeps the partial safe to
    // include even on pages where the session may have just expired.
    return;
}

$bellUserId = (int) $bellUser['id'];
$bellNotifications = fetch_user_notifications($bellUserId, 20);
$bellUnreadCount = count_unread_notifications($bellUserId);
$bellApiUrl = base_url('api/notifications.php');
?>
<div class="notif-bell" id="notifBell" data-api-url="<?php echo h($bellApiUrl); ?>">
    <button type="button" class="notif-bell__btn" id="notifBellBtn"
            aria-haspopup="true" aria-expanded="false" aria-label="Ειδοποιήσεις">
        <svg class="notif-bell__icon" width="22" height="22" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
        </svg>
        <span class="notif-bell__badge<?php echo $bellUnreadCount === 0 ? ' is-hidden' : ''; ?>"
              id="notifBellBadge"><?php echo (int) $bellUnreadCount; ?></span>
    </button>

    <div class="notif-panel" id="notifPanel" role="dialog" aria-label="Ειδοποιήσεις">
        <div class="notif-panel__header">
            <h3 class="notif-panel__title">Ειδοποιήσεις</h3>
            <button type="button" class="notif-panel__clear" id="notifDeleteAllRead">
                Delete all read
            </button>
        </div>

        <div class="notif-panel__list" id="notifPanelList">
            <?php if ($bellNotifications === []): ?>
                <div class="notif-panel__empty">Δεν υπάρχουν ειδοποιήσεις ακόμη.</div>
            <?php else: ?>
                <?php foreach ($bellNotifications as $notif): ?>
                    <?php $isUnread = (int) $notif['is_read'] === 0; ?>
                    <article class="notif-item<?php echo $isUnread ? ' is-unread' : ''; ?>"
                             data-id="<?php echo (int) $notif['id']; ?>">
                        <button type="button" class="notif-item__close" aria-label="Διαγραφή">×</button>
                        <div class="notif-item__body">
                            <p class="notif-item__message"><?php echo h($notif['message']); ?></p>
                            <p class="notif-item__date">
                                <?php echo h(date('d/m/Y, H:i', strtotime($notif['created_at']))); ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="<?php echo h(base_url('assets/js/notifications.js')); ?>?v=<?php echo @filemtime(__DIR__ . '/../assets/js/notifications.js') ?: time(); ?>" defer></script>
